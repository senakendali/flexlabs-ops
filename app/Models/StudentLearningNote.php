<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class StudentLearningNote extends Model
{
    use SoftDeletes;

    protected $table = 'student_learning_notes';

    protected $fillable = [
        'student_id',

        'course_id',
        'course_slug',
        'course_title',

        'module_id',
        'module_title',

        'topic_id',
        'topic_slug',
        'topic_title',

        'sub_topic_id',
        'sub_topic_slug',
        'sub_topic_title',

        'lesson_id',
        'lesson_slug',
        'lesson_title',

        'title',
        'content',
        'tags',

        'video_timestamp_seconds',
        'status',
    ];

    protected $casts = [
        'student_id' => 'integer',

        'course_id' => 'integer',
        'module_id' => 'integer',
        'topic_id' => 'integer',
        'sub_topic_id' => 'integer',
        'lesson_id' => 'integer',

        'tags' => 'array',
        'video_timestamp_seconds' => 'integer',

        'created_at' => 'datetime',
        'updated_at' => 'datetime',
        'deleted_at' => 'datetime',
    ];

    /*
    |--------------------------------------------------------------------------
    | Scopes
    |--------------------------------------------------------------------------
    */

    public function scopeForStudent(Builder $query, int $studentId): Builder
    {
        return $query->where('student_id', $studentId);
    }

    public function scopeActive(Builder $query): Builder
    {
        return $query->where('status', 'active');
    }

    public function scopeByCourse(Builder $query, mixed $courseId = null, ?string $courseSlug = null): Builder
    {
        return $query
            ->when($courseId, fn (Builder $q) => $q->where('course_id', $courseId))
            ->when($courseSlug, fn (Builder $q) => $q->where('course_slug', $courseSlug));
    }

    public function scopeByTopic(Builder $query, mixed $topicId = null, ?string $topicSlug = null): Builder
    {
        return $query
            ->when($topicId, fn (Builder $q) => $q->where('topic_id', $topicId))
            ->when($topicSlug, fn (Builder $q) => $q->where('topic_slug', $topicSlug));
    }

    public function scopeBySubTopic(Builder $query, mixed $subTopicId = null, ?string $subTopicSlug = null): Builder
    {
        return $query
            ->when($subTopicId, fn (Builder $q) => $q->where('sub_topic_id', $subTopicId))
            ->when($subTopicSlug, fn (Builder $q) => $q->where('sub_topic_slug', $subTopicSlug));
    }

    public function scopeByLesson(Builder $query, mixed $lessonId = null, ?string $lessonSlug = null): Builder
    {
        return $query
            ->when($lessonId, fn (Builder $q) => $q->where('lesson_id', $lessonId))
            ->when($lessonSlug, fn (Builder $q) => $q->where('lesson_slug', $lessonSlug));
    }

    public function scopeSearch(Builder $query, ?string $keyword = null): Builder
    {
        $keyword = trim((string) $keyword);

        if ($keyword === '') {
            return $query;
        }

        return $query->where(function (Builder $q) use ($keyword) {
            $q->where('title', 'like', "%{$keyword}%")
                ->orWhere('content', 'like', "%{$keyword}%")
                ->orWhere('course_title', 'like', "%{$keyword}%")
                ->orWhere('module_title', 'like', "%{$keyword}%")
                ->orWhere('topic_title', 'like', "%{$keyword}%")
                ->orWhere('sub_topic_title', 'like', "%{$keyword}%")
                ->orWhere('lesson_title', 'like', "%{$keyword}%");
        });
    }

    /*
    |--------------------------------------------------------------------------
    | Accessors
    |--------------------------------------------------------------------------
    */

    public function getVideoTimestampLabelAttribute(): string
    {
        $seconds = (int) $this->video_timestamp_seconds;

        $minutes = floor($seconds / 60);
        $remainingSeconds = $seconds % 60;

        return sprintf('%d:%02d', $minutes, $remainingSeconds);
    }
}