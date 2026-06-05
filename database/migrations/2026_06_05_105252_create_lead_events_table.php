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
        Schema::create('lead_events', function (Blueprint $table) {
            $table->id();

            /*
            |--------------------------------------------------------------------------
            | Basic Event Information
            |--------------------------------------------------------------------------
            */
            $table->string('title');
            $table->string('slug')->unique();

            // hosted, attended, collaboration, community, campaign, etc.
            $table->string('event_type')->nullable();

            // online, offline, hybrid
            $table->string('event_mode')->nullable();

            $table->string('location')->nullable();
            $table->string('event_url')->nullable();

            /*
            |--------------------------------------------------------------------------
            | Content
            |--------------------------------------------------------------------------
            */
            $table->string('image')->nullable();
            $table->text('short_description')->nullable();
            $table->longText('description')->nullable();

            /*
            |--------------------------------------------------------------------------
            | Schedule
            |--------------------------------------------------------------------------
            */
            $table->date('start_date')->nullable();
            $table->date('end_date')->nullable();
            $table->time('start_time')->nullable();
            $table->time('end_time')->nullable();

            /*
            |--------------------------------------------------------------------------
            | CTA / Registration
            |--------------------------------------------------------------------------
            */
            $table->string('cta_label')->nullable();
            $table->string('external_registration_url')->nullable();

            /*
            |--------------------------------------------------------------------------
            | Display Setting
            |--------------------------------------------------------------------------
            */
            $table->boolean('is_active')->default(true);
            $table->boolean('is_featured')->default(false);
            $table->unsignedInteger('sort_order')->default(0);

            /*
            |--------------------------------------------------------------------------
            | Extra Data
            |--------------------------------------------------------------------------
            */
            $table->json('metadata')->nullable();

            $table->timestamps();
            $table->softDeletes();

            $table->index(['is_active', 'is_featured']);
            $table->index(['start_date', 'end_date']);
            $table->index('sort_order');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('lead_events');
    }
};