<?php

namespace App\Services;

use App\Models\Article;
use App\Models\ArticleGeneration;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;
use RuntimeException;
use Throwable;

class ArticleAiGeneratorService
{
    private string $apiKey;
    private string $model;
    private string $endpoint;

    public function __construct()
    {
        $this->apiKey = (string) (config('services.gemini.api_key') ?: env('GEMINI_API_KEY'));
        $this->model = (string) (config('services.gemini.model') ?: env('GEMINI_MODEL', 'gemini-2.5-flash-lite'));

        $this->endpoint = "https://generativelanguage.googleapis.com/v1beta/models/{$this->model}:generateContent";

        if (blank($this->apiKey)) {
            throw new RuntimeException('Gemini API key belum dikonfigurasi.');
        }
    }

    public function generateOutline(Article $article, ?int $userId = null): array
    {
        return $this->runGeneration(
            article: $article,
            userId: $userId,
            generationType: ArticleGeneration::TYPE_OUTLINE,
            prompt: $this->buildOutlinePrompt($article),
            schema: $this->outlineSchema(),
            onSuccess: function (Article $article, array $result) {
                $article->update([
                    'ai_outline' => $result,
                    'status' => Article::STATUS_AI_GENERATED,
                ]);
            }
        );
    }

    public function generateFullArticle(Article $article, ?int $userId = null): array
    {
        return $this->runGeneration(
            article: $article,
            userId: $userId,
            generationType: ArticleGeneration::TYPE_FULL_ARTICLE,
            prompt: $this->buildFullArticlePrompt($article),
            schema: $this->fullArticleSchema(),
            onSuccess: function (Article $article, array $result) {
                $article->update([
                    'title' => Arr::get($result, 'title', $article->title),
                    'slug' => $article->slug ?: Str::slug((string) Arr::get($result, 'title', $article->title)),
                    'excerpt' => Arr::get($result, 'excerpt', $article->excerpt),
                    'body_html' => Arr::get($result, 'body_html', $article->body_html),

                    'seo_title' => Arr::get($result, 'seo.seo_title', $article->seo_title),
                    'meta_description' => Arr::get($result, 'seo.meta_description', $article->meta_description),
                    'og_title' => Arr::get($result, 'seo.og_title', $article->og_title),
                    'og_description' => Arr::get($result, 'seo.og_description', $article->og_description),
                    'tags' => Arr::get($result, 'seo.tags', $article->tags),

                    'hero_image_alt' => Arr::get($result, 'creative.hero_image_alt', $article->hero_image_alt),
                    'creative_brief' => Arr::get($result, 'creative', $article->creative_brief),
                    'social_captions' => Arr::get($result, 'social', $article->social_captions),

                    'status' => Article::STATUS_AI_GENERATED,
                ]);
            }
        );
    }

    public function improveSeo(Article $article, ?int $userId = null): array
    {
        return $this->runGeneration(
            article: $article,
            userId: $userId,
            generationType: ArticleGeneration::TYPE_SEO,
            prompt: $this->buildSeoPrompt($article),
            schema: $this->seoSchema(),
            onSuccess: function (Article $article, array $result) {
                $article->update([
                    'seo_title' => Arr::get($result, 'seo_title', $article->seo_title),
                    'meta_description' => Arr::get($result, 'meta_description', $article->meta_description),
                    'meta_keywords' => Arr::get($result, 'meta_keywords', $article->meta_keywords),
                    'og_title' => Arr::get($result, 'og_title', $article->og_title),
                    'og_description' => Arr::get($result, 'og_description', $article->og_description),
                    'tags' => Arr::get($result, 'tags', $article->tags),
                ]);
            }
        );
    }

    public function suggestCreative(Article $article, ?int $userId = null): array
    {
        return $this->runGeneration(
            article: $article,
            userId: $userId,
            generationType: ArticleGeneration::TYPE_CREATIVE,
            prompt: $this->buildCreativePrompt($article),
            schema: $this->creativeSchema(),
            onSuccess: function (Article $article, array $result) {
                $article->update([
                    'hero_image_alt' => Arr::get($result, 'hero_image_alt', $article->hero_image_alt),
                    'creative_brief' => $result,
                ]);
            }
        );
    }

    private function runGeneration(
        Article $article,
        ?int $userId,
        string $generationType,
        string $prompt,
        array $schema,
        callable $onSuccess
    ): array {
        $startedAt = microtime(true);

        $generation = ArticleGeneration::create([
            'article_id' => $article->id,
            'user_id' => $userId,
            'generation_type' => $generationType,
            'provider' => ArticleGeneration::PROVIDER_GEMINI,
            'model' => $this->model,
            'prompt_payload' => [
                'prompt' => $prompt,
                'schema' => $schema,
            ],
            'status' => ArticleGeneration::STATUS_PENDING,
        ]);

        try {
            $rawResponse = $this->callGemini($prompt, $schema);
            $result = $this->extractJsonResult($rawResponse);

            $onSuccess($article, $result);

            $usage = Arr::get($rawResponse, 'usageMetadata', []);

            $generation->update([
                'response_payload' => [
                    'result' => $result,
                    'raw' => $rawResponse,
                ],
                'input_tokens' => Arr::get($usage, 'promptTokenCount'),
                'output_tokens' => Arr::get($usage, 'candidatesTokenCount'),
                'total_tokens' => Arr::get($usage, 'totalTokenCount'),
                'duration_ms' => (int) round((microtime(true) - $startedAt) * 1000),
                'status' => ArticleGeneration::STATUS_SUCCESS,
                'meta' => [
                    'finish_reason' => Arr::get($rawResponse, 'candidates.0.finishReason'),
                    'safety_ratings' => Arr::get($rawResponse, 'candidates.0.safetyRatings'),
                ],
            ]);

            return [
                'success' => true,
                'message' => 'Konten berhasil dibuat.',
                'result' => $result,
                'generation_id' => $generation->id,
            ];
        } catch (Throwable $exception) {
            $generation->update([
                'duration_ms' => (int) round((microtime(true) - $startedAt) * 1000),
                'status' => ArticleGeneration::STATUS_FAILED,
                'error_message' => $exception->getMessage(),
            ]);

            throw $exception;
        }
    }

    private function callGemini(string $prompt, array $schema): array
    {
        $payload = [
            'contents' => [
                [
                    'role' => 'user',
                    'parts' => [
                        [
                            'text' => $prompt,
                        ],
                    ],
                ],
            ],
            'generationConfig' => [
                'temperature' => 0.7,
                'topP' => 0.9,
                'maxOutputTokens' => 8192,
                'responseMimeType' => 'application/json',
                'responseSchema' => $schema,
            ],
        ];

        $response = Http::timeout(90)
            ->retry(2, 800)
            ->acceptJson()
            ->asJson()
            ->post($this->endpoint . '?key=' . $this->apiKey, $payload);

        if ($response->failed()) {
            $message = Arr::get($response->json(), 'error.message')
                ?: 'Gemini API request gagal.';

            throw new RuntimeException($message);
        }

        return $response->json();
    }

    private function extractJsonResult(array $rawResponse): array
    {
        $text = Arr::get($rawResponse, 'candidates.0.content.parts.0.text');

        if (! is_string($text) || blank($text)) {
            throw new RuntimeException('Gemini tidak mengembalikan konten yang bisa dibaca.');
        }

        $decoded = json_decode($text, true);

        if (! is_array($decoded)) {
            throw new RuntimeException('Response Gemini bukan JSON valid.');
        }

        return $decoded;
    }

    private function buildBaseContext(Article $article): array
    {
        return [
            'title' => $article->title,
            'slug' => $article->slug,
            'article_type' => $article->article_type,
            'category' => $article->category,
            'tone' => $article->tone,
            'target_audience' => $article->target_audience,
            'language' => $article->language ?: 'id',
            'length_preset' => $article->length_preset,
            'primary_keyword' => $article->primary_keyword,
            'secondary_keywords' => $article->secondary_keywords ?? [],
            'main_angle' => $article->main_angle,
            'must_include' => $article->must_include,
            'avoid_points' => $article->avoid_points,
            'brief_notes' => $article->brief_notes,
            'excerpt' => $article->excerpt,
            'body_html' => $article->body_html,
            'seo_title' => $article->seo_title,
            'meta_description' => $article->meta_description,
            'tags' => $article->tags ?? [],
            'creative_brief' => $article->creative_brief ?? [],
            'social_captions' => $article->social_captions ?? [],
            'ai_outline' => $article->ai_outline ?? [],
        ];
    }

    private function buildOutlinePrompt(Article $article): string
    {
        $context = json_encode($this->buildBaseContext($article), JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);

        return <<<PROMPT
You are a professional Indonesian content strategist for FlexLabs.

Create a clear SEO article outline based on this article brief.

Rules:
- Use Indonesian language.
- Make it useful, professional, friendly, and not overly salesy.
- Do not promise instant results.
- Keep the article credible for software engineering education.
- Return only valid JSON that follows the schema.

Article brief:
{$context}
PROMPT;
    }

    private function buildFullArticlePrompt(Article $article): string
    {
        $context = json_encode($this->buildBaseContext($article), JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);

        return <<<PROMPT
You are a professional Indonesian article writer for FlexLabs.

Write a complete article based on this brief and outline.

Rules:
- Use Indonesian language.
- Use clean HTML for body_html.
- Allowed HTML tags: p, h2, h3, ul, ol, li, strong, em, blockquote.
- Do not include markdown fences.
- Do not exaggerate results.
- Do not be too hard-selling.
- Include a helpful CTA at the end.
- Return only valid JSON that follows the schema.

Article context:
{$context}
PROMPT;
    }

    private function buildSeoPrompt(Article $article): string
    {
        $context = json_encode($this->buildBaseContext($article), JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);

        return <<<PROMPT
You are an SEO content editor for FlexLabs.

Improve the SEO metadata for this article.

Rules:
- Use Indonesian language.
- SEO title should be clear and clickable.
- Meta description should be concise and useful.
- Tags should be relevant to FlexLabs content.
- Return only valid JSON that follows the schema.

Article context:
{$context}
PROMPT;
    }

    private function buildCreativePrompt(Article $article): string
    {
        $context = json_encode($this->buildBaseContext($article), JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);

        return <<<PROMPT
You are a creative director for FlexLabs content marketing.

Create visual direction for the article.

Rules:
- Use Indonesian language.
- The visual should feel modern, educational, professional, and suitable for Indonesian audience.
- Include useful hero image concept, visual style, visual elements, image prompt, and alt text.
- Return only valid JSON that follows the schema.

Article context:
{$context}
PROMPT;
    }

    private function outlineSchema(): array
    {
        return [
            'type' => 'object',
            'properties' => [
                'title_options' => [
                    'type' => 'array',
                    'items' => ['type' => 'string'],
                ],
                'recommended_title' => ['type' => 'string'],
                'recommended_slug' => ['type' => 'string'],
                'excerpt' => ['type' => 'string'],
                'outline' => [
                    'type' => 'array',
                    'items' => [
                        'type' => 'object',
                        'properties' => [
                            'heading' => ['type' => 'string'],
                            'summary' => ['type' => 'string'],
                            'key_points' => [
                                'type' => 'array',
                                'items' => ['type' => 'string'],
                            ],
                        ],
                        'required' => ['heading', 'summary', 'key_points'],
                    ],
                ],
                'cta' => ['type' => 'string'],
                'seo_notes' => [
                    'type' => 'array',
                    'items' => ['type' => 'string'],
                ],
            ],
            'required' => [
                'title_options',
                'recommended_title',
                'recommended_slug',
                'excerpt',
                'outline',
                'cta',
                'seo_notes',
            ],
        ];
    }

    private function fullArticleSchema(): array
    {
        return [
            'type' => 'object',
            'properties' => [
                'title' => ['type' => 'string'],
                'excerpt' => ['type' => 'string'],
                'body_html' => ['type' => 'string'],
                'seo' => [
                    'type' => 'object',
                    'properties' => [
                        'seo_title' => ['type' => 'string'],
                        'meta_description' => ['type' => 'string'],
                        'og_title' => ['type' => 'string'],
                        'og_description' => ['type' => 'string'],
                        'tags' => [
                            'type' => 'array',
                            'items' => ['type' => 'string'],
                        ],
                    ],
                    'required' => [
                        'seo_title',
                        'meta_description',
                        'og_title',
                        'og_description',
                        'tags',
                    ],
                ],
                'creative' => [
                    'type' => 'object',
                    'properties' => [
                        'hero_image_concept' => ['type' => 'string'],
                        'visual_style' => ['type' => 'string'],
                        'hero_image_alt' => ['type' => 'string'],
                        'notes' => ['type' => 'string'],
                    ],
                    'required' => [
                        'hero_image_concept',
                        'visual_style',
                        'hero_image_alt',
                        'notes',
                    ],
                ],
                'social' => [
                    'type' => 'object',
                    'properties' => [
                        'instagram' => ['type' => 'string'],
                        'linkedin' => ['type' => 'string'],
                        'whatsapp' => ['type' => 'string'],
                    ],
                    'required' => [
                        'instagram',
                        'linkedin',
                        'whatsapp',
                    ],
                ],
            ],
            'required' => [
                'title',
                'excerpt',
                'body_html',
                'seo',
                'creative',
                'social',
            ],
        ];
    }

    private function seoSchema(): array
    {
        return [
            'type' => 'object',
            'properties' => [
                'seo_title' => ['type' => 'string'],
                'meta_description' => ['type' => 'string'],
                'meta_keywords' => [
                    'type' => 'array',
                    'items' => ['type' => 'string'],
                ],
                'og_title' => ['type' => 'string'],
                'og_description' => ['type' => 'string'],
                'tags' => [
                    'type' => 'array',
                    'items' => ['type' => 'string'],
                ],
            ],
            'required' => [
                'seo_title',
                'meta_description',
                'meta_keywords',
                'og_title',
                'og_description',
                'tags',
            ],
        ];
    }

    private function creativeSchema(): array
    {
        return [
            'type' => 'object',
            'properties' => [
                'hero_image_concept' => ['type' => 'string'],
                'visual_style' => ['type' => 'string'],
                'visual_elements' => [
                    'type' => 'array',
                    'items' => ['type' => 'string'],
                ],
                'hero_image_alt' => ['type' => 'string'],
                'image_prompt' => ['type' => 'string'],
                'notes' => ['type' => 'string'],
            ],
            'required' => [
                'hero_image_concept',
                'visual_style',
                'visual_elements',
                'hero_image_alt',
                'image_prompt',
                'notes',
            ],
        ];
    }
}