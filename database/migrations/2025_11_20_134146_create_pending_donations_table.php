<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('pending_donations', function (Blueprint $table) {
            $table->id();

            $table->unsignedBigInteger('user_id');

            // ممكن يتبرع لحالة أو مشروع، واحد منهم فقط
            $table->unsignedBigInteger('case_id')->nullable();
            $table->unsignedBigInteger('project_id')->nullable();

            $table->decimal('amount', 12, 2);
            $table->string('payer_name');
            $table->string('phone');

            $table->text('note')->nullable();

            // pending - approved - rejected
            $table->enum('status', ['pending', 'approved', 'rejected'])->default('pending');

            $table->timestamps();
        });

    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('pending_donations');
    }
};
