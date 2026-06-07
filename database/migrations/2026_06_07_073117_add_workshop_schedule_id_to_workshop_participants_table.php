<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('workshop_participants', function (Blueprint $table) {
            $table->foreignId('workshop_schedule_id')
                ->nullable()
                ->after('workshop_id')
                ->constrained('workshop_schedules')
                ->nullOnDelete();

            $table->index(['workshop_id', 'workshop_schedule_id']);
        });
    }

    public function down(): void
    {
        Schema::table('workshop_participants', function (Blueprint $table) {
            $table->dropIndex(['workshop_id', 'workshop_schedule_id']);
            $table->dropConstrainedForeignId('workshop_schedule_id');
        });
    }
};