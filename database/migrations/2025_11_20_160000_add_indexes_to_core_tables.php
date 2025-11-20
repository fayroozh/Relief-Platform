<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void {
        Schema::table('payments', function (Blueprint $table) {
            $table->index(['status']);
            $table->index(['method']);
            $table->index(['user_id']);
            $table->index(['organization_id']);
            $table->index(['project_id']);
            $table->index(['case_id']);
            $table->index(['created_at']);
        });

        Schema::table('notifications', function (Blueprint $table) {
            $table->index(['user_id', 'is_read']);
            $table->index(['created_at']);
        });

        Schema::table('wallet_transactions', function (Blueprint $table) {
            $table->index(['wallet_id']);
            $table->index(['payment_id']);
            $table->index(['type']);
            $table->index(['created_at']);
        });

        Schema::table('point_transactions', function (Blueprint $table) {
            $table->index(['user_id']);
            $table->index(['payment_id']);
            $table->index(['type']);
            $table->index(['created_at']);
        });
    }

    public function down(): void {
        Schema::table('payments', function (Blueprint $table) {
            $table->dropIndex(['payments_status_index']);
            $table->dropIndex(['payments_method_index']);
            $table->dropIndex(['payments_user_id_index']);
            $table->dropIndex(['payments_organization_id_index']);
            $table->dropIndex(['payments_project_id_index']);
            $table->dropIndex(['payments_case_id_index']);
            $table->dropIndex(['payments_created_at_index']);
        });

        Schema::table('notifications', function (Blueprint $table) {
            $table->dropIndex(['notifications_user_id_is_read_index']);
            $table->dropIndex(['notifications_created_at_index']);
        });

        Schema::table('wallet_transactions', function (Blueprint $table) {
            $table->dropIndex(['wallet_transactions_wallet_id_index']);
            $table->dropIndex(['wallet_transactions_payment_id_index']);
            $table->dropIndex(['wallet_transactions_type_index']);
            $table->dropIndex(['wallet_transactions_created_at_index']);
        });

        Schema::table('point_transactions', function (Blueprint $table) {
            $table->dropIndex(['point_transactions_user_id_index']);
            $table->dropIndex(['point_transactions_payment_id_index']);
            $table->dropIndex(['point_transactions_type_index']);
            $table->dropIndex(['point_transactions_created_at_index']);
        });
    }
};