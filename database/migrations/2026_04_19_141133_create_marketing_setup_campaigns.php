<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('marketing_setup_campaigns', function (Blueprint $table) {
            $table->id();

            $table->string('name');
            $table->string('slug')->unique();
            $table->text('objective')->nullable();

            $table->date('start_date');
            $table->date('end_date');

            $table->decimal('total_budget', 15, 2)->default(0);

            $table->string('owner_name')->nullable();
            $table->unsignedBigInteger('pic_user_id')->nullable();

            $table->string('status')->default('planned');
            $table->text('notes')->nullable();

            $table->boolean('is_active')->default(true);

            $table->unsignedBigInteger('created_by')->nullable();
            $table->unsignedBigInteger('updated_by')->nullable();

            $table->timestamps();

            $table->index(['start_date', 'end_date']);
            $table->index(['status', 'is_active']);
            $table->index('pic_user_id');
            $table->index('created_by');
            $table->index('updated_by');

            $table->foreign('pic_user_id')
                ->references('id')
                ->on('users')
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
        Schema::dropIfExists('marketing_setup_campaigns');
    }
};