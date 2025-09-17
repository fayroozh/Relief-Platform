<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    /**
     * Run the migrations.
     */
    public function up()
    {
        Schema::create('cases', function (Blueprint $table) {
            $table->id();
            $table->foreignId('organization_id')->constrained()->onDelete('cascade'); // ربط بالجمعية
            $table->string('title');
            $table->text('description');
            $table->foreignId('category_id')->constrained()->onDelete('cascade'); // ربط بالتصنيفات
            $table->decimal('goal_amount', 12, 2);
            $table->decimal('collected_amount', 12, 2)->default(0);
            $table->enum('status', ['pending', 'approved', 'rejected', 'completed'])->default('pending');
            $table->timestamps();
        });
    }


    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('cases');
    }
};
