<?php
use App\Http\Controllers\Auth\AuthController;
use App\Http\Controllers\Auth\ForgotPasswordController;
use App\Http\Controllers\Auth\ResetPasswordController;
use App\Http\Controllers\Auth\VerificationController;
use App\Http\Controllers\CategoryController;
use App\Http\Controllers\ConnectController;
use App\Http\Controllers\GeminiController;
use App\Http\Controllers\OrderController;
use App\Http\Controllers\ProductController;
use App\Http\Controllers\StoreController;
use App\Http\Controllers\WebhookController;
use App\Http\Middleware\AdminMiddleware;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;


    // Category routes
     Route::get('/categories/all-req', [CategoryController::class, 'getByAdmin']); 
     Route::get('/categories', [CategoryController::class, 'index']);
     Route::get('/categories/children', [CategoryController::class, 'getAllCat']);
     // Product routes
     Route::get('/product',[ProductController::class, 'index']);
     Route::get('/product/{id}',[ProductController::class, 'show']);

     Route::get('stores/{id}', [StoreController::class, 'show']);



Route::middleware(['auth:sanctum'])->group(function () {

    // Connect to Stripe Vendor
    Route::post('/connect/start', [ConnectController::class, 'start']);

    //verification phone
    Route::post('/phone/send', [VerificationController::class, 'send']);
    Route::post('/phone/resend', [VerificationController::class, 'resend']);
    Route::post('/phone/verify', [VerificationController::class, 'verify']);

         Route::get('/email/verify', function () {
                                        return response()->json([
                                            'message' => 'Your email address is not verified.'
                                        ], 403);
                                    })->name('verification.notice');

     // إعادة إرسال رابط التحقق
         Route::post('/email/verification-notification', function (Request $request) {
                                        if ($request->user()->hasVerifiedEmail()) {
                                            return response()->json(['message' => 'Already verified']);
                                        }

                                        $request->user()->sendEmailVerificationNotification();

                                        return response()->json(['message' => 'Verification link sent!']);
                                    })->name('verification.send');

           // مسار لوصف مخطط بناء (يستقبل نصًا وصورة)
        Route::post('/gemini/describe-blueprint', [GeminiController::class, 'describeBlueprint']);
        // مسار لوصف مخطط بناء (يستقبل نصًا pdf)
        Route::post('/gemini/describe-pdf', [GeminiController::class, 'describePdfBlueprint']);
        // مسار للاستعلام عن مواد البناء (يستقبل نصًا فقط)
        Route::post('/gemini/ask-question', [GeminiController::class, 'askQuestion']);

        
        Route::apiResource('orders', OrderController::class);
        Route::get('user/orders',[OrderController::class, 'userOrders']);
    
         Route::post('/logout', [AuthController::class, 'logout']);
         Route::get('/user', [AuthController::class, 'user']);

        Route::patch('stores/{id}', [StoreController::class, 'update']);

             Route::middleware([AdminMiddleware::class])->prefix('admin')->group(function () {
              Route::apiResource('categories', CategoryController::class);
              Route::apiResource('products', ProductController::class);
             });

    

});



Route::get('/email/verify/{id}/{hash}', function (Request $request, $id, $hash) {
    $user = User::find($id);

    if (! $user) {
        return response()->json(['message' => 'User not found.'], 404);
    }

    // تحقق من صحة الـ hash
    if (! hash_equals((string) $hash, sha1($user->getEmailForVerification()))) {
        return response()->json(['message' => 'Invalid verification link.'], 403);
    }

    if ($user->hasVerifiedEmail()) {
        return response()->json(['message' => 'Email already verified.'], 200);
    }

    $user->markEmailAsVerified();

    return response()->json(['message' => 'Email verified successfully.'], 200);
})->name('verification.verify');


// Password reset routes
Route::post('/forgot-password', [ForgotPasswordController::class, 'sendResetLinkEmail']);
Route::post('/reset-password', [ResetPasswordController::class, 'reset']);


    // Webhook route
    Route::post('/webhook',[WebhookController::class, 'handle']);