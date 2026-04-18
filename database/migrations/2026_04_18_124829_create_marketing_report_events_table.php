<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('marketing_report_events', function (Blueprint $table) {
            $table->id();

            $table->foreignId('marketing_report_id')
                ->constrained('marketing_reports')
                ->cascadeOnDelete();

            $table->string('name');

            $table->enum('event_type', [
                'owned_event',
                'external_event',
                'participated_event',
                'trial_class',
                'workshop',
                'info_session',
            ]);

            $table->date('event_date')->nullable();
            $table->string('location')->nullable();

            $table->unsignedInteger('target_participants')->default(0);
            $table->decimal('budget', 15, 2)->default(0);

            $table->enum('status', [
                'planned',
                'scheduled',
                'open_registration',
                'confirmed',
                'done',
                'cancelled',
            ])->default('planned');

            $table->text('notes')->nullable();
            $table->unsignedInteger('sort_order')->default(0);

            $table->timestamps();

            $table->index('marketing_report_id');
            $table->index('event_type');
            $table->index('status');
            $table->index('event_date');
            $table->index(['marketing_report_id', 'sort_order']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('marketing_report_events');
    }
};