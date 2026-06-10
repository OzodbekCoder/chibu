<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('shipments', function (Blueprint $table) {
            // List/dashboard: WHERE created_by_id AND status (+ latest)
            $table->index(['created_by_id', 'status'], 'shipments_owner_status_idx');
            // Reports/range stats: WHERE created_by_id AND created_at BETWEEN
            $table->index(['created_by_id', 'created_at'], 'shipments_owner_created_idx');
        });

        Schema::table('clients', function (Blueprint $table) {
            $table->index('created_by_id', 'clients_owner_idx');
        });

        Schema::table('currency_rates', function (Blueprint $table) {
            // latestYuan(): WHERE base+quote+created_by_id ORDER BY rate_date DESC
            $table->index(['created_by_id', 'base', 'quote', 'rate_date'], 'rates_owner_pair_date_idx');
        });
    }

    public function down(): void
    {
        Schema::table('shipments', function (Blueprint $table) {
            $table->dropIndex('shipments_owner_status_idx');
            $table->dropIndex('shipments_owner_created_idx');
        });
        Schema::table('clients', function (Blueprint $table) {
            $table->dropIndex('clients_owner_idx');
        });
        Schema::table('currency_rates', function (Blueprint $table) {
            $table->dropIndex('rates_owner_pair_date_idx');
        });
    }
};
