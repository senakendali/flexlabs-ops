<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('academic_schedules', function (Blueprint $table) {
            $table->dropForeign(['instructor_id']);
        });

        // Existing values previously stored users.id. Convert them to the
        // matching instructors.id before installing the new foreign key.
        DB::statement('UPDATE academic_schedules AS schedules
            LEFT JOIN instructors ON instructors.user_id = schedules.instructor_id
            SET schedules.instructor_id = instructors.id
            WHERE schedules.instructor_id IS NOT NULL');

        Schema::table('academic_schedules', function (Blueprint $table) {
            $table->foreign('instructor_id')
                ->references('id')
                ->on('instructors')
                ->nullOnDelete()
                ->cascadeOnUpdate();
        });
    }

    public function down(): void
    {
        Schema::table('academic_schedules', function (Blueprint $table) {
            $table->dropForeign(['instructor_id']);
        });

        DB::statement('UPDATE academic_schedules AS schedules
            LEFT JOIN instructors ON instructors.id = schedules.instructor_id
            SET schedules.instructor_id = instructors.user_id
            WHERE schedules.instructor_id IS NOT NULL');

        Schema::table('academic_schedules', function (Blueprint $table) {
            $table->foreign('instructor_id')
                ->references('id')
                ->on('users')
                ->nullOnDelete()
                ->cascadeOnUpdate();
        });
    }
};
