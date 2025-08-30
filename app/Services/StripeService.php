<?php
namespace App\Services;


use Stripe\Stripe;
use Stripe\Account;
use Stripe\AccountLink;
use Stripe\PaymentIntent;
use Stripe\Transfer;


class StripeService
{
public function __construct()
{
Stripe::setApiKey(config('services.stripe.secret'));
}


// Add this new method to fix the error
public function client()
{
    return new \Stripe\StripeClient(config('services.stripe.secret'));
}


// New method to handle Stripe Checkout
public function checkout()
{
    return $this->client()->checkout;
}


public function createConnectedAccount(string $type = 'express')
{
return Account::create(['type' => $type]);
}


public function createAccountLink(string $accountId, string $refreshUrl, string $returnUrl)
{
return AccountLink::create([
'account' => $accountId,
'refresh_url' => $refreshUrl,
'return_url' => $returnUrl,
'type' => 'account_onboarding',
]);
}


public function createPaymentIntent(int $amount, string $currency, array $metadata = [])
{
return PaymentIntent::create([
'amount' => $amount,
'currency' => $currency,
'payment_method_types' => ['card'],
'metadata' => $metadata,
]);
}


public function createTransfer(int $amount, string $currency, string $destination, array $metadata = [])
{
return Transfer::create([
'amount' => $amount,
'currency' => $currency,
'destination' => $destination,
'metadata' => $metadata,
]);
}
}
