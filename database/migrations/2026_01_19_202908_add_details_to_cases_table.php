<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('cases', function (Blueprint $table) {
            // Make organization_id nullable
            $table->unsignedBigInteger('organization_id')->nullable()->change();
            
            // Add new fields
            $table->string('image_path')->nullable()->after('status');
            $table->string('user_name')->nullable()->after('image_path');
            $table->string('user_phone')->nullable()->after('user_name');
            $table->string('location')->nullable()->after('user_phone');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('cases', function (Blueprint $table) {
            // Revert changes
            $table->unsignedBigInteger('organization_id')->nullable(false)->change();
            
            $table->dropColumn(['image_path', 'user_name', 'user_phone', 'location']);
        });
    }
};
