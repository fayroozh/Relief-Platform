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


// ✅ محمية بالتوكن
Route::middleware('auth:sanctum')->group(function () {
    Route::post('/logout', [AuthenticatedSessionController::class, 'destroy']);
});


use App\Http\Controllers\OrganizationController;

Route::get('/organizations', [OrganizationController::class, 'index']);
Route::get('/organizations/{id}', [OrganizationController::class, 'show']);

Route::middleware('auth:sanctum')->group(function () {
    Route::post('/organizations', [OrganizationController::class, 'store']);
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

    // ✅ تبرعات
    Route::get('/donations', [DonationController::class, 'index']); // كل التبرعات
    Route::post('/donations', [DonationController::class, 'store']); // تبرع جديد
    Route::get('/my-donations', [DonationController::class, 'myDonations']); // تبرعات المستخدم الحالي
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
