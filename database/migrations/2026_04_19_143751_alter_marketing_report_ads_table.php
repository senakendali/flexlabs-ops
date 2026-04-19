<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('marketing_report_ads', function (Blueprint $table) {
            $table->unsignedBigInteger('marketing_setup_ad_id')
                ->nullable()
                ->after('marketing_report_id');

            $table->index('marketing_setup_ad_id', 'mr_ads_setup_ad_id_idx');

            $table->foreign('marketing_setup_ad_id', 'mr_ads_setup_ad_id_fk')
                ->references('id')
                ->on('marketing_setup_ads')
                ->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('marketing_report_ads', function (Blueprint $table) {
            $table->dropForeign('mr_ads_setup_ad_id_fk');
            $table->dropIndex('mr_ads_setup_ad_id_idx');
            $table->dropColumn('marketing_setup_ad_id');
        });
    }
};