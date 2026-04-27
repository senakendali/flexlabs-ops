<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('meeting_minute_participants', function (Blueprint $table) {
            $table->id();

            $table->foreignId('meeting_minute_id')
                ->constrained('meeting_minutes')
                ->cascadeOnDelete();

            $table->foreignId('user_id')
                ->nullable()
                ->constrained('users')
                ->nullOnDelete();

            // Untuk peserta eksternal
            $table->string('name')->nullable();
            $table->string('email')->nullable();

            $table->string('role')->default('participant');
            // organizer, notulen, participant, guest

            $table->string('attendance_status')->default('present');
            // present, absent, late, excused

            $table->text('notes')->nullable();

            $table->timestamps();

            $table->index('meeting_minute_id');
            $table->index('user_id');
            $table->index(['role', 'attendance_status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('meeting_minute_participants');
    }
};