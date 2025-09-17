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
            $table->string('title'); // عنوان المشروع
            $table->text('description')->nullable(); // وصف المشروع
            $table->decimal('goal_amount', 12, 2)->default(0); // المبلغ المستهدف
            $table->decimal('raised_amount', 12, 2)->default(0); // المبلغ المجموع
            $table->date('deadline')->nullable(); // آخر موعد للتبرع

            // لمعرفة مين أنشأ المشروع: جمعية أو إدمن
            $table->unsignedBigInteger('created_by_id');
            $table->enum('created_by_type', ['admin', 'organization']);

            $table->enum('status', ['active', 'inactive', 'completed'])->default('active');

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('projects');
    }
};
