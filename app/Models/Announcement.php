<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Collection;

class Announcement extends Model
{
    protected $fillable = [
        'title',
        'slug',
        'content',
        'target_type',
        'program_id',
        'batch_id',
        'publish_at',
        'expired_at',
        'status',
        'is_pinned',
        'is_active',
        'created_by',
        'updated_by',
    ];

    protected $casts = [
        'publish_at' => 'datetime',
        'expired_at' => 'datetime',
        'is_pinned' => 'boolean',
        'is_active' => 'boolean',
    ];

    public function program(): BelongsTo
    {
        return $this->belongsTo(Program::class);
    }

    public function batch(): BelongsTo
    {
        return $this->belongsTo(Batch::class);
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function updater(): BelongsTo
    {
        return $this->belongsTo(User::class, 'updated_by');
    }

    public function scopeActive(Builder $query): Builder
    {
        return $query->where('is_active', true);
    }

    public function scopePublished(Builder $query): Builder
    {
        return $query->where('status', 'published');
    }

    public function scopeVisibleNow(Builder $query): Builder
    {
        return $query
            ->active()
            ->published()
            ->where(function (Builder $publishQuery) {
                $publishQuery
                    ->whereNull('publish_at')
                    ->orWhere('publish_at', '<=', now());
            })
            ->where(function (Builder $expiredQuery) {
                $expiredQuery
                    ->whereNull('expired_at')
                    ->orWhere('expired_at', '>=', now());
            });
    }

    public function scopeForProgramsAndBatches(Builder $query, Collection|array $programIds, Collection|array $batchIds): Builder
    {
        $programIds = collect($programIds)->filter()->unique()->values();
        $batchIds = collect($batchIds)->filter()->unique()->values();

        return $query->where(function (Builder $targetQuery) use ($programIds, $batchIds) {
            $targetQuery->where('target_type', 'all');

            if ($programIds->isNotEmpty()) {
                $targetQuery->orWhere(function (Builder $programQuery) use ($programIds) {
                    $programQuery
                        ->where('target_type', 'program')
                        ->whereIn('program_id', $programIds);
                });
            }

            if ($batchIds->isNotEmpty()) {
                $targetQuery->orWhere(function (Builder $batchQuery) use ($batchIds) {
                    $batchQuery
                        ->where('target_type', 'batch')
                        ->whereIn('batch_id', $batchIds);
                });
            }
        });
    }

    public function getTargetLabelAttribute(): string
    {
        return match ($this->target_type) {
            'all' => 'All Students',
            'program' => 'Program',
            'batch' => 'Batch',
            default => str($this->target_type)->replace('_', ' ')->title()->toString(),
        };
    }

    public function getStatusLabelAttribute(): string
    {
        return str($this->status)->replace('_', ' ')->title()->toString();
    }

    public function getIsVisibleAttribute(): bool
    {
        $isPublished = $this->status === 'published';
        $isActive = (bool) $this->is_active;

        $isPublishReady = !$this->publish_at || $this->publish_at->lte(now());
        $isNotExpired = !$this->expired_at || $this->expired_at->gte(now());

        return $isPublished && $isActive && $isPublishReady && $isNotExpired;
    }
}