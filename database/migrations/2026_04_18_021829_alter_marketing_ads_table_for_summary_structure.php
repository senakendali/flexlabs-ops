<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('marketing_ads', function (Blueprint $table) {
            // Tambahan field future-proof
            $table->string('source_type')->default('manual')->after('is_active');
            $table->string('external_reference')->nullable()->after('source_type');
            $table->string('utm_source')->nullable()->after('external_reference');
            $table->string('utm_campaign')->nullable()->after('utm_source');
            $table->string('utm_content')->nullable()->after('utm_campaign');

            $table->index('source_type');
        });

        Schema::table('marketing_ads', function (Blueprint $table) {
            // Hapus field yang redundant / derived
            $table->dropColumn([
                'campaign_name',
                'cost_per_click',
                'cost_per_lead',
            ]);
        });
    }

    public function down(): void
    {
        Schema::table('marketing_ads', function (Blueprint $table) {
            $table->string('campaign_name')->after('platform');
            $table->decimal('cost_per_click', 15, 2)->default(0)->after('conversions');
            $table->decimal('cost_per_lead', 15, 2)->default(0)->after('cost_per_click');

            $table->dropIndex(['source_type']);
            $table->dropColumn([
                'source_type',
                'external_reference',
                'utm_source',
                'utm_campaign',
                'utm_content',
            ]);
        });
    }
};