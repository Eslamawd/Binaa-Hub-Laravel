<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Stripe\Webhook;
use App\Jobs\HandleStripeEvent;

class WebhookController extends Controller
{
    public function handle(Request $request)
    {
        $payload = $request->getContent();
        $sigHeader = $request->header('Stripe-Signature');
        $endpointSecret = config('services.stripe.webhook_secret');

        try {
            $event = Webhook::constructEvent($payload, $sigHeader, $endpointSecret);
        } catch (\Exception $e) {
            Log::error("❌ Webhook error: " . $e->getMessage());
            return response()->json(['error' => 'Invalid webhook'], 400);
        }

        // 🚀 ارمي الـ event على Job
        HandleStripeEvent::dispatch($event);

        return response()->json(['status' => 'success'], 200);
    }
}
