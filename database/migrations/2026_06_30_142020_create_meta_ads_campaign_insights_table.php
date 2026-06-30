<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('meta_ads_campaign_insights', function (Blueprint $table) {
            $table->id();

            $table->string('ad_account_id')->nullable();

            $table->string('campaign_id')->index();
            $table->string('campaign_name')->nullable();

            $table->date('date_start')->nullable();
            $table->date('date_stop')->nullable();

            $table->decimal('spend', 15, 2)->default(0);
            $table->unsignedBigInteger('reach')->default(0);
            $table->unsignedBigInteger('impressions')->default(0);
            $table->decimal('frequency', 10, 4)->default(0);

            $table->unsignedBigInteger('clicks')->default(0);
            $table->unsignedBigInteger('inline_link_clicks')->default(0);

            $table->decimal('ctr', 10, 6)->default(0);
            $table->decimal('cpc', 15, 6)->default(0);
            $table->decimal('cpm', 15, 6)->default(0);

            $table->unsignedBigInteger('engagement')->default(0);
            $table->unsignedBigInteger('link_click')->default(0);
            $table->unsignedBigInteger('lead_form_submission')->default(0);
            $table->unsignedBigInteger('whatsapp_chat')->default(0);

            $table->decimal('cost_per_lead', 15, 2)->nullable();
            $table->decimal('cost_per_whatsapp_chat', 15, 2)->nullable();

            $table->json('actions')->nullable();
            $table->json('cost_per_action_type')->nullable();
            $table->json('raw_payload')->nullable();

            $table->timestamps();

            $table->unique(
                ['campaign_id', 'date_start', 'date_stop'],
                'meta_campaign_period_unique'
            );
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('meta_ads_campaign_insights');
    }
};