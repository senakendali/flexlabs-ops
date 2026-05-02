<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('instructor_session_trackings', function (Blueprint $table) {
            $table->id();

            $table->foreignId('instructor_schedule_id')
                ->nullable()
                ->constrained('instructor_schedules')
                ->nullOnDelete();

            $table->foreignId('instructor_id')
                ->nullable()
                ->constrained('instructors')
                ->nullOnDelete();

            $table->foreignId('batch_id')
                ->nullable()
                ->constrained('batches')
                ->nullOnDelete();

            $table->foreignId('program_id')
                ->nullable()
                ->constrained('programs')
                ->nullOnDelete();

            $table->date('session_date')->nullable();
            $table->time('scheduled_start_time')->nullable();
            $table->time('scheduled_end_time')->nullable();

            $table->timestamp('checked_in_at')->nullable();
            $table->timestamp('checked_out_at')->nullable();

            $table->unsignedInteger('actual_duration_minutes')->default(0);
            $table->unsignedInteger('late_minutes')->default(0);

            $table->decimal('coverage_percentage', 5, 2)->default(0);

            $table->text('session_notes')->nullable();
            $table->text('issue_notes')->nullable();
            $table->text('follow_up_notes')->nullable();

            $table->string('status', 30)->default('pending');

            $table->timestamp('submitted_at')->nullable();

            $table->foreignId('reviewed_by')
                ->nullable()
                ->constrained('users')
                ->nullOnDelete();

            $table->timestamp('reviewed_at')->nullable();
            $table->text('review_notes')->nullable();

            $table->timestamps();
            $table->softDeletes();

            $table->index(['instructor_schedule_id', 'status'], 'ist_schedule_status_idx');
            $table->index(['instructor_id', 'session_date'], 'ist_instructor_date_idx');
            $table->index(['batch_id', 'session_date'], 'ist_batch_date_idx');
        });

        Schema::create('instructor_session_tracking_items', function (Blueprint $table) {
            $table->id();

            $table->unsignedBigInteger('instructor_session_tracking_id');
            $table->foreign('instructor_session_tracking_id', 'ist_items_tracking_fk')
                ->references('id')
                ->on('instructor_session_trackings')
                ->cascadeOnDelete();

            $table->foreignId('sub_topic_id')
                ->nullable()
                ->constrained('sub_topics')
                ->nullOnDelete();

            $table->boolean('is_delivered')->default(false);
            $table->string('delivery_status', 30)->default('not_delivered');

            $table->text('not_delivered_reason')->nullable();
            $table->text('delivery_notes')->nullable();

            $table->timestamps();

            $table->unique(
                ['instructor_session_tracking_id', 'sub_topic_id'],
                'ist_items_tracking_subtopic_unique'
            );

            $table->index('sub_topic_id', 'ist_items_subtopic_idx');
            $table->index('delivery_status', 'ist_items_status_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('instructor_session_tracking_items');
        Schema::dropIfExists('instructor_session_trackings');
    }
};