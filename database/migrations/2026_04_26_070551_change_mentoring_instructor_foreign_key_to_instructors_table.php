<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        /*
        |--------------------------------------------------------------------------
        | Drop old FK: instructor_id -> users.id
        |--------------------------------------------------------------------------
        */
        Schema::table('student_mentoring_sessions', function (Blueprint $table) {
            $table->dropForeign(['instructor_id']);
        });

        Schema::table('instructor_availability_slots', function (Blueprint $table) {
            $table->dropForeign(['instructor_id']);
        });

        /*
        |--------------------------------------------------------------------------
        | Add new FK: instructor_id -> instructors.id
        |--------------------------------------------------------------------------
        */
        Schema::table('instructor_availability_slots', function (Blueprint $table) {
            $table->foreign('instructor_id')
                ->references('id')
                ->on('instructors')
                ->cascadeOnDelete();
        });

        Schema::table('student_mentoring_sessions', function (Blueprint $table) {
            $table->foreign('instructor_id')
                ->references('id')
                ->on('instructors')
                ->cascadeOnDelete();
        });
    }

    public function down(): void
    {
        /*
        |--------------------------------------------------------------------------
        | Revert FK back to users.id
        |--------------------------------------------------------------------------
        */
        Schema::table('student_mentoring_sessions', function (Blueprint $table) {
            $table->dropForeign(['instructor_id']);
        });

        Schema::table('instructor_availability_slots', function (Blueprint $table) {
            $table->dropForeign(['instructor_id']);
        });

        Schema::table('instructor_availability_slots', function (Blueprint $table) {
            $table->foreign('instructor_id')
                ->references('id')
                ->on('users')
                ->cascadeOnDelete();
        });

        Schema::table('student_mentoring_sessions', function (Blueprint $table) {
            $table->foreign('instructor_id')
                ->references('id')
                ->on('users')
                ->cascadeOnDelete();
        });
    }
};