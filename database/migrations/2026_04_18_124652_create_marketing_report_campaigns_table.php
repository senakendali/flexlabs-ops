<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('marketing_report_campaigns', function (Blueprint $table) {
            $table->id();
            $table->foreignId('marketing_report_id')
                ->constrained('marketing_reports')
                ->cascadeOnDelete();

            $table->string('name');
            $table->text('objective')->nullable();

            $table->date('start_date')->nullable();
            $table->date('end_date')->nullable();

            $table->decimal('budget', 15, 2)->default(0);
            $table->decimal('actual_spend', 15, 2)->default(0);

            $table->string('owner_name')->nullable();

            $table->enum('status', ['planned', 'on_progress', 'review', 'done'])
                ->default('planned');

            $table->text('notes')->nullable();
            $table->unsignedInteger('sort_order')->default(0);

            $table->timestamps();

            $table->index('marketing_report_id');
            $table->index('status');
            $table->index(['marketing_report_id', 'sort_order']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('marketing_report_campaigns');
    }
};