<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('certificates', function (Blueprint $table) {
            $table->id();

            $table->foreignId('report_card_id')
                ->nullable()
                ->constrained('report_cards')
                ->nullOnDelete();

            $table->foreignId('student_id')
                ->constrained('students')
                ->cascadeOnDelete();

            $table->foreignId('batch_id')
                ->nullable()
                ->constrained('batches')
                ->nullOnDelete();

            $table->foreignId('program_id')
                ->nullable()
                ->constrained('programs')
                ->nullOnDelete();

            $table->string('certificate_no')->unique();

            $table->enum('type', [
                'completion',
                'achievement',
                'excellence',
                'participation',
            ])->default('completion');

            $table->string('title')->default('Certificate of Completion');

            $table->date('issued_date')->nullable();
            $table->date('completed_date')->nullable();

            $table->decimal('final_score', 5, 2)->nullable();
            $table->string('grade')->nullable();

            $table->string('public_token')->unique();
            $table->string('verification_url')->nullable();
            $table->string('qr_code_path')->nullable();
            $table->string('pdf_path')->nullable();

            $table->enum('status', [
                'draft',
                'issued',
                'revoked',
                'expired',
            ])->default('draft');

            $table->text('revocation_reason')->nullable();
            $table->timestamp('revoked_at')->nullable();

            $table->foreignId('issued_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('issued_at')->nullable();

            $table->timestamps();
            $table->softDeletes();

            $table->unique([
                'student_id',
                'batch_id',
                'program_id',
                'type',
            ], 'student_program_certificate_unique');

            $table->index(['student_id', 'status']);
            $table->index(['program_id', 'status']);
            $table->index('public_token');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('certificates');
    }
};