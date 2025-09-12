<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Auth\RegisteredUserController;
use App\Http\Controllers\Auth\AuthenticatedSessionController;

// مسار تجريبي للاختبار
Route::get('/ping', function () {
    return response()->json([
        'status' => 'API works fine',
        'message' => 'Ping successful'
    ]);
});

// ✅ تسجيل مستخدم جديد + إرسال OTP
Route::post('/register', [RegisteredUserController::class, 'store']);

// ✅ التحقق من OTP بعد التسجيل
Route::post('/verify-otp', [RegisteredUserController::class, 'verifyOtp']);

// ✅ تسجيل دخول عادي (إيميل + باسورد)
Route::post('/login', [AuthenticatedSessionController::class, 'store']);

// ✅ تسجيل دخول باستخدام رقم الهاتف (OTP عبر WhatsApp)
Route::post('/login-phone', [AuthenticatedSessionController::class, 'loginWithPhone']);

// ✅ محمية بالتوكن
Route::middleware('auth:sanctum')->group(function () {
    Route::post('/logout', [AuthenticatedSessionController::class, 'destroy']);
});


Route::post('/register-phone', [RegisteredUserController::class, 'registerPhone']);
Route::post('/verify-phone', [RegisteredUserController::class, 'verifyPhoneOtp']);

