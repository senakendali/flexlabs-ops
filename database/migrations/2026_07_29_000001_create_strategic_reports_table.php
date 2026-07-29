<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('strategic_reports', function (Blueprint $table) {
            $table->id();
            $table->string('title');
            $table->string('period_type', 20);
            $table->date('period_start');
            $table->date('period_end');
            $table->unsignedSmallInteger('revision')->default(1);
            $table->string('status', 20)->default('draft');
            $table->string('overall_business_health', 30)->nullable();
            $table->string('data_confidence', 30)->nullable();
            $table->unsignedTinyInteger('data_coverage')->nullable();
            $table->json('kpi_snapshot')->nullable();
            $table->json('centre_performance_snapshot')->nullable();
            $table->json('trend_snapshot')->nullable();
            $table->json('cross_functional_snapshot')->nullable();
            $table->text('executive_summary')->nullable();
            $table->json('wins')->nullable();
            $table->json('risks')->nullable();
            $table->json('opportunities')->nullable();
            $table->json('management_decisions')->nullable();
            $table->json('action_plan')->nullable();
            $table->json('data_freshness')->nullable();
            $table->json('source_limitations')->nullable();
            $table->json('ai_metadata')->nullable();
            $table->foreignId('generated_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('generated_at')->nullable();
            $table->foreignId('finalized_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('finalized_at')->nullable();
            $table->timestamps();
            $table->unique(['period_type', 'period_start', 'period_end', 'revision'], 'strategic_reports_period_revision_unique');
            $table->index(['status', 'period_type', 'period_start']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('strategic_reports');
    }
};
