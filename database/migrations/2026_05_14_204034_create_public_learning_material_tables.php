<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('public_learning_materials', function (Blueprint $table) {
            $table->id();

            $table->enum('type', ['trial', 'workshop'])->default('trial');

            $table->string('title');
            $table->string('slug')->unique('plm_slug_unique');
            $table->string('public_token', 100)->unique('plm_token_unique');

            $table->string('subtitle')->nullable();
            $table->longText('description')->nullable();

            $table->string('instructor_name')->nullable();
            $table->string('location')->nullable();

            $table->date('event_date')->nullable();
            $table->dateTime('starts_at')->nullable();
            $table->dateTime('ends_at')->nullable();

            $table->dateTime('access_starts_at')->nullable();
            $table->dateTime('access_ends_at')->nullable();

            $table->string('cover_image_path')->nullable();

            $table->enum('status', ['draft', 'published', 'archived'])->default('draft');

            $table->unsignedBigInteger('created_by')->nullable();
            $table->unsignedBigInteger('updated_by')->nullable();

            $table->timestamps();
            $table->softDeletes();

            $table->foreign('created_by', 'plm_created_by_fk')
                ->references('id')
                ->on('users')
                ->nullOnDelete();

            $table->foreign('updated_by', 'plm_updated_by_fk')
                ->references('id')
                ->on('users')
                ->nullOnDelete();

            $table->index(['type', 'status'], 'plm_type_status_idx');
            $table->index('event_date', 'plm_event_date_idx');
            $table->index(['access_starts_at', 'access_ends_at'], 'plm_access_window_idx');
        });

        Schema::create('public_learning_material_blocks', function (Blueprint $table) {
            $table->id();

            $table->unsignedBigInteger('public_learning_material_id');

            $table->enum('type', ['heading', 'text', 'code', 'image', 'note', 'task'])->default('text');

            $table->string('title')->nullable();
            $table->longText('content')->nullable();

            $table->string('code_language')->nullable();
            $table->longText('code_content')->nullable();

            $table->string('image_path')->nullable();
            $table->string('image_caption')->nullable();

            $table->unsignedInteger('sort_order')->default(1);
            $table->boolean('is_active')->default(true);

            $table->timestamps();

            $table->foreign('public_learning_material_id', 'plm_blocks_material_fk')
                ->references('id')
                ->on('public_learning_materials')
                ->cascadeOnDelete();

            $table->index(
                ['public_learning_material_id', 'sort_order'],
                'plm_blocks_material_order_idx'
            );

            $table->index(['type', 'is_active'], 'plm_blocks_type_active_idx');
        });

        Schema::create('public_learning_material_images', function (Blueprint $table) {
            $table->id();

            $table->unsignedBigInteger('public_learning_material_id');

            $table->string('image_path');
            $table->string('caption')->nullable();

            $table->unsignedInteger('sort_order')->default(1);

            $table->timestamps();

            $table->foreign('public_learning_material_id', 'plm_images_material_fk')
                ->references('id')
                ->on('public_learning_materials')
                ->cascadeOnDelete();

            $table->index(
                ['public_learning_material_id', 'sort_order'],
                'plm_images_material_order_idx'
            );
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('public_learning_material_images');
        Schema::dropIfExists('public_learning_material_blocks');
        Schema::dropIfExists('public_learning_materials');
    }
};