<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class StudentPreference extends Model
{
    use HasFactory;

    public const EMAIL_NOTIFICATIONS = 'email_notifications';
    public const LEARNING_REMINDERS = 'learning_reminders';
    public const PROGRESS_SUMMARY = 'progress_summary';

    public const AVAILABLE_KEYS = [
        self::EMAIL_NOTIFICATIONS,
        self::LEARNING_REMINDERS,
        self::PROGRESS_SUMMARY,
    ];

    protected $fillable = [
        'student_id',
        'preference_key',
        'enabled',
    ];

    protected $casts = [
        'student_id' => 'integer',
        'enabled' => 'boolean',
    ];

    public function student(): BelongsTo
    {
        return $this->belongsTo(Student::class);
    }
}