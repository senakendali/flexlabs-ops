<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Article extends Model
{
    use SoftDeletes;

    /*
    |--------------------------------------------------------------------------
    | Source Types
    |--------------------------------------------------------------------------
    */
    public const SOURCE_MANUAL = 'manual';
    public const SOURCE_WORKSHOP = 'workshop';
    public const SOURCE_WEBINAR = 'webinar';
    public const SOURCE_EVENT = 'event';
    public const SOURCE_CAMPAIGN = 'campaign';

    /*
    |--------------------------------------------------------------------------
    | Article Types
    |--------------------------------------------------------------------------
    */
    public const TYPE_TUTORIAL = 'tutorial';
    public const TYPE_INSIGHT = 'insight';
    public const TYPE_LISTICLE = 'listicle';
    public const TYPE_ANNOUNCEMENT = 'announcement';
    public const TYPE_CASE_STUDY = 'case_study';
    public const TYPE_WORKSHOP_PROMO = 'workshop_promo';

    /*
    |--------------------------------------------------------------------------
    | Statuses
    |--------------------------------------------------------------------------
    | Article Generator dipakai sebagai content production workspace.
    | Status ini tidak berarti artikel sudah live di flexlabs.co.id.
    */
    public const STATUS_DRAFT = 'draft';
    public const STATUS_GENERATING = 'generating';
    public const STATUS_AI_GENERATED = 'ai_generated';
    public const STATUS_EDITED = 'edited';
    public const STATUS_READY_TO_COPY = 'ready_to_copy';
    public const STATUS_GENERATION_FAILED = 'generation_failed';
    public const STATUS_ARCHIVED = 'archived';

    /*
    |--------------------------------------------------------------------------
    | Legacy Workflow Statuses
    |--------------------------------------------------------------------------
    | Tetap disimpan sementara supaya controller/blade lama tidak error
    | sebelum seluruh flow approval/publish dibersihkan.
    */
    public const STATUS_READY_FOR_REVIEW = 'ready_for_review';
    public const STATUS_APPROVED = 'approved';
    public const STATUS_SCHEDULED = 'scheduled';
    public const STATUS_PUBLISHED = 'published';

    /*
    |--------------------------------------------------------------------------
    | Tone Presets
    |--------------------------------------------------------------------------
    */
    public const TONE_PROFESSIONAL_EDUCATIVE = 'professional_educative';
    public const TONE_FRIENDLY_CONVERSATIONAL = 'friendly_conversational';
    public const TONE_THOUGHT_LEADERSHIP = 'thought_leadership';
    public const TONE_TECHNICAL_DEEP_DIVE = 'technical_deep_dive';
    public const TONE_MARKETING_SOFT_SELL = 'marketing_soft_sell';
    public const TONE_INSPIRATIONAL = 'inspirational';

    /*
    |--------------------------------------------------------------------------
    | Length Presets
    |--------------------------------------------------------------------------
    */
    public const LENGTH_SHORT = 'short';
    public const LENGTH_MEDIUM = 'medium';
    public const LENGTH_LONG = 'long';

    protected $fillable = [
        'source_type',
        'source_id',

        'article_type',
        'category',
        'tone',
        'target_audience',
        'language',
        'length_preset',

        'primary_keyword',
        'secondary_keywords',

        'main_angle',
        'must_include',
        'avoid_points',
        'brief_notes',

        'title',
        'slug',
        'excerpt',
        'body_html',

        'status',

        'seo_title',
        'meta_description',
        'meta_keywords',

        'og_title',
        'og_description',
        'og_image_url',

        'canonical_url',
        'tags',

        'hero_image_url',
        'hero_image_alt',
        'creative_brief',

        'social_captions',

        'ai_brief',
        'ai_outline',

        'scheduled_publish_at',
        'published_at',

        'external_url',
        'external_post_id',

        'created_by',
        'reviewed_by',
        'approved_by',
        'published_by',

        'reviewed_at',
        'approved_at',
    ];

    protected $casts = [
        'source_id' => 'integer',

        'secondary_keywords' => 'array',
        'meta_keywords' => 'array',
        'tags' => 'array',
        'creative_brief' => 'array',
        'social_captions' => 'array',
        'ai_brief' => 'array',
        'ai_outline' => 'array',

        'scheduled_publish_at' => 'datetime',
        'published_at' => 'datetime',
        'reviewed_at' => 'datetime',
        'approved_at' => 'datetime',
        'deleted_at' => 'datetime',
    ];

    /*
    |--------------------------------------------------------------------------
    | Options for Controller / Blade
    |--------------------------------------------------------------------------
    */

    public static function sourceTypeOptions(): array
    {
        return [
            self::SOURCE_MANUAL => 'Manual',
            self::SOURCE_WORKSHOP => 'Workshop',
            self::SOURCE_WEBINAR => 'Webinar',
            self::SOURCE_EVENT => 'Event',
            self::SOURCE_CAMPAIGN => 'Campaign',
        ];
    }

    public static function articleTypeOptions(): array
    {
        return [
            self::TYPE_TUTORIAL => 'Tutorial',
            self::TYPE_INSIGHT => 'Insight',
            self::TYPE_LISTICLE => 'Listicle',
            self::TYPE_ANNOUNCEMENT => 'Announcement',
            self::TYPE_CASE_STUDY => 'Case Study',
            self::TYPE_WORKSHOP_PROMO => 'Workshop Promo',
        ];
    }

    public static function statusOptions(): array
    {
        return [
            self::STATUS_DRAFT => 'Draft',
            self::STATUS_GENERATING => 'Generating Content',
            self::STATUS_AI_GENERATED => 'Generated',
            self::STATUS_EDITED => 'Edited',
            self::STATUS_READY_TO_COPY => 'Ready to Copy',
            self::STATUS_GENERATION_FAILED => 'Generation Failed',
            self::STATUS_ARCHIVED => 'Archived',
        ];
    }

    public static function legacyStatusOptions(): array
    {
        return [
            self::STATUS_READY_FOR_REVIEW => 'Ready to Copy',
            self::STATUS_APPROVED => 'Ready to Copy',
            self::STATUS_SCHEDULED => 'Ready to Copy',
            self::STATUS_PUBLISHED => 'Ready to Copy',
        ];
    }

    public static function allStatusOptions(): array
    {
        return array_merge(
            self::statusOptions(),
            self::legacyStatusOptions()
        );
    }

    public static function toneOptions(): array
    {
        return [
            self::TONE_PROFESSIONAL_EDUCATIVE => 'Professional Educative',
            self::TONE_FRIENDLY_CONVERSATIONAL => 'Friendly & Conversational',
            self::TONE_THOUGHT_LEADERSHIP => 'Thought Leadership',
            self::TONE_TECHNICAL_DEEP_DIVE => 'Technical Deep Dive',
            self::TONE_MARKETING_SOFT_SELL => 'Marketing Soft-Sell',
            self::TONE_INSPIRATIONAL => 'Inspirational',
        ];
    }

    public static function lengthOptions(): array
    {
        return [
            self::LENGTH_SHORT => 'Short',
            self::LENGTH_MEDIUM => 'Medium',
            self::LENGTH_LONG => 'Long',
        ];
    }

    public static function categoryOptions(): array
    {
        return [
            'software_engineering' => 'Software Engineering',
            'web_development' => 'Web Development',
            'ai' => 'Artificial Intelligence',
            'laravel' => 'Laravel',
            'ui_ux' => 'UI/UX Design',
            'career' => 'Career',
            'workshop' => 'Workshop',
            'webinar' => 'Webinar',
            'company_update' => 'Company Update',
        ];
    }

    public static function defaultStatus(): string
    {
        return self::STATUS_DRAFT;
    }

    /*
    |--------------------------------------------------------------------------
    | Relationships
    |--------------------------------------------------------------------------
    */

    public function generations(): HasMany
    {
        return $this->hasMany(ArticleGeneration::class);
    }

    public function latestGeneration(): HasMany
    {
        return $this->generations()->latest();
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function reviewer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'reviewed_by');
    }

    public function approver(): BelongsTo
    {
        return $this->belongsTo(User::class, 'approved_by');
    }

    public function publisher(): BelongsTo
    {
        return $this->belongsTo(User::class, 'published_by');
    }

    /*
    |--------------------------------------------------------------------------
    | Scopes
    |--------------------------------------------------------------------------
    */

    public function scopeStatus(Builder $query, string $status): Builder
    {
        return $query->where('status', $status);
    }

    public function scopeDraft(Builder $query): Builder
    {
        return $query->where('status', self::STATUS_DRAFT);
    }

    public function scopeGenerating(Builder $query): Builder
    {
        return $query->where('status', self::STATUS_GENERATING);
    }

    public function scopeAiGenerated(Builder $query): Builder
    {
        return $query->where('status', self::STATUS_AI_GENERATED);
    }

    public function scopeReadyToCopy(Builder $query): Builder
    {
        return $query->whereIn('status', [
            self::STATUS_READY_TO_COPY,

            // Legacy statuses treated as ready content.
            self::STATUS_READY_FOR_REVIEW,
            self::STATUS_APPROVED,
            self::STATUS_SCHEDULED,
            self::STATUS_PUBLISHED,
        ]);
    }

    public function scopeGenerationFailed(Builder $query): Builder
    {
        return $query->where('status', self::STATUS_GENERATION_FAILED);
    }

    public function scopeArchived(Builder $query): Builder
    {
        return $query->where('status', self::STATUS_ARCHIVED);
    }

    public function scopePublished(Builder $query): Builder
    {
        return $query->readyToCopy();
    }

    public function scopeScheduled(Builder $query): Builder
    {
        return $query->where('status', self::STATUS_SCHEDULED)
            ->whereNotNull('scheduled_publish_at');
    }

    public function scopeForSource(Builder $query, string $sourceType, ?int $sourceId = null): Builder
    {
        $query->where('source_type', $sourceType);

        if ($sourceId !== null) {
            $query->where('source_id', $sourceId);
        }

        return $query;
    }

    public function scopeForCategory(Builder $query, ?string $category): Builder
    {
        if (! $category) {
            return $query;
        }

        return $query->where('category', $category);
    }

    public function scopeSearch(Builder $query, ?string $keyword): Builder
    {
        if (! $keyword) {
            return $query;
        }

        return $query->where(function (Builder $subQuery) use ($keyword) {
            $subQuery
                ->where('title', 'like', "%{$keyword}%")
                ->orWhere('slug', 'like', "%{$keyword}%")
                ->orWhere('excerpt', 'like', "%{$keyword}%")
                ->orWhere('primary_keyword', 'like', "%{$keyword}%")
                ->orWhere('seo_title', 'like', "%{$keyword}%")
                ->orWhere('meta_description', 'like', "%{$keyword}%");
        });
    }

    /*
    |--------------------------------------------------------------------------
    | Accessors
    |--------------------------------------------------------------------------
    */

    public function getStatusLabelAttribute(): string
    {
        return self::allStatusOptions()[$this->status]
            ?? ucfirst(str_replace('_', ' ', (string) $this->status));
    }

    public function getSourceTypeLabelAttribute(): string
    {
        return self::sourceTypeOptions()[$this->source_type]
            ?? ucfirst(str_replace('_', ' ', (string) $this->source_type));
    }

    public function getArticleTypeLabelAttribute(): string
    {
        return self::articleTypeOptions()[$this->article_type]
            ?? ucfirst(str_replace('_', ' ', (string) $this->article_type));
    }

    public function getToneLabelAttribute(): string
    {
        return self::toneOptions()[$this->tone]
            ?? ucfirst(str_replace('_', ' ', (string) $this->tone));
    }

    public function getIsReadyToCopyAttribute(): bool
    {
        return $this->isReadyToCopy();
    }

    /*
    |--------------------------------------------------------------------------
    | Status Helpers
    |--------------------------------------------------------------------------
    */

    public function isDraft(): bool
    {
        return $this->status === self::STATUS_DRAFT;
    }

    public function isGenerating(): bool
    {
        return $this->status === self::STATUS_GENERATING;
    }

    public function isAiGenerated(): bool
    {
        return $this->status === self::STATUS_AI_GENERATED;
    }

    public function isGenerationFailed(): bool
    {
        return $this->status === self::STATUS_GENERATION_FAILED;
    }

    public function isReadyToCopy(): bool
    {
        return in_array($this->status, [
            self::STATUS_READY_TO_COPY,

            // Legacy statuses treated as ready content.
            self::STATUS_READY_FOR_REVIEW,
            self::STATUS_APPROVED,
            self::STATUS_SCHEDULED,
            self::STATUS_PUBLISHED,
        ], true);
    }

    public function isArchived(): bool
    {
        return $this->status === self::STATUS_ARCHIVED;
    }

    public function isPublished(): bool
    {
        return $this->isReadyToCopy();
    }

    public function isScheduled(): bool
    {
        return $this->status === self::STATUS_SCHEDULED && $this->scheduled_publish_at !== null;
    }

    public function canBeEdited(): bool
    {
        return ! in_array($this->status, [
            self::STATUS_GENERATING,
            self::STATUS_ARCHIVED,
        ], true);
    }

    public function canRunAiGeneration(): bool
    {
        return ! in_array($this->status, [
            self::STATUS_GENERATING,
            self::STATUS_ARCHIVED,
        ], true);
    }

    public function canBeMarkedReadyToCopy(): bool
    {
        return in_array($this->status, [
            self::STATUS_DRAFT,
            self::STATUS_AI_GENERATED,
            self::STATUS_EDITED,
            self::STATUS_GENERATION_FAILED,
            self::STATUS_READY_TO_COPY,

            // Legacy support during transition.
            self::STATUS_READY_FOR_REVIEW,
            self::STATUS_APPROVED,
            self::STATUS_SCHEDULED,
            self::STATUS_PUBLISHED,
        ], true);
    }

    public function canBePublished(): bool
    {
        return $this->canBeMarkedReadyToCopy();
    }

    /*
    |--------------------------------------------------------------------------
    | Mutating Helpers
    |--------------------------------------------------------------------------
    */

    public function markAsGenerating(): bool
    {
        return $this->update([
            'status' => self::STATUS_GENERATING,
        ]);
    }

    public function markAsGenerated(): bool
    {
        return $this->update([
            'status' => self::STATUS_AI_GENERATED,
        ]);
    }

    public function markAsGenerationFailed(): bool
    {
        return $this->update([
            'status' => self::STATUS_GENERATION_FAILED,
        ]);
    }

    public function markAsReadyToCopy(?int $userId = null): bool
    {
        return $this->update([
            'status' => self::STATUS_READY_TO_COPY,

            // Field lama tetap diisi sebagai audit internal saja.
            // UI tidak perlu menampilkan approved/published wording.
            'approved_by' => $userId,
            'approved_at' => now(),
            'published_by' => $userId,
            'published_at' => now(),
            'scheduled_publish_at' => null,
        ]);
    }
}