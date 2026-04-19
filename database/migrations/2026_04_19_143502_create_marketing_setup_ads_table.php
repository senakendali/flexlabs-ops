<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('marketing_setup_ads', function (Blueprint $table) {
            $table->id();

            $table->unsignedBigInteger('marketing_setup_campaign_id')->nullable();

            $table->string('platform');
            $table->string('ad_name');
            $table->string('slug')->unique()->nullable();

            $table->text('objective')->nullable();

            $table->date('start_date');
            $table->date('end_date');

            $table->decimal('total_budget', 15, 2)->default(0);

            $table->string('status')->default('planned');
            $table->text('notes')->nullable();

            $table->boolean('is_active')->default(true);

            $table->unsignedBigInteger('created_by')->nullable();
            $table->unsignedBigInteger('updated_by')->nullable();

            $table->timestamps();

            $table->index(['start_date', 'end_date']);
            $table->index(['platform', 'status']);
            $table->index(['marketing_setup_campaign_id', 'is_active']);
            $table->index('created_by');
            $table->index('updated_by');

            $table->foreign('marketing_setup_campaign_id')
                ->references('id')
                ->on('marketing_setup_campaigns')
                ->nullOnDelete();

            $table->foreign('created_by')
                ->references('id')
                ->on('users')
                ->nullOnDelete();

            $table->foreign('updated_by')
                ->references('id')
                ->on('users')
                ->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('marketing_setup_ads');
    }
};