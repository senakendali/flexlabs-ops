<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('meeting_minutes', function (Blueprint $table) {
            $table->id();

            $table->string('meeting_no')->unique();
            $table->string('title');

            $table->string('meeting_type')->default('internal');
            // internal, client, vendor, academic, marketing, finance, operations, other

            $table->date('meeting_date');
            $table->time('start_time')->nullable();
            $table->time('end_time')->nullable();

            $table->string('location')->nullable();
            $table->string('platform')->nullable();

            $table->string('department')->nullable();
            // operation, academic, sales, marketing, finance, etc

            $table->string('related_project')->nullable();

            $table->foreignId('organizer_id')
                ->nullable()
                ->constrained('users')
                ->nullOnDelete();

            $table->longText('summary')->nullable();
            $table->longText('notes')->nullable();

            $table->string('status')->default('draft');
            // draft, published, completed, cancelled

            $table->boolean('is_active')->default(true);

            $table->foreignId('created_by')
                ->nullable()
                ->constrained('users')
                ->nullOnDelete();

            $table->foreignId('updated_by')
                ->nullable()
                ->constrained('users')
                ->nullOnDelete();

            $table->timestamps();

            $table->index(['meeting_date', 'status']);
            $table->index(['meeting_type', 'department']);
            $table->index('organizer_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('meeting_minutes');
    }
};