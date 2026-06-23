<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('sem_leads', function (Blueprint $table) {
            /*
            |--------------------------------------------------------------------------
            | Basic contact extension
            |--------------------------------------------------------------------------
            */
            $table->string('email')->nullable()->after('whatsapp_number');

            /*
            |--------------------------------------------------------------------------
            | External lead source tracking
            |--------------------------------------------------------------------------
            | Dipakai untuk data dari Meta Lead Form / Google Sheet / source lain.
            |
            | external_source contoh:
            | - meta
            | - google_sheet
            | - google_sem
            |
            | external_lead_id contoh dari Meta:
            | - l:1720936762582408
            |--------------------------------------------------------------------------
            */
            $table->string('external_source', 50)->nullable()->after('source');
            $table->string('external_lead_id')->nullable()->after('external_source');
            $table->timestamp('external_created_at')->nullable()->after('external_lead_id');

            /*
            |--------------------------------------------------------------------------
            | Meta lead form attribution
            |--------------------------------------------------------------------------
            */
            $table->string('meta_ad_id')->nullable()->after('wbraid');
            $table->string('meta_ad_name')->nullable()->after('meta_ad_id');

            $table->string('meta_adset_id')->nullable()->after('meta_ad_name');
            $table->string('meta_adset_name')->nullable()->after('meta_adset_id');

            $table->string('meta_campaign_id')->nullable()->after('meta_adset_name');
            $table->string('meta_campaign_name')->nullable()->after('meta_campaign_id');

            $table->string('meta_form_id')->nullable()->after('meta_campaign_name');
            $table->string('meta_form_name')->nullable()->after('meta_form_id');

            $table->string('meta_platform', 50)->nullable()->after('meta_form_name');
            $table->boolean('meta_is_organic')->default(false)->after('meta_platform');
            $table->string('meta_lead_status', 100)->nullable()->after('meta_is_organic');

            /*
            |--------------------------------------------------------------------------
            | Lead profile
            |--------------------------------------------------------------------------
            */
            $table->string('education_level')->nullable()->after('meta_lead_status');

            /*
            |--------------------------------------------------------------------------
            | Optional raw payload
            |--------------------------------------------------------------------------
            | Buat jaga-jaga kalau nanti ada field dari Google Sheet/Meta yang belum
            | kita mapping ke kolom khusus.
            |--------------------------------------------------------------------------
            */
            $table->json('external_payload')->nullable()->after('education_level');

            /*
            |--------------------------------------------------------------------------
            | Indexes
            |--------------------------------------------------------------------------
            */
            $table->unique(
                ['external_source', 'external_lead_id'],
                'sem_leads_external_unique'
            );

            $table->index('external_created_at', 'sem_leads_external_created_idx');
            $table->index('meta_platform', 'sem_leads_meta_platform_idx');
            $table->index('meta_campaign_id', 'sem_leads_meta_campaign_idx');
            $table->index('meta_form_id', 'sem_leads_meta_form_idx');
        });
    }

    public function down(): void
    {
        Schema::table('sem_leads', function (Blueprint $table) {
            $table->dropUnique('sem_leads_external_unique');

            $table->dropIndex('sem_leads_external_created_idx');
            $table->dropIndex('sem_leads_meta_platform_idx');
            $table->dropIndex('sem_leads_meta_campaign_idx');
            $table->dropIndex('sem_leads_meta_form_idx');

            $table->dropColumn([
                'email',

                'external_source',
                'external_lead_id',
                'external_created_at',

                'meta_ad_id',
                'meta_ad_name',
                'meta_adset_id',
                'meta_adset_name',
                'meta_campaign_id',
                'meta_campaign_name',
                'meta_form_id',
                'meta_form_name',
                'meta_platform',
                'meta_is_organic',
                'meta_lead_status',

                'education_level',
                'external_payload',
            ]);
        });
    }
};