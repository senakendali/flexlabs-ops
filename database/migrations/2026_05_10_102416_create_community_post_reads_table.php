<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('community_post_reads', function (Blueprint $table) {
            $table->id();

            $table->foreignId('community_post_id')
                ->constrained('community_posts')
                ->cascadeOnDelete();

            $table->foreignId('student_id')
                ->constrained('students')
                ->cascadeOnDelete();

            $table->timestamp('read_at')->nullable();

            $table->timestamps();

            $table->unique(['community_post_id', 'student_id']);
            $table->index(['student_id', 'read_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('community_post_reads');
    }
};