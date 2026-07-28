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
        Schema::create('kpi_definitions', function (Blueprint $table) {
            $table->id();

            $table->string('code', 100)->unique();
            $table->string('name');
            $table->text('description')->nullable();

            /*
             * division:
             * company, academic, sales, marketing, finance, hr, operations.
             *
             * category:
             * financial, growth, sales, learning, people, operations.
             */
            $table->string('division', 50)->nullable()->index();
            $table->string('category', 50)->nullable()->index();

            /*
             * unit:
             * currency, number, percentage, decimal.
             *
             * direction:
             * higher = nilai yang lebih tinggi lebih baik.
             * lower  = nilai yang lebih rendah lebih baik.
             */
            $table->string('unit', 30)->default('number');
            $table->string('direction', 20)->default('higher');
            $table->string('frequency', 30)->default('monthly');

            /*
             * calculation_type:
             * automatic = aktual dihitung dari data FlexOps.
             * manual    = aktual akan diinput dan divalidasi secara manual.
             *
             * calculation_key adalah identifier resolver di service, bukan SQL
             * yang dapat ditulis oleh user.
             */
            $table->string('calculation_type', 20)->default('automatic');
            $table->string('data_source_key', 100)->nullable();
            $table->string('calculation_key', 100)->nullable()->unique();
            $table->json('calculation_settings')->nullable();

            $table->unsignedInteger('sort_order')->default(0);
            $table->boolean('is_active')->default(true)->index();

            $table->foreignId('created_by')
                ->nullable()
                ->constrained('users')
                ->nullOnDelete();

            $table->foreignId('updated_by')
                ->nullable()
                ->constrained('users')
                ->nullOnDelete();

            $table->timestamps();
            $table->softDeletes();

            $table->index(
                ['division', 'is_active', 'sort_order'],
                'kpi_definitions_listing_index'
            );
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('kpi_definitions');
    }
};
