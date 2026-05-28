<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('sub_topics', function (Blueprint $table) {
            if (! Schema::hasColumn('sub_topics', 'video_provider')) {
                $table->string('video_provider', 30)
                    ->nullable()
                    ->after('lesson_type');
            }

            if (! Schema::hasColumn('sub_topics', 'video_disk')) {
                $table->string('video_disk', 50)
                    ->nullable()
                    ->after('video_provider');
            }

            if (! Schema::hasColumn('sub_topics', 'video_path')) {
                $table->text('video_path')
                    ->nullable()
                    ->after('video_disk');
            }

            if (! Schema::hasColumn('sub_topics', 'video_mime')) {
                $table->string('video_mime', 100)
                    ->nullable()
                    ->after('video_path');
            }

            if (! Schema::hasColumn('sub_topics', 'video_size')) {
                $table->unsignedBigInteger('video_size')
                    ->nullable()
                    ->after('video_mime');
            }

            if (! Schema::hasColumn('sub_topics', 'video_duration_seconds')) {
                $table->unsignedInteger('video_duration_seconds')
                    ->nullable()
                    ->after('video_duration_minutes');
            }
        });
    }

    public function down(): void
    {
        Schema::table('sub_topics', function (Blueprint $table) {
            $columns = [
                'video_provider',
                'video_disk',
                'video_path',
                'video_mime',
                'video_size',
                'video_duration_seconds',
            ];

            foreach ($columns as $column) {
                if (Schema::hasColumn('sub_topics', $column)) {
                    $table->dropColumn($column);
                }
            }
        });
    }
};