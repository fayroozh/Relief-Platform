<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void {
        Schema::create('payments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->nullable()->constrained('users')->onDelete('set null'); // donor
            $table->foreignId('organization_id')->nullable()->constrained('organizations')->onDelete('set null');
            $table->foreignId('project_id')->nullable()->constrained('projects')->onDelete('set null'); // optional
            $table->foreignId('case_id')->nullable()->constrained('cases')->onDelete('set null'); // optional
            $table->decimal('amount', 14, 2);
            $table->string('currency', 10)->default('TRY');
            $table->enum('method', ['wallet','shamcash','cash','card','other'])->default('shamcash');
            $table->enum('status', ['pending','completed','failed','cancelled'])->default('pending');
            $table->decimal('platform_fee', 14, 2)->default(0); // 3%
            $table->decimal('points_share', 14, 2)->default(0); // 1%
            $table->decimal('system_share', 14, 2)->default(0); // 2%
            $table->decimal('net_amount', 14, 2)->default(0); // amount - platform_fee
            $table->json('meta')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void {
        Schema::dropIfExists('payments');
    }
};
