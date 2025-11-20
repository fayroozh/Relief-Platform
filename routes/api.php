<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Auth\RegisteredUserController;
use App\Http\Controllers\Auth\AuthenticatedSessionController;
use App\Http\Controllers\Auth\OrganizationRegistrationController;
/*
|-----------------------------------------------------------------------------
| دليل مختصر لاستخدام واجهة API (ملاحظات بالعربي لفريق React)
|-----------------------------------------------------------------------------
| - كل الروابط هنا تحت /api.
| - "محمي بالتوكن" يعني يجب تمرير Header: Authorization: Bearer <access_token>.
| - المصادقة: تسجيل جديد + OTP لمرة واحدة، دخول عادي، و2FA اختياري عبر تحدي.
| - الدفع الحالي يدوي: المستخدم ينشئ تبرع مع حالة pending؛ الأدمن يؤكده لاحقًا.
| - تدفق الدفع اليدوي الأساسي عبر روابط PendingDonationController الموجودة أدناه.
*/
// مسار تجريبي للاختبار
Route::get('/ping', function () {
    return response()->json([
        'status' => 'API works fine',
        'message' => 'Ping successful'
    ]);
});

// ✅ تسجيل مستخدم جديد + إرسال OTP (غير محمي)
// body: { name, email, password, password_confirmation, phone? }
// response: { message, user_id }
Route::post('/register', [RegisteredUserController::class, 'store']);

// ✅ التحقق من OTP بعد التسجيل (غير محمي)
// body: { email, otp_code }
// response: { message, access_token, user }
Route::post('/verify-otp', [RegisteredUserController::class, 'verifyOtp']);

// ✅ تسجيل دخول عادي (غير محمي)
// body: { email, password }
// إذا 2FA مفعلة يرجع: { two_factor: true }، وإلا يصدر التوكن مباشرة
Route::post('/login', [AuthenticatedSessionController::class, 'store']);


// ✅ تسجيل خروج (محمي بالتوكن)
Route::middleware('auth:sanctum')->group(function () {
    Route::post('/logout', [AuthenticatedSessionController::class, 'destroy']);
});


use App\Http\Controllers\OrganizationController;


// تسجيل جمعية جديدة (غير محمي)
// body: بيانات الجمعية الأساسية + مستندات اختيارية
Route::post('/organizations', [OrganizationController::class, 'store']);

// صلاحيات الأدمن فقط (محمي بالتوكن + is_admin)
// اعتماد/رفض الجمعيات
Route::middleware(['auth:sanctum', 'is_admin'])->group(function () {
    Route::post('/organizations/{id}/approve', [OrganizationController::class, 'approve']);
    Route::post('/organizations/{id}/reject', [OrganizationController::class, 'reject']);
});


Route::get('/organizations', [OrganizationController::class, 'index']);
Route::get('/organizations/{id}', [OrganizationController::class, 'show']);

// CRUD للجمعية (محمي بالتوكن: المالك أو الأدمن)
Route::middleware('auth:sanctum')->group(function () {
    Route::post('/organizations/{id}/approve', [OrganizationController::class, 'approve']);
    Route::post('/organizations/{id}/reject', [OrganizationController::class, 'reject']);
    Route::put('/organizations/{id}', [OrganizationController::class, 'update']);
    Route::delete('/organizations/{id}', [OrganizationController::class, 'destroy']);
});

use App\Http\Controllers\ProjectController;
use App\Http\Controllers\DonationController;

// مشاريع (محمي بالتوكن) — index/store/show/update/destroy
Route::middleware('auth:sanctum')->group(function () {
    // ✅ مشاريع
    Route::get('/projects', [ProjectController::class, 'index']);
    Route::post('/projects', [ProjectController::class, 'store']);
    Route::get('/projects/{project}', [ProjectController::class, 'show']);
    Route::put('/projects/{project}', [ProjectController::class, 'update']);
    Route::delete('/projects/{project}', [ProjectController::class, 'destroy']);

});




// تبرعات عامة (محمي بالتوكن)
// ملاحظة: التدفق اليدوي الأساسي موجود في PendingDonationController أدناه
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

// تغيير حالة الحالة (محمي بالتوكن)
Route::patch('/cases/{id}/status', [CaseController::class, 'updateStatus'])
    ->middleware('auth:sanctum'); // حماية


use App\Http\Controllers\CategoryController;

// التصنيفات (محمي بالتوكن)
Route::middleware('auth:sanctum')->group(function () {
    Route::apiResource('categories', CategoryController::class);
});
use App\Http\Middleware\EnsureUserIsAdmin;
use App\Http\Controllers\Admin\StatisticsController;

// إحصائيات الأدمن (محمي بالتوكن + EnsureUserIsAdmin)
Route::middleware(['auth:sanctum', EnsureUserIsAdmin::class])
    ->prefix('admin')
    ->group(function () {
        Route::get('/statistics', [StatisticsController::class, 'index']);
    });
use App\Http\Controllers\Auth\ForgotPasswordController;

// إعادة تعيين كلمة المرور عبر OTP (غير محمي)
// 1) طلب إعادة تعيين: body { email }
Route::post('/password/request-reset', [ForgotPasswordController::class, 'requestReset']);
// 2) تحقق من الكود: body { reset_token, otp_code }
Route::post('/password/verify-code', [ForgotPasswordController::class, 'verifyResetCode']);
// 3) إعادة التعيين: body { reset_token, password, password_confirmation }
Route::post('/password/reset', [ForgotPasswordController::class, 'resetPassword']);

use App\Http\Controllers\PaymentController;

// الدفع (محمي بالتوكن)
// donate: ينشئ دفع pending ليؤكده الأدمن لاحقًا
Route::middleware('auth:sanctum')->group(function () {
    Route::post('payments/donate', [PaymentController::class, 'donate']);
    Route::get('wallet/balance', [PaymentController::class, 'walletBalance']);
});

// تأكيد دفع يدوي للأدمن (محمي بالتوكن + يتحقق داخل الكنترولر)
// body: { amount }
Route::post('payments/{id}/confirm', [PaymentController::class, 'confirmPayment']);

use App\Http\Controllers\ProfileController;

// الملف الشخصي (محمي بالتوكن)
Route::middleware('auth:sanctum')->group(function () {
    Route::get('/profile', [ProfileController::class, 'show']);
    Route::post('/profile/update', [ProfileController::class, 'update']);
    Route::post('/profile/update-password', [ProfileController::class, 'updatePassword']);
});
use App\Http\Controllers\MessageController;

// الرسائل (محمي بالتوكن)
Route::middleware('auth:sanctum')->group(function () {
    Route::get('/messages/inbox', [MessageController::class, 'inbox']);
    Route::get('/messages/sent', [MessageController::class, 'sent']);
    Route::post('/messages', [MessageController::class, 'store']);
    Route::patch('/messages/{id}/read', [MessageController::class, 'markAsRead']);
});

// بث رسائل إداري (محمي بالتوكن + is_admin)
Route::middleware(['auth:sanctum', 'is_admin'])->post('/messages/broadcast', [MessageController::class, 'broadcast']);

use App\Http\Controllers\NotificationController;

// الإشعارات (محمي بالتوكن)
// unread: إرجاع غير المقروء
Route::middleware('auth:sanctum')->group(function () {
    Route::get('/notifications', [NotificationController::class, 'index']);
    Route::get('/notifications/unread', [NotificationController::class, 'unread']);
    Route::post('/notifications/{id}/read', [NotificationController::class, 'markAsRead']);
    Route::delete('/notifications/{id}', [NotificationController::class, 'destroy']);
});

// بث إشعارات إداري (محمي بالتوكن + is_admin)
Route::middleware(['auth:sanctum', 'is_admin'])->group(function () {
    Route::post('/notifications/broadcast', [NotificationController::class, 'broadcast']);
});


use App\Http\Controllers\PendingDonationController;

// التبرعات اليدوية (Pending Donations) — التدفق اليدوي الأساسي
// المستخدم ينشئ طلب تبرع قيد الانتظار؛ يظهر للأدمن ليقبله أو يرفضه لاحقًا
Route::middleware('auth:sanctum')->group(function () {
    // user
    // POST /donations/pending  body: { case_id?, organization_id?, amount, method?, meta? }
    // response: { id, status: 'pending', ... }
    Route::post('/donations/pending', [PendingDonationController::class, 'store']);
    // GET /donations/pending/my  يعرض تبرعات المستخدم المعلقة
    Route::get('/donations/pending/my', [PendingDonationController::class, 'myPending']);

    // admin only
    // قائمة انتظار للأدمن، ثم قبول/رفض الطلبات
    Route::get('/admin/pending-donations', [PendingDonationController::class, 'adminIndex']);
    Route::post('/admin/pending-donations/{id}/approve', [PendingDonationController::class, 'approve']);
    Route::post('/admin/pending-donations/{id}/reject', [PendingDonationController::class, 'reject']);
});

// Admin Routes
Route::middleware(['auth:sanctum', 'is_admin'])->prefix('admin')->name('admin.')->group(function () {
    Route::get('organizations', [\App\Http\Controllers\Admin\OrganizationController::class, 'index'])->name('organizations.index');
    Route::post('organizations/{organization}/approve', [\App\Http\Controllers\Admin\OrganizationController::class, 'approve'])->name('organizations.approve');
    Route::post('organizations/{organization}/reject', [\App\Http\Controllers\Admin\OrganizationController::class, 'reject'])->name('organizations.reject');
});

// تحدي 2FA (غير محمي)
// body: { email, code } → يصدر توكن عند نجاح التحقق
Route::post('/two-factor-challenge', [AuthenticatedSessionController::class, 'twoFactorChallenge']);
use App\Http\Controllers\Auth\TwoFactorAuthController;
// إدارة 2FA (محمي بالتوكن): تمكين/تأكيد/تعطيل/عرض أكواد الاسترداد
Route::middleware('auth:sanctum')->group(function () {
    Route::post('/two-factor/enable', [TwoFactorAuthController::class, 'store']);
    Route::post('/two-factor/confirm', [TwoFactorAuthController::class, 'confirm']);
    Route::post('/two-factor/disable', [TwoFactorAuthController::class, 'destroy']);
    Route::get('/two-factor/recovery-codes', [TwoFactorAuthController::class, 'show']);
});