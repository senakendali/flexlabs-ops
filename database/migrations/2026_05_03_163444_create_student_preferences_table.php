<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('student_preferences', function (Blueprint $table) {
            $table->id();
            $table->foreignId('student_id')->constrained('students')->cascadeOnDelete();
            $table->string('preference_key', 100);
            $table->boolean('enabled')->default(true);
            $table->timestamps();

            $table->unique(['student_id', 'preference_key']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('student_preferences');
    }
};