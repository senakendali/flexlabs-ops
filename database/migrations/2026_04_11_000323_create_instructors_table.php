<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('instructors', function (Blueprint $table) {
            $table->id();

            $table->string('name');
            $table->string('slug')->unique();

            $table->string('email')->nullable()->unique();
            $table->string('phone', 30)->nullable();

            $table->string('specialization')->nullable();

            $table->enum('employment_type', ['full_time', 'part_time'])
                ->default('part_time');

            $table->text('bio')->nullable();
            $table->string('photo')->nullable();

            $table->boolean('is_active')->default(true);

            $table->timestamps();

            // Index tambahan biar query lebih cepat
            $table->index('name');
            $table->index('is_active');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('instructors');
    }
};