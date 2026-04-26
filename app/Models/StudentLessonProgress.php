<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class StudentLessonProgress extends Model
{
    protected $table = 'student_lesson_progresses';

    protected $fillable = [
        'student_id',
        'sub_topic_id',
        'last_position_seconds',
        'duration_seconds',
        'progress_percentage',
        'is_completed',
        'completed_at',
        'last_watched_at',
    ];

    protected $casts = [
        'last_position_seconds' => 'integer',
        'duration_seconds' => 'integer',
        'progress_percentage' => 'decimal:2',
        'is_completed' => 'boolean',
        'completed_at' => 'datetime',
        'last_watched_at' => 'datetime',
    ];

    public function student(): BelongsTo
    {
        return $this->belongsTo(Student::class);
    }

    public function subTopic(): BelongsTo
    {
        return $this->belongsTo(SubTopic::class);
    }
}