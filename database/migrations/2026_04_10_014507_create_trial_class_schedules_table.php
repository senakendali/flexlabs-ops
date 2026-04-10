<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('trial_class_schedules', function (Blueprint $table) {
            $table->id();
            $table->foreignId('trial_class_id')->constrained('trial_classes')->cascadeOnDelete();

            $table->string('title')->nullable();
            $table->date('trial_date')->nullable();
            $table->time('trial_time')->nullable();

            $table->enum('day_name', [
                'monday',
                'tuesday',
                'wednesday',
                'thursday',
                'friday',
                'saturday',
                'sunday'
            ])->nullable();

            $table->string('mode')->default('online'); // online / offline / hybrid
            $table->string('location')->nullable();
            $table->unsignedInteger('quota')->nullable();

            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('trial_class_schedules');
    }
};
