<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            $table->string('order_type')
                ->default('program')
                ->after('student_id');

            $table->foreignId('workshop_id')
                ->nullable()
                ->after('batch_id')
                ->constrained('workshops')
                ->nullOnDelete();

            $table->unsignedBigInteger('batch_id')
                ->nullable()
                ->change();
        });
    }

    public function down(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            $table->dropForeign(['workshop_id']);
            $table->dropColumn(['order_type', 'workshop_id']);

            $table->unsignedBigInteger('batch_id')
                ->nullable(false)
                ->change();
        });
    }
};