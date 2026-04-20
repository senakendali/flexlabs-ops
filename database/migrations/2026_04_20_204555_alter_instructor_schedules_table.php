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
        Schema::create('instructor_schedules', function (Blueprint $table) {
            $table->id();

            $table->foreignId('instructor_id')
                ->constrained('instructors')
                ->restrictOnDelete()
                ->cascadeOnUpdate();

            $table->foreignId('replacement_instructor_id')
                ->nullable()
                ->constrained('instructors')
                ->nullOnDelete()
                ->cascadeOnUpdate();

            $table->foreignId('batch_id')
                ->constrained('batches')
                ->restrictOnDelete()
                ->cascadeOnUpdate();

            $table->foreignId('program_id')
                ->nullable()
                ->constrained('programs')
                ->nullOnDelete()
                ->cascadeOnUpdate();

            $table->foreignId('sub_topic_id')
                ->nullable()
                ->constrained('sub_topics')
                ->nullOnDelete()
                ->cascadeOnUpdate();

            $table->foreignId('rescheduled_from_id')
                ->nullable()
                ->constrained('instructor_schedules')
                ->nullOnDelete()
                ->cascadeOnUpdate();

            $table->string('session_title');
            $table->date('schedule_date');
            $table->time('start_time');
            $table->time('end_time');

            $table->enum('delivery_mode', ['online', 'offline', 'hybrid'])->default('online');
            $table->string('meeting_link')->nullable();
            $table->string('location')->nullable();

            $table->boolean('is_makeup_session')->default(false);

            $table->enum('status', ['scheduled', 'completed', 'cancelled', 'rescheduled'])->default('scheduled');
            $table->text('notes')->nullable();

            $table->timestamps();

            $table->index(['schedule_date', 'start_time'], 'idx_instr_sched_date_time');
            $table->index(['instructor_id', 'schedule_date'], 'idx_instr_sched_instructor_date');
            $table->index(['replacement_instructor_id', 'schedule_date'], 'idx_instr_sched_replace_date');
            $table->index(['batch_id', 'schedule_date'], 'idx_instr_sched_batch_date');
            $table->index('status', 'idx_instr_sched_status');
            $table->index('delivery_mode', 'idx_instr_sched_mode');
            $table->index('is_makeup_session', 'idx_instr_sched_makeup');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('instructor_schedules');
    }
};