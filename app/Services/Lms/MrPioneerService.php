<?php

namespace App\Services\Lms;

use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;
use Symfony\Component\HttpKernel\Exception\HttpException;

class MrPioneerService
{
    public function answer(?Authenticatable $user, array $payload): array
    {
        $question = trim((string) Arr::get($payload, 'question', ''));
        $context = Arr::get($payload, 'context', []);

        if ($question === '') {
            throw new HttpException(422, 'Pertanyaan tidak boleh kosong.');
        }

        $apiKey = config('services.gemini.api_key');
        $model = config('services.gemini.model', 'gemini-2.5-flash');
        $endpoint = rtrim((string) config('services.gemini.endpoint'), '/');
        $timeout = (int) config('services.gemini.timeout', 30);

        if (!$apiKey) {
            throw new HttpException(503, 'Gemini API key belum diset. Tambahkan GEMINI_API_KEY di file .env.');
        }

        $materialContext = $this->buildMaterialContext($context);
        $prompt = $this->buildPrompt($question, $materialContext);

        $url = "{$endpoint}/models/{$model}:generateContent";

        $response = Http::timeout($timeout)
            ->withHeaders([
                'x-goog-api-key' => $apiKey,
                'Content-Type' => 'application/json',
            ])
            ->post($url, [
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
                    'temperature' => 0.25,
                    'topP' => 0.9,
                    'maxOutputTokens' => 1200,
                ],
            ]);

        if ($response->failed()) {
            $message = $response->json('error.message')
                ?: 'Gemini API belum bisa memproses pertanyaan ini.';

            throw new HttpException(502, $message);
        }

        $answer = $this->extractGeminiText($response->json());

        if (!$answer) {
            throw new HttpException(502, 'Mr. Pioneer tidak mengembalikan jawaban.');
        }

        return [
            'answer' => $answer,
            'question' => $question,
            'scope' => 'material_only',
            'provider' => 'gemini',
            'model' => $model,
            'can_save_to_notes' => true,
            'context' => $materialContext,
        ];
    }

    private function buildMaterialContext(array $frontendContext): array
    {
        $courseId = $this->nullableId(
            Arr::get($frontendContext, 'course_id')
                ?? Arr::get($frontendContext, 'courseId')
        );

        $moduleId = $this->nullableId(
            Arr::get($frontendContext, 'module_id')
                ?? Arr::get($frontendContext, 'moduleId')
        );

        $topicId = $this->nullableId(
            Arr::get($frontendContext, 'topic_id')
                ?? Arr::get($frontendContext, 'topicId')
        );

        $subTopicId = $this->nullableId(
            Arr::get($frontendContext, 'sub_topic_id')
                ?? Arr::get($frontendContext, 'subTopicId')
                ?? Arr::get($frontendContext, 'lesson_id')
                ?? Arr::get($frontendContext, 'lessonId')
        );

        $courseSlug = Arr::get($frontendContext, 'course_slug')
            ?? Arr::get($frontendContext, 'courseSlug');

        $topicSlug = Arr::get($frontendContext, 'topic_slug')
            ?? Arr::get($frontendContext, 'topicSlug');

        $subTopicSlug = Arr::get($frontendContext, 'sub_topic_slug')
            ?? Arr::get($frontendContext, 'subTopicSlug')
            ?? Arr::get($frontendContext, 'lesson_slug')
            ?? Arr::get($frontendContext, 'lessonSlug');

        $subTopic = $this->findRecord('sub_topics', $subTopicId, $subTopicSlug);

        if ($subTopic && !$topicId) {
            $topicId = $this->nullableId($subTopic->topic_id ?? null);
        }

        $topic = $this->findRecord('topics', $topicId, $topicSlug);

        if ($topic && !$moduleId) {
            $moduleId = $this->nullableId($topic->module_id ?? null);
        }

        $module = $this->findRecord('modules', $moduleId);

        $course = $this->findRecord('programs', $courseId, $courseSlug);

        return [
            'course' => [
                'id' => $courseId,
                'slug' => $courseSlug,
                'title' => $this->firstFilled([
                    $this->recordValue($course, ['name', 'title']),
                    Arr::get($frontendContext, 'course_title'),
                    Arr::get($frontendContext, 'courseTitle'),
                    'Course',
                ]),
                'description' => $this->recordValue($course, ['description', 'summary']),
            ],

            'module' => [
                'id' => $moduleId,
                'title' => $this->firstFilled([
                    $this->recordValue($module, ['name', 'title']),
                    Arr::get($frontendContext, 'module_title'),
                    Arr::get($frontendContext, 'moduleTitle'),
                    '-',
                ]),
                'description' => $this->recordValue($module, ['description', 'summary']),
            ],

            'topic' => [
                'id' => $topicId,
                'slug' => $topicSlug,
                'title' => $this->firstFilled([
                    $this->recordValue($topic, ['name', 'title']),
                    Arr::get($frontendContext, 'topic_title'),
                    Arr::get($frontendContext, 'topicTitle'),
                    '-',
                ]),
                'description' => $this->recordValue($topic, [
                    'description',
                    'summary',
                    'practice_brief',
                ]),
                'resources' => [
                    'slide_url' => $this->recordValue($topic, ['slide_url']),
                    'starter_code_url' => $this->recordValue($topic, ['starter_code_url']),
                    'supporting_file_url' => $this->recordValue($topic, ['supporting_file_url']),
                    'external_reference_url' => $this->recordValue($topic, ['external_reference_url']),
                    'practice_brief' => $this->recordValue($topic, ['practice_brief']),
                ],
            ],

            'sub_topic' => [
                'id' => $subTopicId,
                'slug' => $subTopicSlug,
                'title' => $this->firstFilled([
                    $this->recordValue($subTopic, ['name', 'title']),
                    Arr::get($frontendContext, 'sub_topic_title'),
                    Arr::get($frontendContext, 'subTopicTitle'),
                    Arr::get($frontendContext, 'lesson_title'),
                    Arr::get($frontendContext, 'lessonTitle'),
                    '-',
                ]),
                'description' => $this->recordValue($subTopic, [
                    'description',
                    'summary',
                    'content',
                    'learning_objectives',
                ]),
                'lesson_type' => $this->recordValue($subTopic, ['lesson_type']),
                'video_url' => $this->recordValue($subTopic, ['video_url']),
            ],
        ];
    }

    private function buildPrompt(string $question, array $materialContext): string
    {
        $course = $materialContext['course'] ?? [];
        $module = $materialContext['module'] ?? [];
        $topic = $materialContext['topic'] ?? [];
        $subTopic = $materialContext['sub_topic'] ?? [];

        $topicResources = $topic['resources'] ?? [];

        return trim("
            You are Mr. Pioneer, FlexLabs learning assistant.

            IMPORTANT RULES:
            1. Answer only from the current learning material context.
            2. The scope is strictly: current course, module, topic, and sub topic.
            3. If the student's question is outside this material, politely say that it is outside the current material scope.
            4. Do not invent curriculum details that are not provided in the context.
            5. Use Indonesian language.
            6. Use a casual, clear, helpful teaching style.
            7. Keep the answer practical and easy for beginner students.
            8. If code is needed, only include short examples that directly explain the current material.
            9. End with one short follow-up suggestion related to this material.

            CURRENT MATERIAL CONTEXT:

            Course:
            - Title: {$this->safeText($course['title'] ?? '-')}
            - Description: {$this->safeText($course['description'] ?? '-')}

            Module:
            - Title: {$this->safeText($module['title'] ?? '-')}
            - Description: {$this->safeText($module['description'] ?? '-')}

            Topic:
            - Title: {$this->safeText($topic['title'] ?? '-')}
            - Description: {$this->safeText($topic['description'] ?? '-')}
            - Practice Brief: {$this->safeText($topicResources['practice_brief'] ?? '-')}
            - Slide URL: {$this->safeText($topicResources['slide_url'] ?? '-')}
            - Starter Code URL: {$this->safeText($topicResources['starter_code_url'] ?? '-')}
            - Supporting File URL: {$this->safeText($topicResources['supporting_file_url'] ?? '-')}
            - External Reference URL: {$this->safeText($topicResources['external_reference_url'] ?? '-')}

            Sub Topic:
            - Title: {$this->safeText($subTopic['title'] ?? '-')}
            - Description: {$this->safeText($subTopic['description'] ?? '-')}
            - Lesson Type: {$this->safeText($subTopic['lesson_type'] ?? '-')}
            - Video URL: {$this->safeText($subTopic['video_url'] ?? '-')}

            Student Question:
            {$this->safeText($question)}
        ");
    }

    private function extractGeminiText(array $response): string
    {
        $parts = Arr::get($response, 'candidates.0.content.parts', []);

        if (!is_array($parts)) {
            return '';
        }

        $text = collect($parts)
            ->map(fn ($part) => is_array($part) ? ($part['text'] ?? '') : '')
            ->filter()
            ->implode("\n");

        return trim($text);
    }

    private function findRecord(string $table, mixed $id = null, ?string $slug = null): ?object
    {
        if (!Schema::hasTable($table)) {
            return null;
        }

        $query = DB::table($table);

        if ($id && Schema::hasColumn($table, 'id')) {
            return $query->where('id', $id)->first();
        }

        if ($slug && Schema::hasColumn($table, 'slug')) {
            return $query->where('slug', $slug)->first();
        }

        return null;
    }

    private function recordValue(?object $record, array $keys): string
    {
        if (!$record) {
            return '';
        }

        foreach ($keys as $key) {
            if (property_exists($record, $key) && filled($record->{$key})) {
                return trim((string) $record->{$key});
            }
        }

        return '';
    }

    private function firstFilled(array $values): string
    {
        foreach ($values as $value) {
            if (filled($value)) {
                return trim((string) $value);
            }
        }

        return '';
    }

    private function nullableId(mixed $value): mixed
    {
        if ($value === null || $value === '') {
            return null;
        }

        if (is_numeric($value)) {
            return (int) $value;
        }

        return $value;
    }

    private function safeText(mixed $value): string
    {
        $text = trim((string) $value);

        if ($text === '') {
            return '-';
        }

        return Str::limit($text, 6000, '...');
    }
}