<?php
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Auth\RegisteredUserController;
use App\Http\Controllers\Auth\AuthenticatedSessionController;
use App\Http\Controllers\Auth\TwoFactorAuthController;

// مسار تجريبي للاختبار
Route::get('/ping', function () {
    return response()->json([
        'status' => 'API works fine',
        'message' => 'Ping successful'
    ]);
});

Route::post('/register', [RegisteredUserController::class, 'store']);
Route::post('/login', [AuthenticatedSessionController::class, 'store']);


Route::post('/login-phone', [AuthenticatedSessionController::class, 'loginWithPhone']);
Route::post('/verify-phone-otp', [AuthenticatedSessionController::class, 'verifyPhoneOtp']);

// ✅ عدلنا هون
Route::post('/verify-otp', [AuthenticatedSessionController::class, 'verifyOtp']);


Route::middleware('auth:sanctum')->group(function () {
    Route::post('/logout', [AuthenticatedSessionController::class, 'destroy']);
});
