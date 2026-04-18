<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('marketing_reports', function (Blueprint $table) {
            $table->id();

            // Basic info
            $table->string('title');
            $table->string('slug')->unique();

            // Reporting period
            $table->enum('period_type', ['weekly', 'monthly']);
            $table->date('start_date')->nullable();
            $table->date('end_date')->nullable();

            // Core management metrics
            $table->unsignedInteger('total_leads')->default(0);
            $table->unsignedInteger('qualified_leads')->default(0);
            $table->unsignedInteger('total_conversions')->default(0);

            $table->decimal('total_revenue', 15, 2)->default(0);
            $table->decimal('budget', 15, 2)->default(0);
            $table->decimal('actual_spend', 15, 2)->default(0);

            // Narrative fields for management reading
            $table->text('summary')->nullable();
            $table->text('key_insight')->nullable();
            $table->text('next_action')->nullable();
            $table->text('notes')->nullable();

            // Status
            $table->enum('status', ['draft', 'published', 'archived'])->default('draft');
            $table->boolean('is_active')->default(true);

            // Audit
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('updated_by')->nullable()->constrained('users')->nullOnDelete();

            $table->timestamps();

            $table->index(['period_type', 'start_date', 'end_date']);
            $table->index('status');
            $table->index('is_active');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('marketing_reports');
    }
};