<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('trial_participants', function (Blueprint $table) {
            $table->id();

            $table->foreignId('trial_schedule_id')
                ->constrained('trial_schedules')
                ->cascadeOnUpdate()
                ->restrictOnDelete();

            $table->foreignId('trial_theme_id')
                ->nullable()
                ->constrained('trial_themes')
                ->cascadeOnUpdate()
                ->nullOnDelete();

            $table->string('full_name');
            $table->string('email')->nullable();
            $table->string('phone', 30)->nullable();
            $table->string('domicile_city')->nullable();
            $table->string('current_activity')->nullable(); // pekerjaan / status saat ini
            $table->text('goal')->nullable(); // tujuan ikut trial

            $table->enum('input_source', ['admin', 'self_registration'])->default('admin');
            $table->enum('status', [
                'registered',
                'contacted',
                'confirmed',
                'attended',
                'cancelled',
                'no_show',
            ])->default('registered');

            $table->text('notes')->nullable();

            $table->timestamps();

            $table->index('trial_schedule_id');
            $table->index('trial_theme_id');
            $table->index('status');
            $table->index('input_source');
            $table->index('full_name');
            $table->index('email');
            $table->index('phone');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('trial_participants');
    }
};