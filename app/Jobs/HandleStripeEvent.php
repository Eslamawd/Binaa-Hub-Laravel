<?php
namespace App\Jobs;

use App\Models\Order;
use App\Models\User;
use App\Jobs\ProcessStripeTransfer;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class HandleStripeEvent implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public $event;

    public function __construct($event)
    {
        $this->event = $event;
    }

    public function handle()
    {
        switch ($this->event->type) {
            case 'account.updated':
                $this->handleAccountUpdated();
                break;

            case 'capability.updated':
                $this->handleCapabilityUpdated();
                break;

            case 'checkout.session.completed':
                $this->handleCheckoutCompleted();
                break;
              // ✅ يتم تشغيل مهمة التحويل فقط عند توفر الرصيد
            case 'balance.available':
                $this->handleBalanceAvailable();
                break;

            case 'payment_intent.payment_failed':
                $this->handlePaymentFailed();
                break;

            default:
                Log::info("⚠️ Unhandled event type: {$this->event->type}");
        }
    }

    protected function handleAccountUpdated()
    {
        $account = $this->event->data->object;
        $user = User::where('stripe_account_id', $account->id)->first();

        if ($user) {
            $user->update([
                'stripe_charges_enabled'   => $account->charges_enabled,
                'stripe_details_submitted' => $account->details_submitted,
            ]);
            Log::info("✅ User {$user->id} updated from account.updated.");
        }
    }

    protected function handleCapabilityUpdated()
    {
        $capability = $this->event->data->object;
        $user = User::where('stripe_account_id', $capability->account)->first();

        if ($user && $capability->id === 'transfers' && $capability->status === 'active') {
            $user->stripe_charges_enabled = true;
            $user->save();
            Log::info("✅ Transfers capability enabled for user {$user->id}.");
        }
    }

    protected function handleCheckoutCompleted()
    {
        $session = $this->event->data->object;
        $orderId = $session->metadata->order_id ?? null;
        $sessionId = $session->id ?? null;
        $sessionPaymentIntent = $session->payment_intent ?? null;
        $sessionPaymentStatus = $session->payment_status ?? null;

        if ($orderId) {
            $order = Order::findOrFail($orderId);

            if ($order && $order->status === 'pending') {
                $order->update([
                    'status' => $sessionPaymentStatus,
                    'stripe_payment_intent' => $sessionPaymentIntent,
                    'stripe_session_id' => $sessionId,
                ]);




                Log::info("🚀 Dispatched transfers for Order {[$order , $sessionId, $sessionPaymentIntent, $sessionPaymentStatus]}");
            }
        }
    }

  protected function handleBalanceAvailable(): void
    {
        $object = $this->event->data->object;

        if ($object->object === 'balance_transaction') {
            // The `source` property of a `balance_transaction` is the ID of the
            // `charge` or `payment_intent` that resulted in this balance change.
            $sourceId = $object->source;
            
            // Look up the order using the Stripe Payment Intent ID
            $orders = Order::where('stripe_payment_intent', $sourceId)
                           ->where('status', 'paid')
                           ->get();

            if ($orders->isNotEmpty()) {
                foreach ($orders as $order) {
                    foreach ($order->items as $item) {
                        Log::info("📦 Dispatching transfer job for Order {$order->id}, Item {$item->id}");
                        ProcessStripeTransfer::dispatch($item->id);
                    }
                    Log::info("🚀 Dispatched transfers for Order {$order->id}.");
                }
            } else {
                Log::info("⚠️ No orders found for source ID: {$sourceId} or orders already processed.");
            }
        }
    }

    protected function handlePaymentFailed(): void
    {
        $intent = $this->event->data->object;
        $orderId = $intent->metadata->order_id ?? null;

        if ($orderId) {
            $order = Order::find($orderId);
            if ($order) {
                $order->update([
                    'status' => 'failed',
                    'stripe_payment_intent' => $intent->id,
                    'stripe_session_id' => null,
                ]);
                Log::info("❌ Payment failed for Order {$order->id}");
            }
        }
    }
}
