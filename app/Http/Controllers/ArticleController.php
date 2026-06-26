<?php

namespace App\Http\Controllers;

use App\Models\Article;
use App\Services\ArticleAiGeneratorService;
use Illuminate\Contracts\View\View;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Throwable;


class ArticleController extends Controller
{
    /*
    |--------------------------------------------------------------------------
    | Listing
    |--------------------------------------------------------------------------
    */

    public function index(Request $request): View
    {
        $articles = Article::query()
            ->with(['creator', 'publisher'])
            ->search($request->string('search')->toString())
            ->forCategory($request->string('category')->toString())
            ->when($request->filled('status'), function ($query) use ($request) {
                $query->where('status', $request->string('status')->toString());
            })
            ->when($request->filled('source_type'), function ($query) use ($request) {
                $query->where('source_type', $request->string('source_type')->toString());
            })
            ->when($request->filled('article_type'), function ($query) use ($request) {
                $query->where('article_type', $request->string('article_type')->toString());
            })
            ->latest()
            ->paginate(10)
            ->withQueryString();

        return view('articles.index', [
            'articles' => $articles,
            'filters' => [
                'search' => $request->string('search')->toString(),
                'status' => $request->string('status')->toString(),
                'category' => $request->string('category')->toString(),
                'source_type' => $request->string('source_type')->toString(),
                'article_type' => $request->string('article_type')->toString(),
            ],
            'statuses' => Article::statusOptions(),
            'categories' => Article::categoryOptions(),
            'sourceTypes' => Article::sourceTypeOptions(),
            'articleTypes' => Article::articleTypeOptions(),
        ]);
    }

    /*
    |--------------------------------------------------------------------------
    | Create
    |--------------------------------------------------------------------------
    */

    public function create(Request $request): View
    {
        return view('articles.create', [
            'article' => new Article([
                'source_type' => Article::SOURCE_MANUAL,
                'status' => Article::STATUS_DRAFT,
                'language' => 'id',
                'tone' => Article::TONE_PROFESSIONAL_EDUCATIVE,
                'length_preset' => Article::LENGTH_MEDIUM,
            ]),
            'options' => $this->formOptions(),
            'prefill' => $this->buildPrefillFromRequest($request),
        ]);
    }

    public function store(Request $request, ArticleAiGeneratorService $aiGenerator): RedirectResponse|JsonResponse
    {
        $validated = $this->validateArticle($request);

        $validated['slug'] = $this->makeUniqueSlug(
            $validated['slug'] ?? $validated['title']
        );

        $validated['status'] = Article::STATUS_DRAFT;
        $validated['created_by'] = auth()->id();

        $article = Article::create($validated);

        /*
        |--------------------------------------------------------------------------
        | Create Flow
        |--------------------------------------------------------------------------
        | Default create article sekarang langsung generate semua kebutuhan konten.
        | Kalau nanti tombol Save Draft mengirim generate_ai=0, artikel hanya disimpan
        | sebagai draft dan diarahkan ke halaman edit.
        */
        $shouldGenerateAi = $request->boolean('generate_ai', true);

        if (! $shouldGenerateAi) {
            if ($this->shouldReturnJson($request)) {
                return response()->json([
                    'success' => true,
                    'message' => 'Draft artikel berhasil disimpan.',
                    'article' => $this->articlePayload($article),
                    'redirect_url' => route('articles.edit', $article),
                ], 201);
            }

            return redirect()
                ->route('articles.edit', $article)
                ->with('success', 'Draft artikel berhasil disimpan.');
        }

        try {
            $article->markAsGenerating();

            $generationResults = $this->generateInitialArticleContent(
                article: $article,
                aiGenerator: $aiGenerator
            );

            $article->refresh();
            $article->markAsReadyToCopy(auth()->id());
            $article->refresh();

            if ($this->shouldReturnJson($request)) {
                return response()->json([
                    'success' => true,
                    'message' => 'Artikel berhasil dibuat dan siap dicopy ke website FlexLabs.',
                    'article' => $this->articlePayload($article),
                    'generation_results' => $generationResults,
                    'redirect_url' => route('articles.show', $article),
                ], 201);
            }

            return redirect()
                ->route('articles.show', $article)
                ->with('success', 'Artikel berhasil dibuat dan siap dicopy ke website FlexLabs.');
        } catch (Throwable $exception) {
            $article->refresh();
            $article->markAsGenerationFailed();
            $article->refresh();

            $message = 'Artikel berhasil disimpan, tapi bantuan AI gagal diproses. Coba generate ulang dari halaman edit.';

            if ($this->shouldReturnJson($request)) {
                return response()->json([
                    'success' => true,
                    'warning' => true,
                    'message' => $message,
                    'error' => $exception->getMessage(),
                    'article' => $this->articlePayload($article),
                    'redirect_url' => route('articles.edit', $article),
                ], 201);
            }

            return redirect()
                ->route('articles.edit', $article)
                ->with('warning', $message);
        }
    }

    private function generateInitialArticleContent(
        Article $article,
        ArticleAiGeneratorService $aiGenerator
    ): array {
        $userId = auth()->id();

        $results = [];

        $results['outline'] = $aiGenerator->generateOutline(
            article: $article->fresh(),
            userId: $userId
        );

        $results['article'] = $aiGenerator->generateFullArticle(
            article: $article->fresh(),
            userId: $userId
        );

        $results['seo'] = $aiGenerator->improveSeo(
            article: $article->fresh(),
            userId: $userId
        );

        $results['creative'] = $aiGenerator->suggestCreative(
            article: $article->fresh(),
            userId: $userId
        );

        return $results;
    }

    /*
    |--------------------------------------------------------------------------
    | Show / Edit
    |--------------------------------------------------------------------------
    */

    public function show(Article $article): View
    {
        $article->load([
            'creator',
            'reviewer',
            'approver',
            'publisher',
            'generations' => fn ($query) => $query->latest()->limit(10),
        ]);

        return view('articles.show', [
            'article' => $article,
            'options' => $this->formOptions(),
        ]);
    }

    public function edit(Article $article): View|RedirectResponse
    {
        if (! $article->canBeEdited()) {
            return redirect()
                ->route('articles.show', $article)
                ->with('warning', 'Artikel yang sudah published atau archived tidak bisa diedit langsung.');
        }

        return view('articles.edit', [
            'article' => $article,
            'options' => $this->formOptions(),
            'prefill' => [],
        ]);
    }

    public function update(
        Request $request,
        Article $article,
        ArticleAiGeneratorService $aiGenerator
    ): RedirectResponse|JsonResponse {
        if (! $article->canRunAiGeneration()) {
            $message = 'Artikel ini belum bisa diproses karena sedang generating atau sudah diarsipkan.';

            if ($this->shouldReturnJson($request)) {
                return response()->json([
                    'success' => false,
                    'message' => $message,
                ], 422);
            }

            return redirect()
                ->back()
                ->with('error', $message);
        }

        $validated = $this->validateArticle($request, $article);

        $incomingSlug = $validated['slug'] ?? null;

        if (filled($incomingSlug)) {
            $normalizedIncomingSlug = str($incomingSlug)->slug('-')->toString();

            $validated['slug'] = $normalizedIncomingSlug === $article->slug
                ? $article->slug
                : $this->makeUniqueSlug($incomingSlug);
        } elseif (($validated['title'] ?? null) !== $article->title) {
            $validated['slug'] = $this->makeUniqueSlug($validated['title']);
        } else {
            $validated['slug'] = $article->slug;
        }

        $article->update($validated);
        $article->refresh();

        /*
        |--------------------------------------------------------------------------
        | Edit Flow
        |--------------------------------------------------------------------------
        | Edit article sekarang sama seperti create:
        | update brief -> generate ulang semua output AI -> redirect show.
        */
        $shouldGenerateAi = $request->boolean('generate_ai', true);

        if (! $shouldGenerateAi) {
            if ($this->shouldReturnJson($request)) {
                return response()->json([
                    'success' => true,
                    'message' => 'Brief artikel berhasil disimpan.',
                    'article' => $this->articlePayload($article),
                    'redirect_url' => route('articles.show', $article),
                ]);
            }

            return redirect()
                ->route('articles.show', $article)
                ->with('success', 'Brief artikel berhasil disimpan.');
        }

        try {
            $article->markAsGenerating();

            $generationResults = $this->generateInitialArticleContent(
                article: $article,
                aiGenerator: $aiGenerator
            );

            $article->refresh();
            $article->markAsReadyToCopy(auth()->id());
            $article->refresh();

            if ($this->shouldReturnJson($request)) {
                return response()->json([
                    'success' => true,
                    'message' => 'Artikel berhasil diperbarui dan hasil AI sudah digenerate ulang.',
                    'article' => $this->articlePayload($article),
                    'generation_results' => $generationResults,
                    'redirect_url' => route('articles.show', $article),
                ]);
            }

            return redirect()
                ->route('articles.show', $article)
                ->with('success', 'Artikel berhasil diperbarui dan hasil AI sudah digenerate ulang.');
        } catch (Throwable $exception) {
            $article->refresh();
            $article->markAsGenerationFailed();
            $article->refresh();

            $message = 'Brief artikel berhasil disimpan, tapi bantuan AI gagal diproses. Coba update ulang artikelnya.';

            if ($this->shouldReturnJson($request)) {
                return response()->json([
                    'success' => true,
                    'warning' => true,
                    'message' => $message,
                    'error' => $exception->getMessage(),
                    'article' => $this->articlePayload($article),
                    'redirect_url' => route('articles.edit', $article),
                ]);
            }

            return redirect()
                ->route('articles.edit', $article)
                ->with('warning', $message);
        }
    }

    /*
    |--------------------------------------------------------------------------
    | Generate From Workshop Preparation
    |--------------------------------------------------------------------------
    */

    public function createFromWorkshop(int $workshopId): View|RedirectResponse
    {
        $workshop = DB::table('workshops')->where('id', $workshopId)->first();

        if (! $workshop) {
            return redirect()
                ->route('articles.create')
                ->with('warning', 'Workshop tidak ditemukan.');
        }

        $workshopTitle = $workshop->title
            ?? $workshop->name
            ?? 'Workshop FlexLabs';

        $workshopDescription = $workshop->description
            ?? $workshop->short_description
            ?? null;

        $prefill = [
            'source_type' => Article::SOURCE_WORKSHOP,
            'source_id' => $workshopId,
            'article_type' => Article::TYPE_WORKSHOP_PROMO,
            'category' => 'workshop',
            'tone' => Article::TONE_PROFESSIONAL_EDUCATIVE,
            'target_audience' => 'Pemula yang ingin belajar software engineering melalui project nyata.',
            'language' => 'id',
            'length_preset' => Article::LENGTH_MEDIUM,
            'title' => $this->suggestWorkshopArticleTitle($workshopTitle),
            'primary_keyword' => Str::of($workshopTitle)
                ->lower()
                ->replace(['-', '|', ':'], ' ')
                ->squish()
                ->toString(),
            'main_angle' => 'Problem-solution: jelaskan masalah belajar coding yang terlalu teoritis, lalu arahkan ke workshop berbasis project.',
            'must_include' => trim(implode("\n", array_filter([
                'Jelaskan manfaat belajar lewat project nyata.',
                'Singgung bahwa AI bisa membantu proses belajar, tapi tetap perlu memahami logic.',
                'Arahkan pembaca untuk melihat jadwal workshop FlexLabs.',
                $workshopDescription ? 'Konteks workshop: ' . strip_tags($workshopDescription) : null,
            ]))),
            'avoid_points' => 'Jangan terlalu hard selling. Jangan menjanjikan hasil instan. Jangan pakai klaim berlebihan.',
            'brief_notes' => 'Draft ini dibuat dari data workshop dan siap dikembangkan menjadi artikel.',
        ];

        return view('articles.create', [
            'article' => new Article($prefill),
            'options' => $this->formOptions(),
            'prefill' => $prefill,
            'sourceWorkshop' => $workshop,
        ]);
    }

    /*
    |--------------------------------------------------------------------------
    | AI Generation Actions
    |--------------------------------------------------------------------------
    */

    public function generateOutline(
        Request $request,
        Article $article,
        ArticleAiGeneratorService $aiGenerator
    ): RedirectResponse|JsonResponse {
        return $this->runAiGeneration(
            request: $request,
            article: $article,
            successMessage: 'Outline artikel berhasil dibuat.',
            callback: fn () => $aiGenerator->generateOutline($article, auth()->id())
        );
    }

    public function generateFullArticle(
        Request $request,
        Article $article,
        ArticleAiGeneratorService $aiGenerator
    ): RedirectResponse|JsonResponse {
        return $this->runAiGeneration(
            request: $request,
            article: $article,
            successMessage: 'Draft artikel berhasil dibuat.',
            callback: fn () => $aiGenerator->generateFullArticle($article, auth()->id())
        );
    }

    public function improveSeo(
        Request $request,
        Article $article,
        ArticleAiGeneratorService $aiGenerator
    ): RedirectResponse|JsonResponse {
        return $this->runAiGeneration(
            request: $request,
            article: $article,
            successMessage: 'SEO artikel berhasil diperbarui.',
            callback: fn () => $aiGenerator->improveSeo($article, auth()->id())
        );
    }

    public function suggestCreative(
        Request $request,
        Article $article,
        ArticleAiGeneratorService $aiGenerator
    ): RedirectResponse|JsonResponse {
        return $this->runAiGeneration(
            request: $request,
            article: $article,
            successMessage: 'Arahan visual artikel berhasil dibuat.',
            callback: fn () => $aiGenerator->suggestCreative($article, auth()->id())
        );
    }

    private function runAiGeneration(
        Request $request,
        Article $article,
        string $successMessage,
        callable $callback
    ): RedirectResponse|JsonResponse {
        if (! $article->canBeEdited()) {
            return $this->errorResponse(
                $request,
                'Artikel yang sudah published atau archived tidak bisa diproses dengan bantuan penulisan.',
                route('articles.show', $article),
                409
            );
        }

        try {
            $result = $callback();

            $article->refresh();

            if ($this->shouldReturnJson($request)) {
                return response()->json([
                    'success' => true,
                    'message' => $successMessage,
                    'result' => $result['result'] ?? null,
                    'generation_id' => $result['generation_id'] ?? null,
                    'article' => $this->articlePayload($article),
                    'redirect_url' => route('articles.edit', $article),
                ]);
            }

            return redirect()
                ->route('articles.edit', $article)
                ->with('success', $successMessage);
        } catch (Throwable $exception) {
            return $this->errorResponse(
                $request,
                $exception->getMessage() ?: 'Bantuan penulisan gagal diproses.',
                route('articles.edit', $article),
                500
            );
        }
    }

    /*
    |--------------------------------------------------------------------------
    | Workflow Actions
    |--------------------------------------------------------------------------
    */

    public function markReadyForReview(Request $request, Article $article): RedirectResponse|JsonResponse
    {
        if (! in_array($article->status, [
            Article::STATUS_DRAFT,
            Article::STATUS_AI_GENERATED,
            Article::STATUS_EDITED,
        ], true)) {
            return $this->errorResponse(
                $request,
                'Status artikel tidak bisa diubah ke Ready for Review.',
                route('articles.show', $article),
                409
            );
        }

        $article->update([
            'status' => Article::STATUS_READY_FOR_REVIEW,
            'reviewed_by' => null,
            'reviewed_at' => null,
        ]);

        $article->refresh();

        return $this->successResponse(
            $request,
            'Artikel ditandai siap untuk direview.',
            route('articles.show', $article),
            $article
        );
    }

    public function approve(Request $request, Article $article): RedirectResponse|JsonResponse
    {
        if (! in_array($article->status, [
            Article::STATUS_READY_FOR_REVIEW,
            Article::STATUS_EDITED,
            Article::STATUS_AI_GENERATED,
        ], true)) {
            return $this->errorResponse(
                $request,
                'Artikel belum bisa di-approve.',
                route('articles.show', $article),
                409
            );
        }

        $article->update([
            'status' => Article::STATUS_APPROVED,
            'approved_by' => auth()->id(),
            'approved_at' => now(),
        ]);

        $article->refresh();

        return $this->successResponse(
            $request,
            'Artikel berhasil di-approve.',
            route('articles.show', $article),
            $article
        );
    }

    public function publish(Request $request, Article $article): RedirectResponse|JsonResponse
    {
        if (! $article->canBePublished()) {
            return $this->errorResponse(
                $request,
                'Artikel belum bisa dipublish.',
                route('articles.show', $article),
                409
            );
        }

        $article->update([
            'status' => Article::STATUS_PUBLISHED,
            'published_by' => auth()->id(),
            'published_at' => now(),
            'scheduled_publish_at' => null,
        ]);

        $article->refresh();

        return $this->successResponse(
            $request,
            'Artikel berhasil dipublish.',
            route('articles.show', $article),
            $article
        );
    }

    public function schedule(Request $request, Article $article): RedirectResponse|JsonResponse
    {
        if (! $article->canBePublished()) {
            return $this->errorResponse(
                $request,
                'Artikel belum bisa dijadwalkan.',
                route('articles.show', $article),
                409
            );
        }

        $validated = $request->validate([
            'scheduled_publish_at' => ['required', 'date', 'after:now'],
        ]);

        $article->update([
            'status' => Article::STATUS_SCHEDULED,
            'scheduled_publish_at' => $validated['scheduled_publish_at'],
        ]);

        $article->refresh();

        return $this->successResponse(
            $request,
            'Artikel berhasil dijadwalkan.',
            route('articles.show', $article),
            $article
        );
    }

    public function archive(Request $request, Article $article): RedirectResponse|JsonResponse
    {
        if ($article->status === Article::STATUS_ARCHIVED) {
            return $this->successResponse(
                $request,
                'Artikel sudah dalam status archived.',
                route('articles.index'),
                $article
            );
        }

        $article->update([
            'status' => Article::STATUS_ARCHIVED,
        ]);

        $article->refresh();

        return $this->successResponse(
            $request,
            'Artikel berhasil diarsipkan.',
            route('articles.index'),
            $article
        );
    }

    public function destroy(Request $request, Article $article): RedirectResponse|JsonResponse
    {
        if ($article->status === Article::STATUS_PUBLISHED) {
            return $this->errorResponse(
                $request,
                'Artikel published tidak bisa dihapus langsung. Archive dulu kalau ingin disembunyikan.',
                route('articles.show', $article),
                409
            );
        }

        $articleId = $article->id;
        $articleTitle = $article->title;

        $article->delete();

        if ($this->shouldReturnJson($request)) {
            return response()->json([
                'success' => true,
                'message' => 'Artikel berhasil dihapus.',
                'article' => [
                    'id' => $articleId,
                    'title' => $articleTitle,
                    'deleted' => true,
                ],
                'redirect_url' => route('articles.index'),
            ]);
        }

        return redirect()
            ->route('articles.index')
            ->with('success', 'Artikel berhasil dihapus.');
    }

    /*
    |--------------------------------------------------------------------------
    | Private Helpers - Options
    |--------------------------------------------------------------------------
    */

    private function formOptions(): array
    {
        return [
            'sourceTypes' => Article::sourceTypeOptions(),
            'articleTypes' => Article::articleTypeOptions(),
            'statuses' => Article::statusOptions(),
            'tones' => Article::toneOptions(),
            'lengths' => Article::lengthOptions(),
            'categories' => Article::categoryOptions(),

            'defaultTone' => Article::TONE_PROFESSIONAL_EDUCATIVE,
            'defaultLength' => Article::LENGTH_MEDIUM,
            'defaultLanguage' => 'id',
        ];
    }

    private function buildPrefillFromRequest(Request $request): array
    {
        return [
            'source_type' => $request->string('source_type', Article::SOURCE_MANUAL)->toString(),
            'source_id' => $request->filled('source_id') ? (int) $request->input('source_id') : null,
            'article_type' => $request->string('article_type')->toString(),
            'category' => $request->string('category')->toString(),
            'tone' => $request->string('tone', Article::TONE_PROFESSIONAL_EDUCATIVE)->toString(),
            'target_audience' => $request->string('target_audience')->toString(),
            'primary_keyword' => $request->string('primary_keyword')->toString(),
        ];
    }

    /*
    |--------------------------------------------------------------------------
    | Private Helpers - Validation
    |--------------------------------------------------------------------------
    */

    private function validateArticle(Request $request, ?Article $article = null): array
    {
        $validSourceTypes = array_keys(Article::sourceTypeOptions());
        $validArticleTypes = array_keys(Article::articleTypeOptions());
        $validTones = array_keys(Article::toneOptions());
        $validLengths = array_keys(Article::lengthOptions());
        $validCategories = array_keys(Article::categoryOptions());

        $validated = $request->validate([
            'source_type' => ['nullable', 'string', Rule::in($validSourceTypes)],
            'source_id' => ['nullable', 'integer', 'min:1'],

            'article_type' => ['nullable', 'string', Rule::in($validArticleTypes)],
            'category' => ['nullable', 'string', Rule::in($validCategories)],
            'tone' => ['nullable', 'string', Rule::in($validTones)],
            'target_audience' => ['nullable', 'string', 'max:160'],
            'language' => ['nullable', 'string', 'max:20'],
            'length_preset' => ['nullable', 'string', Rule::in($validLengths)],

            'primary_keyword' => ['nullable', 'string', 'max:255'],
            'secondary_keywords' => ['nullable', 'array'],
            'secondary_keywords.*' => ['nullable', 'string', 'max:120'],

            'main_angle' => ['nullable', 'string'],
            'must_include' => ['nullable', 'string'],
            'avoid_points' => ['nullable', 'string'],
            'brief_notes' => ['nullable', 'string'],

            'title' => ['required', 'string', 'max:255'],
            'slug' => [
                'nullable',
                'string',
                'max:255',
                Rule::unique('articles', 'slug')->ignore($article?->id),
            ],
            'excerpt' => ['nullable', 'string'],
            'body_html' => ['nullable', 'string'],

            'seo_title' => ['nullable', 'string', 'max:255'],
            'meta_description' => ['nullable', 'string'],
            'meta_keywords' => ['nullable', 'array'],
            'meta_keywords.*' => ['nullable', 'string', 'max:120'],

            'og_title' => ['nullable', 'string', 'max:255'],
            'og_description' => ['nullable', 'string'],
            'og_image_url' => ['nullable', 'string', 'max:255'],

            'canonical_url' => ['nullable', 'string', 'max:255'],
            'tags' => ['nullable', 'array'],
            'tags.*' => ['nullable', 'string', 'max:80'],

            'hero_image_url' => ['nullable', 'string', 'max:255'],
            'hero_image_alt' => ['nullable', 'string', 'max:255'],
            'creative_brief' => ['nullable', 'array'],

            'social_captions' => ['nullable', 'array'],

            'ai_brief' => ['nullable', 'array'],
            'ai_outline' => ['nullable', 'array'],

            'scheduled_publish_at' => ['nullable', 'date'],
            'external_url' => ['nullable', 'string', 'max:255'],
            'external_post_id' => ['nullable', 'string', 'max:255'],
        ]);

        return $this->normalizeArticlePayload($validated);
    }

    private function normalizeArticlePayload(array $payload): array
    {
        $payload['source_type'] = $payload['source_type'] ?? Article::SOURCE_MANUAL;
        $payload['language'] = $payload['language'] ?? 'id';
        $payload['tone'] = $payload['tone'] ?? Article::TONE_PROFESSIONAL_EDUCATIVE;
        $payload['length_preset'] = $payload['length_preset'] ?? Article::LENGTH_MEDIUM;

        $arrayFields = [
            'secondary_keywords',
            'meta_keywords',
            'tags',
            'creative_brief',
            'social_captions',
            'ai_brief',
            'ai_outline',
        ];

        foreach ($arrayFields as $field) {
            if (! array_key_exists($field, $payload)) {
                continue;
            }

            if (in_array($field, ['secondary_keywords', 'meta_keywords', 'tags'], true)) {
                $payload[$field] = $this->cleanStringArray($payload[$field]);
                continue;
            }

            $payload[$field] = is_array($payload[$field]) ? $payload[$field] : null;
        }

        return $payload;
    }

    private function cleanStringArray(?array $items): array
    {
        if (! $items) {
            return [];
        }

        return collect($items)
            ->map(fn ($item) => is_string($item) ? trim($item) : null)
            ->filter()
            ->unique()
            ->values()
            ->all();
    }

    /*
    |--------------------------------------------------------------------------
    | Private Helpers - Slug / Workshop
    |--------------------------------------------------------------------------
    */

    private function makeUniqueSlug(string $value, ?int $ignoreId = null): string
    {
        $baseSlug = Str::slug($value);

        if ($baseSlug === '') {
            $baseSlug = 'artikel-flexlabs';
        }

        $slug = $baseSlug;
        $counter = 2;

        while (
            Article::query()
                ->where('slug', $slug)
                ->when($ignoreId, fn ($query) => $query->where('id', '!=', $ignoreId))
                ->exists()
        ) {
            $slug = "{$baseSlug}-{$counter}";
            $counter++;
        }

        return $slug;
    }

    private function suggestWorkshopArticleTitle(string $workshopTitle): string
    {
        $cleanTitle = Str::of($workshopTitle)
            ->replaceMatches('/\s+/', ' ')
            ->trim()
            ->toString();

        return "Kenapa {$cleanTitle} Cocok untuk Belajar Lewat Project Nyata?";
    }

    /*
    |--------------------------------------------------------------------------
    | Private Helpers - Async / JSON Response
    |--------------------------------------------------------------------------
    */

    private function shouldReturnJson(Request $request): bool
    {
        return $request->expectsJson()
            || $request->ajax()
            || $request->wantsJson();
    }

    private function articlePayload(Article $article): array
    {
        return [
            'id' => $article->id,
            'source_type' => $article->source_type,
            'source_type_label' => $article->source_type_label,
            'source_id' => $article->source_id,

            'article_type' => $article->article_type,
            'article_type_label' => $article->article_type_label,

            'category' => $article->category,
            'tone' => $article->tone,
            'tone_label' => $article->tone_label,

            'title' => $article->title,
            'slug' => $article->slug,
            'excerpt' => $article->excerpt,
            'body_html' => $article->body_html,

            'primary_keyword' => $article->primary_keyword,
            'secondary_keywords' => $article->secondary_keywords,

            'status' => $article->status,
            'status_label' => $article->status_label,

            'seo_title' => $article->seo_title,
            'meta_description' => $article->meta_description,
            'meta_keywords' => $article->meta_keywords,
            'og_title' => $article->og_title,
            'og_description' => $article->og_description,
            'tags' => $article->tags,

            'hero_image_url' => $article->hero_image_url,
            'hero_image_alt' => $article->hero_image_alt,
            'creative_brief' => $article->creative_brief,
            'social_captions' => $article->social_captions,

            'ai_outline' => $article->ai_outline,

            'scheduled_publish_at' => optional($article->scheduled_publish_at)->toDateTimeString(),
            'published_at' => optional($article->published_at)->toDateTimeString(),

            'can_be_edited' => $article->canBeEdited(),
            'can_be_published' => $article->canBePublished(),

            'edit_url' => route('articles.edit', $article),
            'show_url' => route('articles.show', $article),
        ];
    }

    private function successResponse(
        Request $request,
        string $message,
        string $redirectUrl,
        ?Article $article = null
    ): RedirectResponse|JsonResponse {
        if ($this->shouldReturnJson($request)) {
            return response()->json([
                'success' => true,
                'message' => $message,
                'article' => $article ? $this->articlePayload($article) : null,
                'redirect_url' => $redirectUrl,
            ]);
        }

        return redirect($redirectUrl)->with('success', $message);
    }

    private function errorResponse(
        Request $request,
        string $message,
        string $redirectUrl,
        int $statusCode = 422
    ): RedirectResponse|JsonResponse {
        if ($this->shouldReturnJson($request)) {
            return response()->json([
                'success' => false,
                'message' => $message,
                'redirect_url' => $redirectUrl,
            ], $statusCode);
        }

        return back()->with('warning', $message);
    }
}