<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            // Nilai existing tidak berubah; kolom hanya dibuat nullable agar
            // group registration dapat menggunakan satu order untuk banyak student.
            $table->unsignedBigInteger('student_id')->nullable()->change();

            $table->foreignId('group_registration_id')
                ->nullable()
                ->after('student_id')
                ->constrained('group_registrations')
                ->restrictOnDelete();

            $table->unique('group_registration_id', 'orders_group_registration_unique');
        });

        Schema::table('payment_schedules', function (Blueprint $table) {
            $table->decimal('gross_amount', 12, 2)->nullable()->after('amount');
            $table->decimal('wht_rate', 5, 2)->default(0)->after('gross_amount');
            $table->decimal('wht_amount', 12, 2)->default(0)->after('wht_rate');
            $table->decimal('net_amount', 12, 2)->nullable()->after('wht_amount');
        });

        // Backfill non-destruktif: schedule lama dianggap tanpa WHT.
        DB::table('payment_schedules')
            ->whereNull('gross_amount')
            ->update([
                'gross_amount' => DB::raw('amount'),
                'net_amount' => DB::raw('amount'),
            ]);

        DB::statement(
            "ALTER TABLE student_enrollments
             MODIFY enrollment_source
             ENUM('manual','payment','import','group_registration') NOT NULL"
        );
    }

    public function down(): void
    {
        // Jangan melakukan rollback destruktif jika group registration sudah dipakai.
        if (DB::table('orders')->whereNull('student_id')->exists()) {
            throw new \RuntimeException(
                'Rollback dibatalkan: terdapat order group registration dengan student_id null.'
            );
        }

        if (DB::table('student_enrollments')
            ->where('enrollment_source', 'group_registration')
            ->exists()) {
            throw new \RuntimeException(
                'Rollback dibatalkan: terdapat enrollment dari group registration.'
            );
        }

        DB::statement(
            "ALTER TABLE student_enrollments
             MODIFY enrollment_source
             ENUM('manual','payment','import') NOT NULL"
        );

        Schema::table('payment_schedules', function (Blueprint $table) {
            $table->dropColumn([
                'gross_amount',
                'wht_rate',
                'wht_amount',
                'net_amount',
            ]);
        });

        Schema::table('orders', function (Blueprint $table) {
            $table->dropUnique('orders_group_registration_unique');
            $table->dropConstrainedForeignId('group_registration_id');
            $table->unsignedBigInteger('student_id')->nullable(false)->change();
        });
    }
};