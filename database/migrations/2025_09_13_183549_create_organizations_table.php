<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('organizations', function (Blueprint $table) {
            $table->id();
            // user who submitted the organization (nullable in case admin creates it)
            $table->foreignId('user_id')->nullable()->constrained('users')->onDelete('set null');

            $table->string('name');
            $table->string('slug')->unique();
            $table->text('description')->nullable();

            // documents: store array of file paths / metadata as JSON
            $table->json('documents')->nullable();

            // contact & meta
            $table->string('email')->nullable();
            $table->string('phone')->nullable();
            $table->string('website')->nullable();

            // status: pending => needs admin approval, approved, rejected
            $table->enum('status', ['pending','approved','rejected','suspended'])->default('pending');
            $table->text('admin_notes')->nullable();
            $table->timestamp('approved_at')->nullable();

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('organizations');
    }
};
