<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('donations', function (Blueprint $table) {
            $table->id();

            $table->foreignId('case_id')->constrained('cases')->onDelete('cascade');
            $table->foreignId('user_id')->nullable()->constrained('users')->onDelete('set null');

            $table->decimal('amount', 12, 2);
            $table->string('method'); // cash, shamm, stripe, paypal...
            $table->enum('status', ['pending', 'completed', 'failed'])->default('pending');

            $table->string('note')->nullable();
            $table->string('receipt_path')->nullable(); // صورة إيصال (اختياري)

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('donations');
    }
};
