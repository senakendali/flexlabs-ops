<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('modules', function (Blueprint $table) {
            $table->foreignId('program_stage_id')->nullable()->after('id');
        });

        if (Schema::hasColumn('modules', 'program_id')) {
            Schema::table('modules', function (Blueprint $table) {
                $table->dropForeign(['program_id']);
                $table->dropColumn('program_id');
            });
        }

        Schema::table('modules', function (Blueprint $table) {
            $table->foreign('program_stage_id')
                ->references('id')
                ->on('program_stages')
                ->cascadeOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('modules', function (Blueprint $table) {
            $table->dropForeign(['program_stage_id']);
            $table->dropColumn('program_stage_id');
        });

        Schema::table('modules', function (Blueprint $table) {
            $table->foreignId('program_id')->nullable();
        });
    }
};