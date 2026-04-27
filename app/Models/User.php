<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;

use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;

class User extends Authenticatable
{
    /** @use HasFactory<UserFactory> */
    use HasApiTokens, HasFactory, Notifiable;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'name',
        'email',
        'password',
        'user_type',
        'role',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var list<string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
        ];
    }

    public function atkRequests(): HasMany
    {
        return $this->hasMany(AtkRequest::class, 'user_id');
    }

    public function approvedAtkRequests(): HasMany
    {
        return $this->hasMany(AtkRequest::class, 'approved_by');
    }

    public function createdMarketingReports(): HasMany
    {
        return $this->hasMany(MarketingReport::class, 'created_by');
    }

    public function updatedMarketingReports(): HasMany
    {
        return $this->hasMany(MarketingReport::class, 'updated_by');
    }

    public function student(): HasOne
    {
        return $this->hasOne(Student::class);
    }

    public function studentEnrollments(): HasMany
    {
        return $this->hasManyThrough(
            StudentEnrollment::class,
            Student::class,
            'user_id',
            'student_id',
            'id',
            'id'
        );
    }

    public function createdStudentEnrollments(): HasMany
    {
        return $this->hasMany(StudentEnrollment::class, 'created_by');
    }

    public function updatedStudentEnrollments(): HasMany
    {
        return $this->hasMany(StudentEnrollment::class, 'updated_by');
    }

    public function getIsStudentAttribute(): bool
    {
        return ($this->user_type === 'student') || ($this->role === 'student');
    }

    public function getIsInstructorAttribute(): bool
    {
        return ($this->user_type === 'instructor') || ($this->role === 'instructor');
    }

    public function getIsAdminAttribute(): bool
    {
        return ($this->user_type === 'admin') || ($this->role === 'admin');
    }

    public function instructor(): HasOne
    {
        return $this->hasOne(Instructor::class, 'user_id');
    }

    public function createdAnnouncements(): HasMany
    {
        return $this->hasMany(Announcement::class, 'created_by');
    }

    public function updatedAnnouncements(): HasMany
    {
        return $this->hasMany(Announcement::class, 'updated_by');
    }

    public function organizedMeetingMinutes(): HasMany
    {
        return $this->hasMany(MeetingMinute::class, 'organizer_id');
    }

    public function createdMeetingMinutes(): HasMany
    {
        return $this->hasMany(MeetingMinute::class, 'created_by');
    }

    public function updatedMeetingMinutes(): HasMany
    {
        return $this->hasMany(MeetingMinute::class, 'updated_by');
    }

    public function meetingMinuteParticipations(): HasMany
    {
        return $this->hasMany(MeetingMinuteParticipant::class);
    }

    public function meetingMinuteActionItems(): HasMany
    {
        return $this->hasMany(MeetingMinuteActionItem::class, 'pic_user_id');
    }
}