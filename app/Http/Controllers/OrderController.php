<?php

namespace App\Http\Controllers;

use App\Jobs\CreateStripeSessionAndOrder;
use App\Models\Order;
use App\Models\Product;
use App\Services\StripeService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class OrderController extends Controller
{
    // 1. حقن خدمة Stripe في الكنترولر
    public function __construct(private StripeService $stripeService)
    {
    }

    // 2. هذه الوظائف لم تتغير
    public function getRevenue()
    {
        $revenue = Order::sum('total_price');
        return response()->json(['count' => $revenue]);
    }

    public function count()
    {
        $count = Order::count();
        return response()->json(['count' => $count]);
    }

    public function orders()
    {
        $orders = Order::with(['user', 'items.product'])->paginate(6);
        return response()->json(['orders' => $orders]);
    }

    public function userOrders(){
        $orders = auth()->user()->orders()->with('items.product')->paginate(6);
        return response()->json(['orders' => $orders]);
    }


     public function store(Request $request)
    {
        $validated = $request->validate([
            'location' => 'nullable|string',
            'cart' => 'required|array',
            'cart.*.product_id' => 'required|integer|exists:products,id',
            'cart.*.quantity' => 'required|integer|min:1',
        ]);

        $user = auth()->user();
        $totalPrice = 0;
        $lineItems = [];
        $vendors = [];

        // 🛒 Build line_items and check vendors
        foreach ($validated['cart'] as $item) {
            $product = Product::with('vendor')->findOrFail($item['product_id']);

            if (!$product->vendor->stripe_charges_enabled) {
                return response()->json([
                    'error' => "Vendor {$product->vendor->name} is not ready to receive payments."
                ], 422);
            }

            $itemPrice = $product->price;
            $totalPrice += $itemPrice * $item['quantity'];
            $vendors[$product->vendor_id] = true;

            $lineItems[] = [
                'price_data' => [
                    'currency' => 'usd',
                    'product_data' => [
                        'name' => $product->name,
                    ],
                    'unit_amount' => $itemPrice * 100,
                ],
                'quantity' => $item['quantity'],
            ];
        }

        // ✅ Create the order
        $order = $user->orders()->create([
            'total_price' => $totalPrice,
            'location' => $validated['location'] ?? 'cairo',
            'amount_platform_fee' => 1, 
            'status' => 'pending',
        ]);

        // ✅ Create order items
        foreach ($validated['cart'] as $item) {
            $product = Product::findOrFail($item['product_id']);
            $order->items()->create([
                'product_id' => $item['product_id'],
                'vendor_id' => $product->vendor_id,
                'quantity' => $item['quantity'],
            ]);
            $product->decrement('stock', $item['quantity']);
        }

        // 💳 Create Stripe Checkout Session
        try {
            $session = $this->stripeService->client()->checkout->sessions->create([
                'mode' => 'payment',
                'line_items' => $lineItems,
                'success_url' => env('FRONTEND_URL') . '/success?order_id=' . $order->id . '&session_id={CHECKOUT_SESSION_ID}',
                'cancel_url' => env('FRONTEND_URL') . '/cancel?order_id=' . $order->id,
                'metadata' => ['order_id' => (string)$order->id],
                'payment_intent_data' => [
                    'transfer_group' => "ORDER_{$order->id}",
                    'metadata' => ['order_id' => (string)$order->id],
                ],
            ]);

            // 📝 Store the session_id and payment_intent_id and link them to the order
            $order->update([
                'stripe_session_id' => $session->id,
                'stripe_payment_intent' => $session->payment_intent, // ✅ Add this line
            ]);

            return response()->json(['url' => $session->url], 200);

        } catch (\Exception $e) {
            Log::error('Stripe Checkout session creation failed: ' . $e->getMessage());
            $order->delete(); // 🗑️ If it fails, delete the order
            return response()->json(['error' => 'Failed to create a payment session.'], 500);
        }
    }
}
