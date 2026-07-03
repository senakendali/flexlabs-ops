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
                    'temperature' => 0.35,
                    'topP' => 0.9,
                    'maxOutputTokens' => 1400,
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
            'scope' => 'topic_material',
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
                ?? Arr::get($frontendContext, 'program_id')
                ?? Arr::get($frontendContext, 'programId')
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
            ?? Arr::get($frontendContext, 'courseSlug')
            ?? Arr::get($frontendContext, 'program_slug')
            ?? Arr::get($frontendContext, 'programSlug');

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

        if (!$courseId) {
            $courseId = $this->resolveCourseId($topic, $module);
        }

        $course = $this->findRecord('programs', $courseId, $courseSlug);

        $relatedSubTopics = $this->getRelatedSubTopics($topicId, $subTopicId);

        return [
            'scope' => [
                'mode' => 'topic',
                'label' => 'Current topic scope',
                'description' => 'Jawaban dikunci ke topic aktif, bukan hanya sub topic aktif.',
            ],

            'course' => [
                'id' => $courseId,
                'slug' => $courseSlug,
                'title' => $this->firstFilled([
                    $this->recordValue($course, ['name', 'title']),
                    Arr::get($frontendContext, 'course_title'),
                    Arr::get($frontendContext, 'courseTitle'),
                    Arr::get($frontendContext, 'program_title'),
                    Arr::get($frontendContext, 'programTitle'),
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

            'current_sub_topic' => [
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

            'related_sub_topics' => $relatedSubTopics,
        ];
    }

    private function buildPrompt(string $question, array $materialContext): string
    {
        $course = $materialContext['course'] ?? [];
        $module = $materialContext['module'] ?? [];
        $topic = $materialContext['topic'] ?? [];
        $subTopic = $materialContext['current_sub_topic'] ?? [];
        $topicResources = $topic['resources'] ?? [];
        $relatedSubTopics = $materialContext['related_sub_topics'] ?? [];

        $relatedSubTopicText = $this->formatRelatedSubTopicsForPrompt($relatedSubTopics);

        return trim("
            You are Mr. Pioneer, FlexLabs learning assistant.

            IMPORTANT RULES:
            1. Use Indonesian language.
            2. Use a casual, clear, helpful teaching style for beginner students.
            3. The main scope is the CURRENT TOPIC, not only the current sub topic.
            4. Use the current sub topic as the student's current learning position, but you may explain using other sub topics inside the same topic.
            5. If the student's question is still related to the current topic/module, answer it helpfully even when the exact detail is not written in the context.
            6. When using general knowledge to clarify a related concept, say it as conceptual explanation, not as a fixed FlexLabs curriculum statement.
            7. If the question is completely outside the current topic/module, politely say that it is outside the current topic, then give a short bridge explaining what part might still be related.
            8. Do not invent FlexLabs curriculum details, schedules, assessment rules, links, or internal data that are not provided in the context.
            9. If code is needed, include short beginner-friendly examples only.
            10. End with one short follow-up suggestion related to the current topic.

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

            Current Sub Topic / Current Lesson:
            - Title: {$this->safeText($subTopic['title'] ?? '-')}
            - Description: {$this->safeText($subTopic['description'] ?? '-')}
            - Lesson Type: {$this->safeText($subTopic['lesson_type'] ?? '-')}
            - Video URL: {$this->safeText($subTopic['video_url'] ?? '-')}

            Other Sub Topics Inside The Same Topic:
            {$relatedSubTopicText}

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

        if ($id && is_numeric($id) && Schema::hasColumn($table, 'id')) {
            return $query->where('id', (int) $id)->first();
        }

        if ($slug && Schema::hasColumn($table, 'slug')) {
            return $query->where('slug', $slug)->first();
        }

        return null;
    }

    private function getRelatedSubTopics(mixed $topicId, mixed $currentSubTopicId = null): array
    {
        if (!$topicId || !is_numeric($topicId) || !Schema::hasTable('sub_topics')) {
            return [];
        }

        $query = DB::table('sub_topics')
            ->where('topic_id', (int) $topicId);

        if (Schema::hasColumn('sub_topics', 'is_active')) {
            $query->where('is_active', true);
        }

        if (Schema::hasColumn('sub_topics', 'sort_order')) {
            $query->orderBy('sort_order');
        } else {
            $query->orderBy('id');
        }

        return $query
            ->limit(30)
            ->get()
            ->map(function ($subTopic) use ($currentSubTopicId) {
                return [
                    'id' => $this->nullableId($subTopic->id ?? null),
                    'title' => $this->recordValue($subTopic, ['name', 'title']),
                    'description' => $this->recordValue($subTopic, [
                        'description',
                        'summary',
                        'content',
                        'learning_objectives',
                    ]),
                    'lesson_type' => $this->recordValue($subTopic, ['lesson_type']),
                    'is_current' => $currentSubTopicId
                        && (string) ($subTopic->id ?? '') === (string) $currentSubTopicId,
                ];
            })
            ->values()
            ->all();
    }

    private function formatRelatedSubTopicsForPrompt(array $relatedSubTopics): string
    {
        if (empty($relatedSubTopics)) {
            return '- No related sub topics were found from database.';
        }

        return collect($relatedSubTopics)
            ->map(function (array $subTopic, int $index) {
                $number = $index + 1;
                $currentMark = !empty($subTopic['is_current']) ? ' (current)' : '';
                $title = $this->safeText($subTopic['title'] ?? '-');
                $description = $this->safeText($subTopic['description'] ?? '-');
                $lessonType = $this->safeText($subTopic['lesson_type'] ?? '-');

                return "{$number}. {$title}{$currentMark}\n   - Lesson Type: {$lessonType}\n   - Description: {$description}";
            })
            ->implode("\n");
    }

    private function resolveCourseId(?object $topic = null, ?object $module = null): mixed
    {
        $directTopicCourseId = $this->firstFilled([
            $this->recordRawValue($topic, ['program_id', 'course_id']),
        ]);

        if ($directTopicCourseId !== '') {
            return $this->nullableId($directTopicCourseId);
        }

        $directModuleCourseId = $this->firstFilled([
            $this->recordRawValue($module, ['program_id', 'course_id']),
        ]);

        if ($directModuleCourseId !== '') {
            return $this->nullableId($directModuleCourseId);
        }

        $stageId = $this->nullableId($this->recordRawValue($module, ['stage_id']));

        if (!$stageId || !Schema::hasTable('stages')) {
            return null;
        }

        $stage = $this->findRecord('stages', $stageId);

        return $this->nullableId($this->recordRawValue($stage, ['program_id', 'course_id']));
    }

    private function recordValue(?object $record, array $keys): string
    {
        $value = $this->recordRawValue($record, $keys);

        return trim((string) $value);
    }

    private function recordRawValue(?object $record, array $keys): mixed
    {
        if (!$record) {
            return '';
        }

        foreach ($keys as $key) {
            if (property_exists($record, $key) && filled($record->{$key})) {
                return $record->{$key};
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
