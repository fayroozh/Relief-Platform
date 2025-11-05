<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('projects', function (Blueprint $table) {
            $table->id();

            // 🔹 معلومات المشروع الأساسية
            $table->string('title'); // عنوان المشروع
            $table->text('description')->nullable(); // وصف المشروع

            // 🔹 مبالغ المشروع
            $table->decimal('goal_amount', 12, 2)->default(0); // المبلغ المستهدف
            $table->decimal('raised_amount', 12, 2)->default(0); // المبلغ المجموع حتى الآن

            // 🔹 وقت الانتهاء
            $table->date('deadline')->nullable();

            // 🔹 من أنشأ المشروع (إما أدمن أو جمعية)
            $table->unsignedBigInteger('created_by_id');
            $table->enum('created_by_type', ['admin', 'organization']);

            // 🔹 حالة المشروع
            $table->enum('status', ['pending', 'approved', 'rejected', 'completed'])->default('pending');

            // 🔹 مسار صورة المشروع (اختياري)
            $table->string('image_path')->nullable();

            // 🔹 ملاحظات الأدمن عند الرفض أو المراجعة
            $table->text('admin_notes')->nullable();

            $table->timestamps();

            // 🔹 فهرس لتحسين البحث
            $table->index(['created_by_id', 'created_by_type']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('projects');
    }
};
