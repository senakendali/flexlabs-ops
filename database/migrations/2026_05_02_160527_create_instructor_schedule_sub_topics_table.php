<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('instructor_schedule_sub_topics', function (Blueprint $table) {
            $table->id();

            $table->foreignId('instructor_schedule_id')
                ->constrained('instructor_schedules')
                ->cascadeOnDelete();

            $table->foreignId('sub_topic_id')
                ->constrained('sub_topics')
                ->cascadeOnDelete();

            $table->unsignedInteger('sort_order')->default(0);

            $table->timestamps();

            $table->unique(
                ['instructor_schedule_id', 'sub_topic_id'],
                'schedule_subtopic_unique'
            );

            $table->index('instructor_schedule_id', 'schedule_subtopic_schedule_idx');
            $table->index('sub_topic_id', 'schedule_subtopic_sub_topic_idx');
            $table->index('sort_order', 'schedule_subtopic_sort_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('instructor_schedule_sub_topics');
    }
};