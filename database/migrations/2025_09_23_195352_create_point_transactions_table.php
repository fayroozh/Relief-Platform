<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void {
        Schema::create('point_transactions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('users')->onDelete('cascade');
            $table->integer('points'); // positive for earn, negative for spend
            $table->enum('type', ['earned','spent','adjustment']);
            $table->string('source')->nullable(); // e.g. 'donation', 'referral', 'daily_login'
            $table->foreignId('payment_id')->nullable()->constrained('payments')->onDelete('set null');
            $table->json('meta')->nullable();
            $table->timestamps();
        });

        // ensure users table has points_balance column (migration below or modify existing)
    }

    public function down(): void {
        Schema::dropIfExists('point_transactions');
    }
};
