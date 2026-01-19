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
        Schema::table('donations', function (Blueprint $table) {
            // Make case_id nullable
            $table->unsignedBigInteger('case_id')->nullable()->change();
            
            // Add project_id
            $table->foreignId('project_id')->nullable()->after('case_id')->constrained('projects')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('donations', function (Blueprint $table) {
            // Revert changes (this might fail if there are records with null case_id, but it's okay for dev)
            $table->unsignedBigInteger('case_id')->nullable(false)->change();
            $table->dropForeign(['project_id']);
            $table->dropColumn('project_id');
        });
    }
};
