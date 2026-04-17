<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('marketing_events', function (Blueprint $table) {
            $table->id();
            $table->foreignId('marketing_campaign_id')->nullable()->constrained('marketing_campaigns')->nullOnDelete();

            $table->string('name');
            $table->string('slug')->unique();

            $table->string('event_type')->nullable(); // workshop, webinar, offline_event, campus_visit
            $table->date('event_date')->nullable();
            $table->string('location')->nullable();

            $table->string('target_audience')->nullable();

            $table->unsignedInteger('target_participants')->default(0);
            $table->unsignedInteger('registrants')->default(0);
            $table->unsignedInteger('attendees')->default(0);
            $table->unsignedInteger('leads_generated')->default(0);
            $table->unsignedInteger('conversions')->default(0);

            $table->decimal('budget', 15, 2)->default(0);

            $table->string('status')->default('planned'); // planned, ongoing, done, cancelled
            $table->text('description')->nullable();
            $table->text('notes')->nullable();

            $table->boolean('is_active')->default(true);

            $table->foreignId('pic_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('updated_by')->nullable()->constrained('users')->nullOnDelete();

            $table->timestamps();

            $table->index(['event_date', 'status']);
            $table->index(['event_type']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('marketing_events');
    }
};