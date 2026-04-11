<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('trial_schedules', function (Blueprint $table) {
            $table->id();

            $table->foreignId('program_id')
                ->constrained('programs')
                ->cascadeOnUpdate()
                ->cascadeOnDelete();

            $table->foreignId('trial_theme_id')
                ->nullable()
                ->constrained('trial_themes')
                ->nullOnDelete()
                ->cascadeOnUpdate();

            $table->string('name');
            $table->string('day_name', 20);
            $table->time('start_time');
            $table->time('end_time')->nullable();
            $table->unsignedInteger('quota')->nullable();
            $table->text('description')->nullable();
            $table->boolean('is_active')->default(true);

            $table->timestamps();

            $table->index(['program_id', 'is_active']);
            $table->index(['trial_theme_id', 'is_active']);
            $table->index(['day_name', 'start_time']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('trial_schedules');
    }
};