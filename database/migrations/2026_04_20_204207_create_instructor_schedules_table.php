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

            $table->string('session_title');
            $table->date('schedule_date');
            $table->time('start_time');
            $table->time('end_time');

            $table->enum('delivery_mode', ['online', 'offline', 'hybrid'])->default('online');
            $table->string('meeting_link')->nullable();
            $table->string('location')->nullable();

            $table->enum('status', ['scheduled', 'completed', 'cancelled', 'rescheduled'])->default('scheduled');
            $table->text('notes')->nullable();

            $table->timestamps();

            $table->index(['schedule_date', 'start_time']);
            $table->index(['instructor_id', 'schedule_date']);
            $table->index(['batch_id', 'schedule_date']);
            $table->index(['status']);
            $table->index(['delivery_mode']);
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