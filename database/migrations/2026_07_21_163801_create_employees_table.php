<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('employees', function (Blueprint $table) {
            $table->id();

            /*
            |--------------------------------------------------------------------------
            | Employee Identity
            |--------------------------------------------------------------------------
            | Disimpan sebagai string karena employee number dapat memiliki leading
            | zero atau format non-numerik pada sumber lain di masa depan.
            */
            $table->string('employee_number', 100)
                ->nullable()
                ->unique();

            $table->string('name');

            /*
            | Dipakai untuk fallback matching berdasarkan nama apabila row seperti
            | Sick Leave tidak membawa employee number.
            */
            $table->string('normalized_name')
                ->index();

            $table->string('email')
                ->nullable()
                ->index();

            $table->string('phone', 50)
                ->nullable();

            /*
            |--------------------------------------------------------------------------
            | Employment Information
            |--------------------------------------------------------------------------
            | Data ini dapat diisi otomatis dari file Evertime jika tersedia.
            */
            $table->string('employee_type')
                ->nullable()
                ->index();

            $table->string('work_team')
                ->nullable()
                ->index();

            $table->string('duty_type')
                ->nullable()
                ->index();

            /*
            | Digunakan sebagai konfigurasi awal untuk mendeteksi keterlambatan
            | atau tanggal kerja yang hilang.
            */
            $table->time('default_start_time')
                ->nullable();

            $table->time('default_end_time')
                ->nullable();

            /*
            | Contoh:
            | - attendance_import
            | - manual
            | - api
            */
            $table->string('source')
                ->default('attendance_import')
                ->index();

            $table->boolean('is_active')
                ->default(true)
                ->index();

            $table->timestamp('first_seen_at')
                ->nullable();

            $table->timestamp('last_seen_at')
                ->nullable();

            /*
            | Tempat menyimpan data tambahan dari Evertime yang belum perlu
            | dijadikan kolom permanen.
            */
            $table->json('metadata')
                ->nullable();

            $table->timestamps();
            $table->softDeletes();

            $table->index([
                'is_active',
                'work_team',
            ]);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('employees');
    }
};