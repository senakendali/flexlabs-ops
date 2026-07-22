<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('company_holidays', function (Blueprint $table) {
            $table->id();

            $table->date('holiday_date')
                ->unique();

            $table->string('name');

            /*
            | public_holiday
            | company_holiday
            | collective_leave
            */
            $table->string('holiday_type')
                ->default('public_holiday')
                ->index();

            $table->boolean('is_active')
                ->default(true)
                ->index();

            $table->text('notes')
                ->nullable();

            $table->json('metadata')
                ->nullable();

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('company_holidays');
    }
};