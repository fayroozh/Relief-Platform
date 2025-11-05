<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Auth\RegisteredUserController;
use App\Http\Controllers\Auth\AuthenticatedSessionController;
use App\Http\Controllers\Auth\OrganizationRegistrationController;
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


// ✅ محمية بالتوكن
Route::middleware('auth:sanctum')->group(function () {
    Route::post('/logout', [AuthenticatedSessionController::class, 'destroy']);
});


use App\Http\Controllers\OrganizationController;


// تسجيل جمعية جديدة
Route::post('/organizations', [OrganizationController::class, 'store']);

// صلاحيات الأدمن فقط
Route::middleware(['auth:sanctum', 'can:isAdmin'])->group(function () {
    Route::post('/organizations/{id}/approve', [OrganizationController::class, 'approve']);
    Route::post('/organizations/{id}/reject', [OrganizationController::class, 'reject']);
});


Route::get('/organizations', [OrganizationController::class, 'index']);
Route::get('/organizations/{id}', [OrganizationController::class, 'show']);

Route::middleware('auth:sanctum')->group(function () {
    Route::post('/organizations/{id}/approve', [OrganizationController::class, 'approve']);
    Route::post('/organizations/{id}/reject', [OrganizationController::class, 'reject']);
    Route::put('/organizations/{id}', [OrganizationController::class, 'update']);
    Route::delete('/organizations/{id}', [OrganizationController::class, 'destroy']);
});

use App\Http\Controllers\ProjectController;
use App\Http\Controllers\DonationController;

Route::middleware('auth:sanctum')->group(function () {
    // ✅ مشاريع
    Route::get('/projects', [ProjectController::class, 'index']);
    Route::post('/projects', [ProjectController::class, 'store']);
    Route::get('/projects/{project}', [ProjectController::class, 'show']);
    Route::put('/projects/{project}', [ProjectController::class, 'update']);
    Route::delete('/projects/{project}', [ProjectController::class, 'destroy']);

});




Route::middleware('auth:sanctum')->group(function () {
    Route::get('/donations', [DonationController::class, 'index']);
    Route::get('/donations/{id}', [DonationController::class, 'show']);
    Route::post('/donations', [DonationController::class, 'store']);
    Route::post('/donations/{id}/receipt', [DonationController::class, 'uploadReceipt']);
    Route::post('/donations/{id}/confirm', [DonationController::class, 'confirm']);
});




use App\Http\Controllers\CaseController;

Route::get('/cases', [CaseController::class, 'index']);
Route::post('/cases', [CaseController::class, 'store']);

// مسار للأدمن لتغيير الحالة
Route::patch('/cases/{id}/status', [CaseController::class, 'updateStatus'])
    ->middleware('auth:sanctum'); // حماية


use App\Http\Controllers\CategoryController;

Route::middleware('auth:sanctum')->group(function () {
    Route::apiResource('categories', CategoryController::class);
});
use App\Http\Middleware\EnsureUserIsAdmin;
use App\Http\Controllers\Admin\StatisticsController;

Route::middleware(['auth:sanctum', EnsureUserIsAdmin::class])
    ->prefix('admin')
    ->group(function () {
        Route::get('/statistics', [StatisticsController::class, 'index']);
    });
use App\Http\Controllers\Auth\ForgotPasswordController;

Route::post('/password/request-reset', [ForgotPasswordController::class, 'requestReset']);
Route::post('/password/verify-code', [ForgotPasswordController::class, 'verifyResetCode']);
Route::post('/password/reset', [ForgotPasswordController::class, 'resetPassword']);

use App\Http\Controllers\PaymentController;

Route::middleware('auth:sanctum')->group(function () {
    Route::post('payments/donate', [PaymentController::class, 'donate']);
    Route::get('wallet/balance', [PaymentController::class, 'walletBalance']);
});

// admin confirm (protected by sanctum + admin check inside controller)
Route::post('payments/{id}/confirm', [PaymentController::class, 'confirmPayment']);

use App\Http\Controllers\ProfileController;

Route::middleware('auth:sanctum')->group(function () {
    Route::get('/profile', [ProfileController::class, 'show']);
    Route::post('/profile/update', [ProfileController::class, 'update']);
    Route::post('/profile/update-password', [ProfileController::class, 'updatePassword']);
});
use App\Http\Controllers\MessageController;

Route::middleware('auth:sanctum')->group(function () {
    Route::get('/messages/inbox', [MessageController::class, 'inbox']);
    Route::get('/messages/sent', [MessageController::class, 'sent']);
    Route::post('/messages', [MessageController::class, 'store']);
    Route::patch('/messages/{id}/read', [MessageController::class, 'markAsRead']);
});

// فقط الأدمن
Route::middleware(['auth:sanctum', 'can:isAdmin'])->post('/messages/broadcast', [MessageController::class, 'broadcast']);

use App\Http\Controllers\NotificationController;

Route::middleware('auth:sanctum')->group(function () {
    Route::get('/notifications', [NotificationController::class, 'index']);
    Route::get('/notifications/unread', [NotificationController::class, 'unread']);
    Route::post('/notifications/{id}/read', [NotificationController::class, 'markAsRead']);
    Route::delete('/notifications/{id}', [NotificationController::class, 'destroy']);
});

Route::middleware(['auth:sanctum', 'can:isAdmin'])->group(function () {
    Route::post('/notifications/broadcast', [NotificationController::class, 'broadcast']);
});


