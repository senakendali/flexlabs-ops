<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('students', function (Blueprint $table) {
            if (!Schema::hasColumn('students', 'avatar_url')) {
                $table->string('avatar_url')->nullable()->after('phone');
            }

            if (!Schema::hasColumn('students', 'bio')) {
                $table->text('bio')->nullable()->after('goal');
            }
        });
    }

    public function down(): void
    {
        Schema::table('students', function (Blueprint $table) {
            if (Schema::hasColumn('students', 'avatar_url')) {
                $table->dropColumn('avatar_url');
            }

            if (Schema::hasColumn('students', 'bio')) {
                $table->dropColumn('bio');
            }
        });
    }
};