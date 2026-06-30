<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('meta_ads_ai_reports', function (Blueprint $table) {
            $table->id();

            $table->string('report_type')->default('campaign'); // overview / campaign
            $table->string('campaign_id')->nullable()->index();
            $table->string('campaign_name')->nullable();

            $table->date('date_start')->nullable();
            $table->date('date_stop')->nullable();

            $table->string('provider')->default('gemini');
            $table->string('model')->nullable();

            $table->json('input_snapshot')->nullable();
            $table->json('output')->nullable();

            $table->text('summary_text')->nullable();
            $table->string('health_status')->nullable();
            $table->string('main_bottleneck')->nullable();

            $table->timestamp('generated_at')->nullable();
            $table->timestamps();

            $table->unique(
                ['report_type', 'campaign_id', 'date_start', 'date_stop'],
                'meta_ads_ai_report_unique'
            );
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('meta_ads_ai_reports');
    }
};