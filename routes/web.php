<?php

use App\Http\Controllers\Auth\AuthController;
use App\Http\Controllers\ConnectController;
use App\Http\Controllers\WebhookController;
use Illuminate\Foundation\Auth\EmailVerificationRequest;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('welcome');
});




    Route::post('/register', [AuthController::class, 'register']);
    Route::post('/login', [AuthController::class, 'login']);

    
    Route::get('/connect/return', [ConnectController::class, 'return']);







