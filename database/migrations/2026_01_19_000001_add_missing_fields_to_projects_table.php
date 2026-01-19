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
        Schema::table('projects', function (Blueprint $table) {
            // خيارات التبرع
            $table->json('suggested_amounts')->nullable()->after('goal_amount');
            $table->json('payment_channels')->nullable()->after('suggested_amounts');
            $table->text('thank_you_message')->nullable()->after('payment_channels');
            $table->boolean('allow_custom_amount')->default(true)->after('thank_you_message');
            
            // خيارات التكرار
            $table->boolean('enable_repetition')->default(false)->after('allow_custom_amount');
            $table->string('repetition_type')->nullable()->after('enable_repetition'); // weekly, monthly
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('projects', function (Blueprint $table) {
            $table->dropColumn([
                'suggested_amounts',
                'payment_channels',
                'thank_you_message',
                'allow_custom_amount',
                'enable_repetition',
                'repetition_type'
            ]);
        });
    }
};
