<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('users', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('email')->unique()->nullable();
            $table->string('phone')->unique()->nullable(); // للـ SMS أو شام كاش مستقبلاً
            $table->timestamp('email_verified_at')->nullable();
            $table->timestamp('phone_verified_at')->nullable();
            $table->string('password');

            // أنواع المستخدمين
            $table->enum('user_type', ['user','donor','beneficiary','organization','admin'])->default('user');
            $table->enum('status', ['pending','active','rejected','blocked'])->default('active'); // الجمعيات تبدأ pending

            // الحقول الخاصة بالمصادقة الثنائية البسيطة (OTP)
            $table->string('otp_code')->nullable();
            $table->timestamp('otp_expires_at')->nullable();

            // لو حبيتي تحتفظي بخيارات Fortify لاحقاً (ممكن نستغني عنها حالياً)
            $table->text('two_factor_secret')->nullable();
            $table->text('two_factor_recovery_codes')->nullable();
            $table->timestamp('two_factor_confirmed_at')->nullable();

            $table->rememberToken();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('users');
    }
};
