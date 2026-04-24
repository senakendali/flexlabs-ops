<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('workshops', function (Blueprint $table) {
            $table->id();
            $table->string('title');
            $table->string('slug')->unique();
            $table->string('badge')->nullable();
            $table->text('short_description')->nullable();
            $table->longText('overview')->nullable();

            $table->decimal('price', 12, 2)->default(0);
            $table->decimal('old_price', 12, 2)->nullable();

            $table->unsignedTinyInteger('rating')->default(5);
            $table->unsignedInteger('rating_count')->default(0);

            $table->string('duration')->nullable();
            $table->string('level')->nullable();
            $table->string('category')->nullable();
            $table->text('audience')->nullable();

            $table->string('image')->nullable();
            $table->enum('intro_video_type', ['youtube', 'upload'])->default('youtube');
            $table->text('intro_video_url')->nullable();

            $table->boolean('is_active')->default(true);
            $table->unsignedInteger('sort_order')->default(0);

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('workshops');
    }
};