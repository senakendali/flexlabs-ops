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
        Schema::create('kpi_targets', function (Blueprint $table) {
            $table->id();

            $table->foreignId('kpi_definition_id')
                ->constrained('kpi_definitions')
                ->restrictOnDelete();

            /*
             * period_month wajib disimpan sebagai tanggal pertama setiap bulan.
             * Contoh target Agustus 2026 disimpan sebagai 2026-08-01.
             */
            $table->date('period_month');

            /*
             * scope_type:
             * company, division, program, atau batch.
             *
             * scope_identifier:
             * company              => "company"
             * division             => "sales", "marketing", dan seterusnya
             * program atau batch   => ID record dalam bentuk string
             *
             * scope_label menyimpan label tampilan agar riwayat tetap mudah
             * dibaca, tetapi bukan sumber relasi utama.
             */
            $table->string('scope_type', 30)->default('company');
            $table->string('scope_identifier', 100)->default('company');
            $table->string('scope_label')->nullable();

            $table->decimal('target_value', 20, 4);

            $table->foreignId('owner_user_id')
                ->nullable()
                ->constrained('users')
                ->nullOnDelete();

            /*
             * status:
             * draft, active, atau locked.
             */
            $table->string('status', 20)->default('draft')->index();
            $table->text('notes')->nullable();

            /*
             * Diisi ketika target dibuat melalui fitur copy bulan sebelumnya.
             */
            $table->foreignId('source_target_id')
                ->nullable()
                ->constrained('kpi_targets')
                ->nullOnDelete();

            $table->timestamp('activated_at')->nullable();
            $table->timestamp('locked_at')->nullable();

            $table->foreignId('locked_by')
                ->nullable()
                ->constrained('users')
                ->nullOnDelete();

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

            /*
             * Menjamin satu KPI hanya memiliki satu target untuk kombinasi
             * bulan dan scope yang sama. Target soft-deleted harus direstore
             * jika kombinasi yang sama ingin digunakan kembali.
             */
            $table->unique(
                [
                    'kpi_definition_id',
                    'period_month',
                    'scope_type',
                    'scope_identifier',
                ],
                'kpi_targets_period_scope_unique'
            );

            $table->index(
                ['period_month', 'status'],
                'kpi_targets_period_status_index'
            );

            $table->index(
                ['scope_type', 'scope_identifier', 'period_month'],
                'kpi_targets_scope_period_index'
            );
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('kpi_targets');
    }
};
