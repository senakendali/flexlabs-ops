<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('equipments', function (Blueprint $table) {
            $table->enum('type', ['assigned', 'borrowable'])
                ->default('borrowable')
                ->after('status');

            $table->foreignId('assigned_user_id')
                ->nullable()
                ->after('type')
                ->constrained('users')
                ->nullOnDelete();

            $table->string('location')
                ->nullable()
                ->after('assigned_user_id');

            $table->date('purchase_date')
                ->nullable()
                ->after('location');

            $table->decimal('purchase_price', 15, 2)
                ->nullable()
                ->after('purchase_date');

            $table->timestamp('last_maintenance_at')
                ->nullable()
                ->after('purchase_price');
        });
    }

    public function down(): void
    {
        Schema::table('equipments', function (Blueprint $table) {
            $table->dropConstrainedForeignId('assigned_user_id');
            $table->dropColumn([
                'type',
                'location',
                'purchase_date',
                'purchase_price',
                'last_maintenance_at',
            ]);
        });
    }
};