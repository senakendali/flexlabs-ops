<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::statement("
            ALTER TABLE equipments
            MODIFY COLUMN type
            ENUM('borrowable', 'assigned', 'fixed')
            NOT NULL DEFAULT 'borrowable'
        ");
    }

    public function down(): void
    {
        // Supaya rollback tidak gagal jika sudah ada equipment bertipe fixed.
        DB::table('equipments')
            ->where('type', 'fixed')
            ->update([
                'type' => 'borrowable',
            ]);

        DB::statement("
            ALTER TABLE equipments
            MODIFY COLUMN type
            ENUM('borrowable', 'assigned')
            NOT NULL DEFAULT 'borrowable'
        ");
    }
};