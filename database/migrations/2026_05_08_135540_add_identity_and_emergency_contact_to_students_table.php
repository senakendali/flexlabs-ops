<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('students', function (Blueprint $table) {
            $table->string('nik', 16)
                ->nullable()
                ->after('phone');

            $table->string('emergency_contact_name')
                ->nullable()
                ->after('nik');

            $table->string('emergency_contact_phone', 30)
                ->nullable()
                ->after('emergency_contact_name');

            $table->string('emergency_contact_relation', 100)
                ->nullable()
                ->after('emergency_contact_phone');
        });
    }

    public function down(): void
    {
        Schema::table('students', function (Blueprint $table) {
            $table->dropColumn([
                'nik',
                'emergency_contact_name',
                'emergency_contact_phone',
                'emergency_contact_relation',
            ]);
        });
    }
};