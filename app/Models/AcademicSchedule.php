<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AcademicSchedule extends Model
{
    use HasFactory;

    protected $fillable = [
        'program_id',
        'batch_id',
        'title',
        'schedule_type',
        'schedule_date',
        'is_all_day',
        'start_time',
        'end_time',
        'instructor_id',
        'delivery_mode',
        'meeting_link',
        'location',
        'notes',
        'status',
        'created_by',
    ];

    protected function casts(): array
    {
        return [
            'program_id' => 'integer',
            'batch_id' => 'integer',
            'schedule_date' => 'date',
            'is_all_day' => 'boolean',
            'instructor_id' => 'integer',
            'created_by' => 'integer',
        ];
    }

    public function program(): BelongsTo
    {
        return $this->belongsTo(Program::class);
    }

    public function batch(): BelongsTo
    {
        return $this->belongsTo(Batch::class);
    }

    public function instructor(): BelongsTo
    {
        return $this->belongsTo(Instructor::class, 'instructor_id', 'user_id');
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function scopeBetweenDates(
        Builder $query,
        string $startDate,
        string $endDate
    ): Builder {
        return $query->whereBetween('schedule_date', [$startDate, $endDate]);
    }

    public function scopeForProgram(Builder $query, ?int $programId): Builder
    {
        return $query->when(
            $programId,
            fn (Builder $query, int $id) => $query->where('program_id', $id)
        );
    }

    public function scopeForBatch(Builder $query, ?int $batchId): Builder
    {
        return $query->when(
            $batchId,
            fn (Builder $query, int $id) => $query->where('batch_id', $id)
        );
    }
}