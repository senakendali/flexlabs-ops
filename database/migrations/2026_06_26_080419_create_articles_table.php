<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('articles', function (Blueprint $table) {
            $table->id();

            /*
            |--------------------------------------------------------------------------
            | Source
            |--------------------------------------------------------------------------
            | source_type:
            | - manual
            | - workshop
            | - webinar
            | - event
            | - campaign
            |
            | source_id:
            | ID dari table sumber, contoh workshop_id.
            */
            $table->string('source_type', 50)->default('manual')->index();
            $table->unsignedBigInteger('source_id')->nullable()->index();

            /*
            |--------------------------------------------------------------------------
            | Content Brief
            |--------------------------------------------------------------------------
            */
            $table->string('article_type', 80)->nullable()->index();
            $table->string('category', 120)->nullable()->index();
            $table->string('tone', 120)->nullable();
            $table->string('target_audience', 160)->nullable();
            $table->string('language', 20)->default('id');
            $table->string('length_preset', 50)->nullable();

            $table->string('primary_keyword')->nullable();
            $table->json('secondary_keywords')->nullable();

            $table->text('main_angle')->nullable();
            $table->text('must_include')->nullable();
            $table->text('avoid_points')->nullable();
            $table->longText('brief_notes')->nullable();

            /*
            |--------------------------------------------------------------------------
            | Article Content
            |--------------------------------------------------------------------------
            */
            $table->string('title');
            $table->string('slug')->unique();
            $table->text('excerpt')->nullable();
            $table->longText('body_html')->nullable();

            /*
            |--------------------------------------------------------------------------
            | Workflow Status
            |--------------------------------------------------------------------------
            | MVP active:
            | - draft
            | - ai_generated
            | - published
            |
            | Future:
            | - edited
            | - ready_for_review
            | - approved
            | - scheduled
            | - archived
            */
            $table->string('status', 50)->default('draft')->index();

            /*
            |--------------------------------------------------------------------------
            | SEO
            |--------------------------------------------------------------------------
            */
            $table->string('seo_title')->nullable();
            $table->text('meta_description')->nullable();
            $table->json('meta_keywords')->nullable();

            $table->string('og_title')->nullable();
            $table->text('og_description')->nullable();
            $table->string('og_image_url')->nullable();

            $table->string('canonical_url')->nullable();
            $table->json('tags')->nullable();

            /*
            |--------------------------------------------------------------------------
            | Creative / Image Suggestion
            |--------------------------------------------------------------------------
            */
            $table->string('hero_image_url')->nullable();
            $table->string('hero_image_alt')->nullable();
            $table->json('creative_brief')->nullable();

            /*
            |--------------------------------------------------------------------------
            | Social Distribution
            |--------------------------------------------------------------------------
            */
            $table->json('social_captions')->nullable();

            /*
            |--------------------------------------------------------------------------
            | AI Payload Snapshot
            |--------------------------------------------------------------------------
            | ai_brief:
            | request brief final yang dipakai untuk generate.
            |
            | ai_outline:
            | outline hasil AI sebelum jadi full artikel.
            */
            $table->json('ai_brief')->nullable();
            $table->json('ai_outline')->nullable();

            /*
            |--------------------------------------------------------------------------
            | Publishing
            |--------------------------------------------------------------------------
            */
            $table->timestamp('scheduled_publish_at')->nullable()->index();
            $table->timestamp('published_at')->nullable()->index();

            $table->string('external_url')->nullable();
            $table->string('external_post_id')->nullable()->index();

            /*
            |--------------------------------------------------------------------------
            | Ownership / Approval
            |--------------------------------------------------------------------------
            */
            $table->foreignId('created_by')
                ->nullable()
                ->constrained('users')
                ->nullOnDelete();

            $table->foreignId('reviewed_by')
                ->nullable()
                ->constrained('users')
                ->nullOnDelete();

            $table->foreignId('approved_by')
                ->nullable()
                ->constrained('users')
                ->nullOnDelete();

            $table->foreignId('published_by')
                ->nullable()
                ->constrained('users')
                ->nullOnDelete();

            $table->timestamp('reviewed_at')->nullable();
            $table->timestamp('approved_at')->nullable();

            $table->timestamps();
            $table->softDeletes();

            /*
            |--------------------------------------------------------------------------
            | Indexes
            |--------------------------------------------------------------------------
            */
            $table->index(['status', 'published_at'], 'articles_status_published_idx');
            $table->index(['source_type', 'source_id'], 'articles_source_idx');
            $table->index(['category', 'status'], 'articles_category_status_idx');
            $table->index(['created_by', 'status'], 'articles_creator_status_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('articles');
    }
};