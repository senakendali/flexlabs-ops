<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('ai_agents', function (Blueprint $table) {
            $table->string('division', 50)
                ->after('id')
                ->index();

            $table->string('code', 50)
                ->after('division')
                ->unique();

            $table->string('name', 100)
                ->after('code');
        });
    }

    public function down(): void
    {
        Schema::table('ai_agents', function (Blueprint $table) {
            $table->dropUnique(['code']);
            $table->dropIndex(['division']);

            $table->dropColumn([
                'division',
                'code',
                'name',
            ]);
        });
    }
};