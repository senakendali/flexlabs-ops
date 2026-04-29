<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Certificate extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'report_card_id',
        'student_id',
        'batch_id',
        'program_id',
        'certificate_no',
        'type',
        'title',
        'issued_date',
        'completed_date',
        'final_score',
        'grade',
        'public_token',
        'verification_url',
        'qr_code_path',
        'pdf_path',
        'status',
        'revocation_reason',
        'revoked_at',
        'issued_by',
        'issued_at',
    ];

    protected $casts = [
        'issued_date' => 'date',
        'completed_date' => 'date',
        'final_score' => 'decimal:2',
        'revoked_at' => 'datetime',
        'issued_at' => 'datetime',
    ];

    public function reportCard(): BelongsTo
    {
        return $this->belongsTo(ReportCard::class);
    }

    public function student(): BelongsTo
    {
        return $this->belongsTo(Student::class);
    }

    public function batch(): BelongsTo
    {
        return $this->belongsTo(Batch::class);
    }

    public function program(): BelongsTo
    {
        return $this->belongsTo(Program::class);
    }

    public function issuer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'issued_by');
    }

    public function isValid(): bool
    {
        return $this->status === 'issued';
    }

    public function isRevoked(): bool
    {
        return $this->status === 'revoked';
    }

    public function scopeIssued($query)
    {
        return $query->where('status', 'issued');
    }

    public function scopeValid($query)
    {
        return $query->where('status', 'issued');
    }
}