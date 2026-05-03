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
        Schema::create('student_learning_notes', function (Blueprint $table) {
            $table->id();

            /*
            |--------------------------------------------------------------------------
            | Owner
            |--------------------------------------------------------------------------
            | student_id wajib diisi dari authenticated student di backend,
            | bukan dari payload frontend.
            */
            $table->unsignedBigInteger('student_id')->index();

            /*
            |--------------------------------------------------------------------------
            | Course / Program Snapshot
            |--------------------------------------------------------------------------
            | Di frontend kita pakai istilah course. Di backend FlexOps bisa saja
            | sumbernya dari programs/batches. Jadi course_id disimpan sebagai
            | reference ID fleksibel dulu.
            */
            $table->unsignedBigInteger('course_id')->nullable()->index();
            $table->string('course_slug')->nullable()->index();
            $table->string('course_title')->nullable();

            /*
            |--------------------------------------------------------------------------
            | Module Snapshot
            |--------------------------------------------------------------------------
            */
            $table->unsignedBigInteger('module_id')->nullable()->index();
            $table->string('module_title')->nullable();

            /*
            |--------------------------------------------------------------------------
            | Topic Snapshot
            |--------------------------------------------------------------------------
            | Ini penting untuk filter notes berdasarkan topik.
            */
            $table->unsignedBigInteger('topic_id')->nullable()->index();
            $table->string('topic_slug')->nullable()->index();
            $table->string('topic_title')->nullable();

            /*
            |--------------------------------------------------------------------------
            | Sub Topic / Lesson Snapshot
            |--------------------------------------------------------------------------
            | Di UI disebut lesson, tapi di DB kita banyak pakai sub_topics.
            | Dua-duanya disimpan supaya fleksibel.
            */
            $table->unsignedBigInteger('sub_topic_id')->nullable()->index();
            $table->string('sub_topic_slug')->nullable()->index();
            $table->string('sub_topic_title')->nullable();

            $table->unsignedBigInteger('lesson_id')->nullable()->index();
            $table->string('lesson_slug')->nullable()->index();
            $table->string('lesson_title')->nullable();

            /*
            |--------------------------------------------------------------------------
            | Note Content
            |--------------------------------------------------------------------------
            */
            $table->string('title');
            $table->longText('content');
            $table->json('tags')->nullable();

            /*
            |--------------------------------------------------------------------------
            | Video Context
            |--------------------------------------------------------------------------
            | Supaya nanti notes bisa balik ke timestamp video tertentu.
            */
            $table->unsignedInteger('video_timestamp_seconds')->default(0);

            /*
            |--------------------------------------------------------------------------
            | Status
            |--------------------------------------------------------------------------
            | active = visible, archived = disembunyikan kalau nanti butuh archive.
            */
            $table->string('status', 30)->default('active')->index();

            $table->timestamps();
            $table->softDeletes();

            /*
            |--------------------------------------------------------------------------
            | Composite Indexes
            |--------------------------------------------------------------------------
            | Buat query notes by student + topic/subtopic/course lebih kenceng.
            */
            $table->index(['student_id', 'course_id']);
            $table->index(['student_id', 'course_slug']);

            $table->index(['student_id', 'topic_id']);
            $table->index(['student_id', 'topic_slug']);

            $table->index(['student_id', 'sub_topic_id']);
            $table->index(['student_id', 'sub_topic_slug']);

            $table->index(['student_id', 'lesson_id']);
            $table->index(['student_id', 'lesson_slug']);

            $table->index(['student_id', 'status']);
            $table->index(['student_id', 'created_at']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('student_learning_notes');
    }
};