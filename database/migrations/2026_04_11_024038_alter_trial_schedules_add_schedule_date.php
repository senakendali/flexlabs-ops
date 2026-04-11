<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('trial_schedules', function (Blueprint $table) {
            $table->date('schedule_date')->nullable()->after('name');
        });

        Schema::table('trial_schedules', function (Blueprint $table) {
            $table->dropIndex(['day_name', 'start_time']);
            $table->dropColumn('day_name');
            $table->index(['schedule_date', 'start_time']);
        });
    }

    public function down(): void
    {
        Schema::table('trial_schedules', function (Blueprint $table) {
            // Balikin day_name kalau rollback
            $table->string('day_name', 20)->after('name');

            // Drop index baru
            $table->dropIndex(['schedule_date', 'start_time']);

            // Hapus schedule_date
            $table->dropColumn('schedule_date');

            // Balikin index lama
            $table->index(['day_name', 'start_time']);
        });
    }
};