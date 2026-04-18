<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('marketing_report_ads', function (Blueprint $table) {
            $table->id();

            $table->foreignId('marketing_report_id')
                ->constrained('marketing_reports')
                ->cascadeOnDelete();

            $table->foreignId('marketing_report_campaign_id')
                ->nullable()
                ->constrained('marketing_report_campaigns')
                ->nullOnDelete();

            $table->string('platform', 100);
            $table->string('ad_name');
            $table->string('objective')->nullable();

            $table->date('start_date')->nullable();
            $table->date('end_date')->nullable();

            $table->decimal('budget', 15, 2)->default(0);
            $table->decimal('actual_spend', 15, 2)->default(0);

            $table->enum('status', ['active', 'paused', 'review', 'done'])
                ->default('active');

            $table->text('notes')->nullable();
            $table->unsignedInteger('sort_order')->default(0);

            $table->timestamps();

            $table->index('marketing_report_id');
            $table->index('marketing_report_campaign_id');
            $table->index('platform');
            $table->index('status');
            $table->index(['marketing_report_id', 'sort_order']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('marketing_report_ads');
    }
};