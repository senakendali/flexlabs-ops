<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('google_ads_dashboard_snapshots', function (Blueprint $table) {
            $table->dropUnique(
                'google_ads_snapshots_customer_preset_unique'
            );

            $table->unique(
                ['customer_id', 'date_preset', 'date_start'],
                'google_ads_snapshots_customer_preset_start_unique'
            );
        });
    }

    public function down(): void
    {
        Schema::table('google_ads_dashboard_snapshots', function (Blueprint $table) {
            $table->dropUnique(
                'google_ads_snapshots_customer_preset_start_unique'
            );

            $table->unique(
                ['customer_id', 'date_preset'],
                'google_ads_snapshots_customer_preset_unique'
            );
        });
    }
};