<?php

namespace App\Http\Controllers;

use App\Services\StripeService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log; // لإضافة رسائل في السجل

// app/Http/Controllers/ConnectController.php
class ConnectController extends Controller
{
    // 1. استخدام StripeService في المنشئ (Constructor)
    public function __construct(private StripeService $stripeService)
    {
    }

    public function start(Request $request)
    {
        $user = auth()->user();

        // 2. التحقق من وجود المستخدم
        if (!$user) {
            // يمكن استخدام abort() بدلاً من ذلك، لكن هذا يعطي رسالة أوضح
            return response()->json(['error' => 'Unauthenticated'], 401);
        }

        // 3. إنشاء حساب مرتبط إذا لم يكن موجوداً
        if (!$user->stripe_account_id) {
            try {
                // نستخدم وظيفة createConnectedAccount من خدمتنا
                $acct = $this->stripeService->createConnectedAccount('express');
                
                // حفظ المعرف في قاعدة البيانات
                $user->forceFill(['stripe_account_id' => $acct->id])->save();
            } catch (\Exception $e) {
                Log::error('Stripe account creation failed: ' . $e->getMessage());
                return response()->json(['error' => 'Failed to create Stripe account.'], 500);
            }
        }

        // 4. إنشاء رابط التفعيل باستخدام خدمتنا
        try {
            // نستخدم وظيفة createAccountLink من خدمتنا
            $link = $this->stripeService->createAccountLink(
                $user->stripe_account_id,
                config('app.url') . '/connect/refresh',
                config('app.url') . '/connect/return'
            );
            
            // 5. إرجاع الرابط
            return response()->json(['url' => $link->url]);
        } catch (\Exception $e) {
            Log::error('Stripe account link creation failed: ' . $e->getMessage());
            return response()->json(['error' => 'Failed to create Stripe onboarding link.'], 500);
        }
    }
    public function return() {
        return redirect(config('app.frontend_url'));
    }
    
}