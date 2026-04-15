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
        Schema::create('leads', function (Blueprint $table) {
            $table->id();

            $table->string('name');
            $table->string('email')->nullable();
            $table->string('phone')->nullable();

            $table->string('source')->nullable(); // IG, TikTok, Referral

            $table->enum('status', [
                'new',
                'contacted',
                'ignored',
                'warm',
                'hot',
                'closed_lost',
                'not_relevant'
            ])->default('new');

            $table->text('notes')->nullable();

            // assign ke sales
            $table->foreignId('assigned_to')->nullable()->constrained('users')->nullOnDelete();

            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('leads');
    }
};
