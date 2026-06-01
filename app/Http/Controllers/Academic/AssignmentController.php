<?php

namespace App\Http\Controllers\Academic;

use App\Http\Controllers\Controller;
use App\Models\Assignment;
use App\Models\SubTopic;
use App\Models\Topic;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;
use Throwable;

class AssignmentController extends Controller
{
    public function index(Request $request): View
    {
        $search = trim((string) $request->input('search'));
        $topicId = $request->input('topic_id');
        $subTopicId = $request->input('sub_topic_id');
        $type = $request->input('assignment_type');
        $status = $request->input('status');

        $assignments = Assignment::query()
            ->with([
                'topic:id,module_id,name',
                'topic.module:id,program_stage_id,name',
                'topic.module.stage:id,program_id,name',
                'topic.module.stage.program:id,name',
                'subTopic:id,topic_id,name',
                'createdBy:id,name',
                'updatedBy:id,name',
            ])
            ->withCount([
                'batchAssignments',
                'submissions',
            ])
            ->when($search !== '', function ($query) use ($search) {
                $query->where(function ($q) use ($search) {
                    $q->where('title', 'like', '%' . $search . '%')
                        ->orWhere('instruction', 'like', '%' . $search . '%')
                        ->orWhereHas('topic', function ($topicQuery) use ($search) {
                            $topicQuery->where('name', 'like', '%' . $search . '%');
                        })
                        ->orWhereHas('subTopic', function ($subTopicQuery) use ($search) {
                            $subTopicQuery->where('name', 'like', '%' . $search . '%');
                        });
                });
            })
            ->when($topicId, function ($query) use ($topicId) {
                $query->where('topic_id', $topicId);
            })
            ->when($subTopicId, function ($query) use ($subTopicId) {
                $query->where('sub_topic_id', $subTopicId);
            })
            ->when($type, function ($query) use ($type) {
                $query->where('assignment_type', $type);
            })
            ->when($status, function ($query) use ($status) {
                $query->where('status', $status);
            })
            ->orderBy('sort_order')
            ->orderByDesc('id')
            ->paginate(15)
            ->withQueryString();

        $topics = Topic::query()
            ->with([
                'module:id,program_stage_id,name',
                'module.stage:id,program_id,name',
                'module.stage.program:id,name',
            ])
            ->orderBy('sort_order')
            ->orderBy('name')
            ->get();

        $subTopics = SubTopic::query()
            ->with([
                'topic:id,module_id,name',
                'topic.module:id,program_stage_id,name',
            ])
            ->orderBy('sort_order')
            ->orderBy('name')
            ->get();

        $stats = [
            'total' => Assignment::count(),
            'published' => Assignment::where('status', 'published')->count(),
            'draft' => Assignment::where('status', 'draft')->count(),
            'active' => Assignment::where('is_active', true)->count(),
        ];

        $assignmentTypes = $this->assignmentTypes();
        $statuses = $this->statuses();

        return view('academic.assignments.index', [
            'assignments' => $assignments,
            'topics' => $topics,
            'subTopics' => $subTopics,
            'stats' => $stats,
            'assignmentTypes' => $assignmentTypes,
            'statuses' => $statuses,
        ]);
    }

    public function store(Request $request): JsonResponse
    {
        try {
            $validated = $this->validateAssignment($request);
            $validated = $this->normalizeAssignmentTarget($validated);

            $assignment = DB::transaction(function () use ($validated) {
                return Assignment::create([
                    'topic_id' => $validated['topic_id'],
                    'sub_topic_id' => $validated['sub_topic_id'] ?? null,

                    'title' => $validated['title'],
                    'slug' => $this->generateUniqueSlug($validated['title']),

                    'assignment_type' => $validated['assignment_type'],
                    'instruction' => $this->normalizeInstructionHtml($validated['instruction'] ?? null),
                    'attachment_url' => $validated['attachment_url'] ?? null,
                    'starter_file_url' => $validated['starter_file_url'] ?? null,
                    'reference_url' => $validated['reference_url'] ?? null,

                    'estimated_minutes' => $validated['estimated_minutes'] ?? null,
                    'max_score' => $validated['max_score'] ?? 100,

                    'is_required' => (bool) ($validated['is_required'] ?? true),
                    'sort_order' => $validated['sort_order'] ?? 1,

                    'status' => $validated['status'] ?? 'draft',
                    'is_active' => (bool) ($validated['is_active'] ?? true),

                    'created_by' => auth()->id(),
                    'updated_by' => auth()->id(),
                ]);
            });

            return response()->json([
                'success' => true,
                'message' => 'Assignment berhasil ditambahkan.',
                'data' => [
                    'id' => $assignment->id,
                    'title' => $assignment->title,
                ],
            ]);
        } catch (ValidationException $e) {
            throw $e;
        } catch (Throwable $e) {
            return $this->errorResponse('Gagal menambahkan assignment.', $e);
        }
    }

    public function update(Request $request, Assignment $assignment): JsonResponse
    {
        try {
            $validated = $this->validateAssignment($request);
            $validated = $this->normalizeAssignmentTarget($validated);

            DB::transaction(function () use ($assignment, $validated) {
                $assignment->update([
                    'topic_id' => $validated['topic_id'],
                    'sub_topic_id' => $validated['sub_topic_id'] ?? null,

                    'title' => $validated['title'],
                    'slug' => $this->generateUniqueSlug($validated['title'], $assignment->id),

                    'assignment_type' => $validated['assignment_type'],
                    'instruction' => $this->normalizeInstructionHtml($validated['instruction'] ?? null),
                    'attachment_url' => $validated['attachment_url'] ?? null,
                    'starter_file_url' => $validated['starter_file_url'] ?? null,
                    'reference_url' => $validated['reference_url'] ?? null,

                    'estimated_minutes' => $validated['estimated_minutes'] ?? null,
                    'max_score' => $validated['max_score'] ?? $assignment->max_score ?? 100,

                    'is_required' => (bool) ($validated['is_required'] ?? true),
                    'sort_order' => $validated['sort_order'] ?? $assignment->sort_order ?? 1,

                    'status' => $validated['status'] ?? $assignment->status ?? 'draft',
                    'is_active' => (bool) ($validated['is_active'] ?? true),

                    'updated_by' => auth()->id(),
                ]);
            });

            return response()->json([
                'success' => true,
                'message' => 'Assignment berhasil diperbarui.',
                'data' => [
                    'id' => $assignment->id,
                    'title' => $assignment->fresh()->title,
                ],
            ]);
        } catch (ValidationException $e) {
            throw $e;
        } catch (Throwable $e) {
            return $this->errorResponse('Gagal memperbarui assignment.', $e);
        }
    }

    public function destroy(Assignment $assignment): JsonResponse
    {
        try {
            DB::transaction(function () use ($assignment) {
                $assignment->delete();
            });

            return response()->json([
                'success' => true,
                'message' => 'Assignment berhasil dihapus.',
            ]);
        } catch (Throwable $e) {
            return $this->errorResponse('Gagal menghapus assignment.', $e);
        }
    }

    private function validateAssignment(Request $request): array
    {
        return $request->validate([
            'topic_id' => [
                'nullable',
                'exists:topics,id',
            ],

            'sub_topic_id' => [
                'nullable',
                'exists:sub_topics,id',
            ],

            'title' => [
                'required',
                'string',
                'max:255',
            ],

            'assignment_type' => [
                'required',
                Rule::in(array_keys($this->assignmentTypes())),
            ],

            'instruction' => [
                'nullable',
                'string',
            ],

            'attachment_url' => [
                'nullable',
                'url',
            ],

            'starter_file_url' => [
                'nullable',
                'url',
            ],

            'reference_url' => [
                'nullable',
                'url',
            ],

            'estimated_minutes' => [
                'nullable',
                'integer',
                'min:1',
                'max:9999',
            ],

            'max_score' => [
                'nullable',
                'integer',
                'min:1',
                'max:999',
            ],

            'is_required' => [
                'required',
                'boolean',
            ],

            'sort_order' => [
                'nullable',
                'integer',
                'min:1',
            ],

            'status' => [
                'required',
                Rule::in(array_keys($this->statuses())),
            ],

            'is_active' => [
                'required',
                'boolean',
            ],
        ]);
    }

    private function normalizeAssignmentTarget(array $validated): array
    {
        $topicId = $validated['topic_id'] ?? null;
        $subTopicId = $validated['sub_topic_id'] ?? null;

        if (!$topicId && !$subTopicId) {
            throw ValidationException::withMessages([
                'topic_id' => ['Pilih topic atau sub topic untuk assignment ini.'],
            ]);
        }

        if ($subTopicId) {
            $subTopic = SubTopic::query()
                ->select(['id', 'topic_id'])
                ->findOrFail($subTopicId);

            if ($topicId && (int) $topicId !== (int) $subTopic->topic_id) {
                throw ValidationException::withMessages([
                    'sub_topic_id' => ['Sub topic yang dipilih tidak sesuai dengan topic.'],
                ]);
            }

            $validated['topic_id'] = $subTopic->topic_id;
        }

        return $validated;
    }

    private function normalizeInstructionHtml(?string $instruction): ?string
    {
        if ($instruction === null) {
            return null;
        }

        $instruction = trim($instruction);

        if ($instruction === '') {
            return null;
        }

        $instruction = str_replace(["\r\n", "\r"], "\n", $instruction);

        /*
        |--------------------------------------------------------------------------
        | Kalau HTML Quill terkirim dalam bentuk escaped:
        | &lt;p&gt;Instruksi&lt;/p&gt;
        |--------------------------------------------------------------------------
        */
        if ($this->looksLikeEscapedHtml($instruction)) {
            $instruction = html_entity_decode($instruction, ENT_QUOTES | ENT_HTML5, 'UTF-8');
            $instruction = trim($instruction);
        }

        if ($this->isEmptyQuillHtml($instruction)) {
            return null;
        }

        /*
        |--------------------------------------------------------------------------
        | Kalau sudah HTML, jangan di-convert ulang jadi paragraph.
        | Cukup dibersihkan dari tag/attribute yang berbahaya.
        |--------------------------------------------------------------------------
        */
        if ($this->containsHtmlTag($instruction)) {
            $html = $this->cleanInstructionHtml($instruction);

            return $this->isEmptyInstructionHtml($html)
                ? null
                : $html;
        }

        /*
        |--------------------------------------------------------------------------
        | Fallback kalau frontend masih kirim plain text.
        | Double enter => paragraph baru.
        | Single enter => <br>, bukan paragraph baru.
        |--------------------------------------------------------------------------
        */
        $html = $this->plainTextToInstructionHtml($instruction);

        return $this->isEmptyInstructionHtml($html)
            ? null
            : $html;
    }

    private function looksLikeEscapedHtml(string $value): bool
    {
        return (bool) preg_match(
            '/&lt;\s*\/?\s*(p|br|h1|h2|h3|h4|h5|h6|ul|ol|li|strong|b|em|i|u|s|a|blockquote|pre|code|span|div)\b/i',
            $value
        );
    }

    private function containsHtmlTag(string $value): bool
    {
        return (bool) preg_match('/<\s*\/?\s*[a-z][^>]*>/i', $value);
    }

    private function isEmptyQuillHtml(string $html): bool
    {
        $normalized = strtolower(
            preg_replace('/\s+/', '', str_replace('&nbsp;', '', $html)) ?? ''
        );

        return in_array($normalized, [
            '',
            '<p></p>',
            '<p><br></p>',
            '<p><br/></p>',
            '<div></div>',
            '<div><br></div>',
            '<div><br/></div>',
        ], true);
    }

    private function isEmptyInstructionHtml(?string $html): bool
    {
        if ($html === null) {
            return true;
        }

        $html = trim($html);

        if ($html === '' || $this->isEmptyQuillHtml($html)) {
            return true;
        }

        $text = trim(
            html_entity_decode(
                strip_tags(str_replace('&nbsp;', ' ', $html)),
                ENT_QUOTES | ENT_HTML5,
                'UTF-8'
            )
        );

        $hasMeaningfulTag = (bool) preg_match(
            '/<(img|video|audio|iframe|table|ul|ol|li|blockquote|pre|code)\b/i',
            $html
        );

        return $text === '' && !$hasMeaningfulTag;
    }

    private function cleanInstructionHtml(string $html): string
    {
        $html = trim($html);

        if ($html === '') {
            return '';
        }

        /*
        |--------------------------------------------------------------------------
        | Buang tag berbahaya.
        |--------------------------------------------------------------------------
        */
        $html = preg_replace('/<!--.*?-->/s', '', $html) ?? $html;

        $html = preg_replace(
            '/<\s*(script|style|iframe|object|embed|form|input|button|textarea|select|option|meta|link|base)\b[^>]*>.*?<\s*\/\s*\1\s*>/is',
            '',
            $html
        ) ?? $html;

        $html = preg_replace(
            '/<\s*(script|style|iframe|object|embed|form|input|button|textarea|select|option|meta|link|base)\b[^>]*\/?>/is',
            '',
            $html
        ) ?? $html;

        /*
        |--------------------------------------------------------------------------
        | Hanya izinkan tag yang umum dipakai Quill untuk assignment.
        |--------------------------------------------------------------------------
        */
        $allowedTags = implode('', [
            '<p>',
            '<br>',
            '<strong>',
            '<b>',
            '<em>',
            '<i>',
            '<u>',
            '<s>',
            '<ol>',
            '<ul>',
            '<li>',
            '<a>',
            '<h1>',
            '<h2>',
            '<h3>',
            '<h4>',
            '<h5>',
            '<h6>',
            '<blockquote>',
            '<pre>',
            '<code>',
            '<span>',
            '<div>',
        ]);

        $html = strip_tags($html, $allowedTags);

        /*
        |--------------------------------------------------------------------------
        | Buang attribute berbahaya.
        |--------------------------------------------------------------------------
        */
        $html = preg_replace('/\s+on[a-z]+\s*=\s*(".*?"|\'.*?\'|[^\s>]+)/is', '', $html) ?? $html;
        $html = preg_replace('/\s+style\s*=\s*(".*?"|\'.*?\'|[^\s>]+)/is', '', $html) ?? $html;
        $html = preg_replace('/\s+src\s*=\s*(".*?"|\'.*?\'|[^\s>]+)/is', '', $html) ?? $html;

        /*
        |--------------------------------------------------------------------------
        | Bersihkan tag <a>. Hanya href aman yang dipertahankan.
        |--------------------------------------------------------------------------
        */
        $html = preg_replace_callback('/<a\b([^>]*)>/i', function ($matches) {
            $attributes = $matches[1] ?? '';
            $href = null;

            if (preg_match('/href\s*=\s*("([^"]*)"|\'([^\']*)\'|([^\s>]+))/i', $attributes, $hrefMatches)) {
                $href = $hrefMatches[2] ?? $hrefMatches[3] ?? $hrefMatches[4] ?? null;
            }

            $href = trim((string) $href);

            if ($href === '' || !$this->isSafeInstructionUrl($href)) {
                return '<a>';
            }

            return '<a href="' . e($href) . '" target="_blank" rel="noopener noreferrer">';
        }, $html) ?? $html;

        /*
        |--------------------------------------------------------------------------
        | Untuk tag selain <a>, pertahankan class Quill saja.
        | Contoh: ql-align-center, ql-indent-1.
        |--------------------------------------------------------------------------
        */
        $html = preg_replace_callback('/<(?!\/|a\b|br\b)([a-z0-9]+)\b([^>]*)>/i', function ($matches) {
            $tag = strtolower($matches[1]);
            $attributes = $matches[2] ?? '';
            $classAttr = '';

            if (preg_match('/class\s*=\s*("([^"]*)"|\'([^\']*)\'|([^\s>]+))/i', $attributes, $classMatches)) {
                $classValue = $classMatches[2] ?? $classMatches[3] ?? $classMatches[4] ?? '';

                $allowedClasses = collect(preg_split('/\s+/', trim($classValue)) ?: [])
                    ->filter(fn ($className) => preg_match('/^ql-[a-z0-9_-]+$/i', $className))
                    ->values()
                    ->all();

                if (!empty($allowedClasses)) {
                    $classAttr = ' class="' . e(implode(' ', $allowedClasses)) . '"';
                }
            }

            return '<' . $tag . $classAttr . '>';
        }, $html) ?? $html;

        /*
        |--------------------------------------------------------------------------
        | Rapikan whitespace antar tag tanpa mengubah isi text.
        |--------------------------------------------------------------------------
        */
        $html = preg_replace('/>\s+</', '><', $html) ?? $html;

        return trim($html);
    }

    private function isSafeInstructionUrl(string $url): bool
    {
        $url = trim($url);

        if ($url === '') {
            return false;
        }

        $lowerUrl = strtolower($url);

        if (
            str_starts_with($lowerUrl, 'javascript:')
            || str_starts_with($lowerUrl, 'data:')
            || str_starts_with($lowerUrl, 'vbscript:')
        ) {
            return false;
        }

        return str_starts_with($lowerUrl, 'http://')
            || str_starts_with($lowerUrl, 'https://')
            || str_starts_with($lowerUrl, 'mailto:')
            || str_starts_with($lowerUrl, 'tel:')
            || str_starts_with($lowerUrl, '#')
            || str_starts_with($lowerUrl, '/');
    }

    private function plainTextToInstructionHtml(string $text): string
    {
        $text = trim(str_replace(["\r\n", "\r"], "\n", $text));

        if ($text === '') {
            return '';
        }

        $paragraphs = preg_split("/\n{2,}/", $text) ?: [];

        return collect($paragraphs)
            ->map(function ($paragraph) {
                $paragraph = trim((string) $paragraph);

                if ($paragraph === '') {
                    return null;
                }

                $paragraph = e($paragraph);
                $paragraph = preg_replace("/\n/", '<br>', $paragraph);

                return '<p>' . $paragraph . '</p>';
            })
            ->filter()
            ->implode('');
    }

    private function assignmentTypes(): array
    {
        return [
            'text' => 'Text Answer',
            'file' => 'File Upload',
            'link' => 'Link Submission',
            'mixed' => 'Mixed Submission',
        ];
    }

    private function statuses(): array
    {
        return [
            'draft' => 'Draft',
            'published' => 'Published',
            'archived' => 'Archived',
        ];
    }

    private function generateUniqueSlug(string $title, ?int $ignoreId = null): string
    {
        $baseSlug = Str::slug($title);
        $baseSlug = $baseSlug !== '' ? $baseSlug : 'assignment';

        $slug = $baseSlug;
        $counter = 1;

        while (
            Assignment::query()
                ->when($ignoreId, function ($query) use ($ignoreId) {
                    $query->where('id', '!=', $ignoreId);
                })
                ->where('slug', $slug)
                ->exists()
        ) {
            $slug = $baseSlug . '-' . $counter;
            $counter++;
        }

        return $slug;
    }

    private function errorResponse(string $message, Throwable $e): JsonResponse
    {
        return response()->json([
            'success' => false,
            'message' => $message,
            'error' => config('app.debug') ? $e->getMessage() : null,
        ], 500);
    }
}