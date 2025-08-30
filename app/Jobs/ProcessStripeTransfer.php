<?php

namespace App\Jobs;

use App\Models\OrderItem;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Stripe\StripeClient;

class ProcessStripeTransfer implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    protected $orderItemId;

    public function __construct($orderItemId)
    {
        // ✅ يتم الآن استقبال وسيطة واحدة فقط
        $this->orderItemId = $orderItemId;
    }

    public function handle()
    {
        $orderItem = OrderItem::with(['product', 'vendor'])->findOrFail($this->orderItemId);
        $stripe = new StripeClient(config('services.stripe.secret'));

        try {
            $amount = $orderItem->quantity * $orderItem->product->price * 100;
            
            // ✅ الحصول على Order ID من Order Item
            $orderId = $orderItem->order_id;

            $transfer = $stripe->transfers->create([
                'amount'         => $amount,
                'currency'       => 'usd',
                'destination'    => $orderItem->vendor->stripe_account_id,
                'transfer_group' => "ORDER_{$orderId}",
                'metadata'       => [
                    'order_id'   => $orderId,
                    'product_id' => $orderItem->product_id,
                ],
            ]);

            Log::info("✅ Transfer {$transfer->id} created for Order {$orderId}, Item {$this->orderItemId}");
        } catch (\Exception $e) {
            Log::error("❌ Transfer failed for Order {$orderId}, Item {$this->orderItemId}: " . $e->getMessage());
            $this->release(30); // retry after 30s
        }
    }
}
