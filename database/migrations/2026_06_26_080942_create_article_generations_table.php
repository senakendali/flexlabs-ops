<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('article_generations', function (Blueprint $table) {
            $table->id();

            $table->foreignId('article_id')
                ->nullable()
                ->constrained('articles')
                ->cascadeOnDelete();

            $table->foreignId('user_id')
                ->nullable()
                ->constrained('users')
                ->nullOnDelete();

            /*
            |--------------------------------------------------------------------------
            | Generation Type
            |--------------------------------------------------------------------------
            | Possible values:
            | - outline
            | - full_article
            | - seo
            | - creative
            | - social
            | - regenerate_section
            | - workshop_article
            */
            $table->string('generation_type', 80)->index();

            /*
            |--------------------------------------------------------------------------
            | Section Target
            |--------------------------------------------------------------------------
            | Dipakai untuk regenerate section.
            | Example:
            | - title
            | - meta_description
            | - body_intro
            | - body_conclusion
            | - creative_brief
            | - instagram_caption
            */
            $table->string('section_key', 120)->nullable()->index();

            /*
            |--------------------------------------------------------------------------
            | AI Provider / Model
            |--------------------------------------------------------------------------
            */
            $table->string('provider', 50)->default('gemini');
            $table->string('model', 120)->nullable();

            /*
            |--------------------------------------------------------------------------
            | Prompt and Response
            |--------------------------------------------------------------------------
            */
            $table->json('prompt_payload')->nullable();
            $table->json('response_payload')->nullable();

            /*
            |--------------------------------------------------------------------------
            | Token / Usage Tracking
            |--------------------------------------------------------------------------
            | Beberapa provider mungkin belum selalu balikin token usage.
            | Jadi nullable.
            */
            $table->unsignedInteger('input_tokens')->nullable();
            $table->unsignedInteger('output_tokens')->nullable();
            $table->unsignedInteger('total_tokens')->nullable();

            /*
            |--------------------------------------------------------------------------
            | Runtime Tracking
            |--------------------------------------------------------------------------
            */
            $table->unsignedInteger('duration_ms')->nullable();

            /*
            |--------------------------------------------------------------------------
            | Status
            |--------------------------------------------------------------------------
            | - pending
            | - success
            | - failed
            */
            $table->string('status', 50)->default('pending')->index();

            $table->text('error_message')->nullable();

            /*
            |--------------------------------------------------------------------------
            | Extra Metadata
            |--------------------------------------------------------------------------
            | Bisa untuk safety ratings, finish reason, raw usage, dll.
            */
            $table->json('meta')->nullable();

            $table->timestamps();

            /*
            |--------------------------------------------------------------------------
            | Indexes
            |--------------------------------------------------------------------------
            */
            $table->index(['article_id', 'generation_type'], 'article_gen_article_type_idx');
            $table->index(['user_id', 'created_at'], 'article_gen_user_created_idx');
            $table->index(['provider', 'model'], 'article_gen_provider_model_idx');
            $table->index(['status', 'created_at'], 'article_gen_status_created_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('article_generations');
    }
};