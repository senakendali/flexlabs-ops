<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('topics', function (Blueprint $table) {
            $table->text('slide_url')->nullable()->after('description');
            $table->text('starter_code_url')->nullable()->after('slide_url');
            $table->text('supporting_file_url')->nullable()->after('starter_code_url');
            $table->text('external_reference_url')->nullable()->after('supporting_file_url');
            $table->longText('practice_brief')->nullable()->after('external_reference_url');
        });

        Schema::table('sub_topics', function (Blueprint $table) {
            $table->string('lesson_type', 30)->default('video')->after('description');
            $table->text('video_url')->nullable()->after('lesson_type');
            $table->unsignedSmallInteger('video_duration_minutes')->nullable()->after('video_url');
            $table->text('thumbnail_url')->nullable()->after('video_duration_minutes');
        });
    }

    public function down(): void
    {
        Schema::table('topics', function (Blueprint $table) {
            $table->dropColumn([
                'slide_url',
                'starter_code_url',
                'supporting_file_url',
                'external_reference_url',
                'practice_brief',
            ]);
        });

        Schema::table('sub_topics', function (Blueprint $table) {
            $table->dropColumn([
                'lesson_type',
                'video_url',
                'video_duration_minutes',
                'thumbnail_url',
            ]);
        });
    }
};