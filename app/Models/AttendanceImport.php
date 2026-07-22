<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class AttendanceImport extends Model
{
    public const STATUS_UPLOADED = 'uploaded';
    public const STATUS_REVIEWING = 'reviewing';
    public const STATUS_PROCESSING = 'processing';
    public const STATUS_COMPLETED = 'completed';
    public const STATUS_FAILED = 'failed';
    public const STATUS_CANCELLED = 'cancelled';

    protected $fillable = [
        'original_file_name',
        'stored_file_path',
        'sheet_name',
        'date_from',
        'date_to',

        'total_rows',
        'imported_rows',
        'generated_rows',
        'valid_rows',
        'review_rows',
        'error_rows',
        'duplicate_rows',

        'status',

        'uploaded_by',
        'confirmed_by',

        'imported_at',
        'confirmed_at',

        'failure_message',

        'settings',
        'summary',
    ];

    protected $casts = [
        'date_from' => 'date',
        'date_to' => 'date',

        'total_rows' => 'integer',
        'imported_rows' => 'integer',
        'generated_rows' => 'integer',
        'valid_rows' => 'integer',
        'review_rows' => 'integer',
        'error_rows' => 'integer',
        'duplicate_rows' => 'integer',

        'imported_at' => 'datetime',
        'confirmed_at' => 'datetime',

        'settings' => 'array',
        'summary' => 'array',
    ];

    public function rows(): HasMany
    {
        return $this->hasMany(
            AttendanceImportRow::class
        );
    }

    public function attendances(): HasMany
    {
        return $this->hasMany(
            EmployeeAttendance::class
        );
    }

    public function uploader(): BelongsTo
    {
        return $this->belongsTo(
            User::class,
            'uploaded_by'
        );
    }

    public function confirmer(): BelongsTo
    {
        return $this->belongsTo(
            User::class,
            'confirmed_by'
        );
    }

    public function scopeReviewing(Builder $query): Builder
    {
        return $query->where(
            'status',
            self::STATUS_REVIEWING
        );
    }

    public function scopeCompleted(Builder $query): Builder
    {
        return $query->where(
            'status',
            self::STATUS_COMPLETED
        );
    }
}