<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('students', function (Blueprint $table) {
            $table->id();

            $table->string('full_name');
            $table->string('email')->nullable()->unique();
            $table->string('phone', 30)->nullable();

            $table->string('city')->nullable();
            $table->string('current_status')->nullable();
            $table->text('goal')->nullable();
            $table->string('source')->nullable();

            $table->enum('status', ['lead', 'trial', 'active', 'inactive'])
                ->default('lead');

            $table->timestamps();

            $table->index('full_name');
            $table->index('phone');
            $table->index('status');
            $table->index('source');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('students');
    }
};