<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ArticleGeneration extends Model
{
    /*
    |--------------------------------------------------------------------------
    | Providers
    |--------------------------------------------------------------------------
    */
    public const PROVIDER_GEMINI = 'gemini';

    /*
    |--------------------------------------------------------------------------
    | Generation Types
    |--------------------------------------------------------------------------
    */
    public const TYPE_OUTLINE = 'outline';
    public const TYPE_FULL_ARTICLE = 'full_article';
    public const TYPE_SEO = 'seo';
    public const TYPE_CREATIVE = 'creative';
    public const TYPE_SOCIAL = 'social';
    public const TYPE_REGENERATE_SECTION = 'regenerate_section';
    public const TYPE_WORKSHOP_ARTICLE = 'workshop_article';

    /*
    |--------------------------------------------------------------------------
    | Statuses
    |--------------------------------------------------------------------------
    */
    public const STATUS_PENDING = 'pending';
    public const STATUS_SUCCESS = 'success';
    public const STATUS_FAILED = 'failed';

    protected $fillable = [
        'article_id',
        'user_id',

        'generation_type',
        'section_key',

        'provider',
        'model',

        'prompt_payload',
        'response_payload',

        'input_tokens',
        'output_tokens',
        'total_tokens',

        'duration_ms',

        'status',
        'error_message',
        'meta',
    ];

    protected $casts = [
        'article_id' => 'integer',
        'user_id' => 'integer',

        'prompt_payload' => 'array',
        'response_payload' => 'array',
        'meta' => 'array',

        'input_tokens' => 'integer',
        'output_tokens' => 'integer',
        'total_tokens' => 'integer',
        'duration_ms' => 'integer',
    ];

    /*
    |--------------------------------------------------------------------------
    | Options for Controller / Blade
    |--------------------------------------------------------------------------
    */

    public static function providerOptions(): array
    {
        return [
            self::PROVIDER_GEMINI => 'Gemini',
        ];
    }

    public static function generationTypeOptions(): array
    {
        return [
            self::TYPE_OUTLINE => 'Outline',
            self::TYPE_FULL_ARTICLE => 'Full Article',
            self::TYPE_SEO => 'SEO',
            self::TYPE_CREATIVE => 'Creative',
            self::TYPE_SOCIAL => 'Social',
            self::TYPE_REGENERATE_SECTION => 'Regenerate Section',
            self::TYPE_WORKSHOP_ARTICLE => 'Workshop Article',
        ];
    }

    public static function statusOptions(): array
    {
        return [
            self::STATUS_PENDING => 'Pending',
            self::STATUS_SUCCESS => 'Success',
            self::STATUS_FAILED => 'Failed',
        ];
    }

    public static function sectionOptions(): array
    {
        return [
            'title' => 'Title',
            'slug' => 'Slug',
            'excerpt' => 'Excerpt',
            'body_intro' => 'Body Intro',
            'body_content' => 'Body Content',
            'body_conclusion' => 'Body Conclusion',
            'seo_title' => 'SEO Title',
            'meta_description' => 'Meta Description',
            'creative_brief' => 'Creative Brief',
            'hero_image_alt' => 'Hero Image Alt',
            'instagram_caption' => 'Instagram Caption',
            'linkedin_caption' => 'LinkedIn Caption',
            'whatsapp_caption' => 'WhatsApp Caption',
        ];
    }

    /*
    |--------------------------------------------------------------------------
    | Relationships
    |--------------------------------------------------------------------------
    */

    public function article(): BelongsTo
    {
        return $this->belongsTo(Article::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /*
    |--------------------------------------------------------------------------
    | Scopes
    |--------------------------------------------------------------------------
    */

    public function scopeForArticle(Builder $query, int $articleId): Builder
    {
        return $query->where('article_id', $articleId);
    }

    public function scopeForUser(Builder $query, int $userId): Builder
    {
        return $query->where('user_id', $userId);
    }

    public function scopeType(Builder $query, string $type): Builder
    {
        return $query->where('generation_type', $type);
    }

    public function scopeProvider(Builder $query, string $provider): Builder
    {
        return $query->where('provider', $provider);
    }

    public function scopeSuccess(Builder $query): Builder
    {
        return $query->where('status', self::STATUS_SUCCESS);
    }

    public function scopeFailed(Builder $query): Builder
    {
        return $query->where('status', self::STATUS_FAILED);
    }

    public function scopeLatestFirst(Builder $query): Builder
    {
        return $query->latest();
    }

    /*
    |--------------------------------------------------------------------------
    | Helpers
    |--------------------------------------------------------------------------
    */

    public function getGenerationTypeLabelAttribute(): string
    {
        return self::generationTypeOptions()[$this->generation_type]
            ?? ucfirst(str_replace('_', ' ', (string) $this->generation_type));
    }

    public function getStatusLabelAttribute(): string
    {
        return self::statusOptions()[$this->status]
            ?? ucfirst(str_replace('_', ' ', (string) $this->status));
    }

    public function getProviderLabelAttribute(): string
    {
        return self::providerOptions()[$this->provider]
            ?? ucfirst(str_replace('_', ' ', (string) $this->provider));
    }

    public function isPending(): bool
    {
        return $this->status === self::STATUS_PENDING;
    }

    public function isSuccess(): bool
    {
        return $this->status === self::STATUS_SUCCESS;
    }

    public function isFailed(): bool
    {
        return $this->status === self::STATUS_FAILED;
    }
}