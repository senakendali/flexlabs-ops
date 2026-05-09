<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('sub_topics', function (Blueprint $table) {
            $table->longText('content')
                ->nullable()
                ->after('description');

            $table->string('content_format', 30)
                ->default('markdown')
                ->after('content');
        });
    }

    public function down(): void
    {
        Schema::table('sub_topics', function (Blueprint $table) {
            $table->dropColumn([
                'content',
                'content_format',
            ]);
        });
    }
};