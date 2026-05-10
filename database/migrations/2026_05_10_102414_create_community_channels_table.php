<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('community_channels', function (Blueprint $table) {
            $table->id();

            $table->foreignId('community_group_id')
                ->constrained('community_groups')
                ->cascadeOnDelete();

            $table->string('name');
            $table->string('slug');

            $table->enum('type', [
                'announcement',
                'discussion',
                'coding_help',
                'project',
                'career',
                'general',
            ])->default('discussion');

            $table->text('description')->nullable();

            $table->boolean('is_readonly')->default(false);
            $table->boolean('is_pinned')->default(false);
            $table->boolean('is_active')->default(true);

            $table->unsignedInteger('sort_order')->default(0);

            $table->timestamps();

            $table->unique(['community_group_id', 'slug']);
            $table->index(['community_group_id', 'type']);
            $table->index(['is_active', 'sort_order']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('community_channels');
    }
};