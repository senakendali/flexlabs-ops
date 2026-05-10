<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CommunityComment extends Model
{
    use HasFactory;

    protected $fillable = [
        'community_post_id',
        'author_type',
        'author_id',
        'student_id',
        'body',
        'is_solution',
        'is_active',
    ];

    protected $casts = [
        'is_solution' => 'boolean',
        'is_active' => 'boolean',
    ];

    public function post(): BelongsTo
    {
        return $this->belongsTo(CommunityPost::class, 'community_post_id');
    }

    public function student(): BelongsTo
    {
        return $this->belongsTo(Student::class, 'student_id');
    }
}