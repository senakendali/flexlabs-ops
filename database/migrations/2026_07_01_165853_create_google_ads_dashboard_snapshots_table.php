<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('google_ads_dashboard_snapshots', function (Blueprint $table) {
            $table->id();

            $table->string('customer_id')->index();
            $table->string('login_customer_id')->nullable()->index();
            $table->string('date_preset')->default('last_7d')->index();

            $table->date('date_start')->nullable();
            $table->date('date_stop')->nullable();

            $table->boolean('is_available')->default(false)->index();

            $table->unsignedInteger('campaign_count')->default(0);
            $table->unsignedInteger('enabled_campaign_count')->default(0);
            $table->unsignedInteger('paused_campaign_count')->default(0);

            $table->decimal('total_cost', 18, 2)->default(0);
            $table->unsignedBigInteger('total_impressions')->default(0);
            $table->unsignedBigInteger('total_clicks')->default(0);
            $table->decimal('ctr', 8, 2)->default(0);
            $table->decimal('average_cpc', 18, 2)->default(0);
            $table->decimal('total_conversions', 18, 2)->default(0);
            $table->decimal('total_conversion_value', 18, 2)->default(0);
            $table->decimal('cost_per_conversion', 18, 2)->nullable();
            $table->decimal('conversion_rate', 8, 2)->default(0);
            $table->decimal('roas', 10, 2)->default(0);

            $table->unsignedInteger('critical_count')->default(0);
            $table->unsignedInteger('attention_count')->default(0);
            $table->unsignedInteger('healthy_count')->default(0);

            $table->longText('summary_text')->nullable();

            $table->longText('ai_summary_text')->nullable();
            $table->json('ai_payload')->nullable();
            $table->string('ai_model')->nullable();
            $table->timestamp('ai_generated_at')->nullable();

            $table->json('payload')->nullable();
            $table->longText('error_message')->nullable();

            $table->timestamp('synced_at')->nullable()->index();
            $table->timestamps();

            $table->unique(
                ['customer_id', 'date_preset'],
                'google_ads_snapshots_customer_preset_unique'
            );
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('google_ads_dashboard_snapshots');
    }
};