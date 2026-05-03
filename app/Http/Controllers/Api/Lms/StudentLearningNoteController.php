<?php

namespace App\Http\Controllers\Api\Lms;

use App\Http\Controllers\Controller;
use App\Models\Student;
use App\Models\StudentLearningNote;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class StudentLearningNoteController extends Controller
{
    /**
     * List notes milik student.
     *
     * Support filter:
     * - course_id
     * - course_slug
     * - module_id
     * - topic_id
     * - topic_slug
     * - sub_topic_id
     * - sub_topic_slug
     * - lesson_id
     * - lesson_slug
     * - keyword
     * - status
     */
    public function index(Request $request): JsonResponse
    {
        $studentId = $this->resolveStudentId($request);

        if (! $studentId) {
            return response()->json([
                'message' => 'Student profile tidak ditemukan.',
            ], 422);
        }

        $validated = $request->validate([
            'course_id' => ['nullable', 'integer'],
            'course_slug' => ['nullable', 'string', 'max:255'],

            'module_id' => ['nullable', 'integer'],

            'topic_id' => ['nullable', 'integer'],
            'topic_slug' => ['nullable', 'string', 'max:255'],

            'sub_topic_id' => ['nullable', 'integer'],
            'sub_topic_slug' => ['nullable', 'string', 'max:255'],

            'lesson_id' => ['nullable', 'integer'],
            'lesson_slug' => ['nullable', 'string', 'max:255'],

            'keyword' => ['nullable', 'string', 'max:255'],
            'status' => ['nullable', Rule::in(['active', 'archived'])],

            'per_page' => ['nullable', 'integer', 'min:1', 'max:100'],
        ]);

        $perPage = (int) ($validated['per_page'] ?? 15);

        $notes = StudentLearningNote::query()
            ->forStudent($studentId)
            ->where('status', $validated['status'] ?? 'active')
            ->when($validated['course_id'] ?? null, function ($query, $courseId) {
                $query->where('course_id', $courseId);
            })
            ->when($validated['course_slug'] ?? null, function ($query, $courseSlug) {
                $query->where('course_slug', $courseSlug);
            })
            ->when($validated['module_id'] ?? null, function ($query, $moduleId) {
                $query->where('module_id', $moduleId);
            })
            ->when($validated['topic_id'] ?? null, function ($query, $topicId) {
                $query->where('topic_id', $topicId);
            })
            ->when($validated['topic_slug'] ?? null, function ($query, $topicSlug) {
                $query->where('topic_slug', $topicSlug);
            })
            ->when($validated['sub_topic_id'] ?? null, function ($query, $subTopicId) {
                $query->where('sub_topic_id', $subTopicId);
            })
            ->when($validated['sub_topic_slug'] ?? null, function ($query, $subTopicSlug) {
                $query->where('sub_topic_slug', $subTopicSlug);
            })
            ->when($validated['lesson_id'] ?? null, function ($query, $lessonId) {
                $query->where('lesson_id', $lessonId);
            })
            ->when($validated['lesson_slug'] ?? null, function ($query, $lessonSlug) {
                $query->where('lesson_slug', $lessonSlug);
            })
            ->search($validated['keyword'] ?? null)
            ->latest()
            ->paginate($perPage);

        return response()->json([
            'message' => 'Notes berhasil dimuat.',
            'data' => [
                'notes' => $notes->through(fn (StudentLearningNote $note) => $this->formatNote($note)),
            ],
        ]);
    }

    /**
     * Simpan note dari halaman learning.
     *
     * Endpoint:
     * POST /api/lms/student/learn/{courseSlug}/{lessonSlug}/notes
     */
    public function store(Request $request, string $courseSlug, string $lessonSlug): JsonResponse
    {
        $studentId = $this->resolveStudentId($request);

        if (! $studentId) {
            return response()->json([
                'message' => 'Student profile tidak ditemukan.',
            ], 422);
        }

        $validated = $request->validate([
            'title' => ['required', 'string', 'max:255'],
            'content' => ['required', 'string'],

            'tags' => ['nullable', 'array'],
            'tags.*' => ['nullable', 'string', 'max:50'],

            'course_id' => ['nullable', 'integer'],
            'course_slug' => ['nullable', 'string', 'max:255'],
            'course_title' => ['nullable', 'string', 'max:255'],

            'module_id' => ['nullable', 'integer'],
            'module_title' => ['nullable', 'string', 'max:255'],

            'topic_id' => ['nullable', 'integer'],
            'topic_slug' => ['nullable', 'string', 'max:255'],
            'topic_title' => ['nullable', 'string', 'max:255'],

            'sub_topic_id' => ['nullable', 'integer'],
            'sub_topic_slug' => ['nullable', 'string', 'max:255'],
            'sub_topic_title' => ['nullable', 'string', 'max:255'],

            'lesson_id' => ['nullable', 'integer'],
            'lesson_slug' => ['nullable', 'string', 'max:255'],
            'lesson_title' => ['nullable', 'string', 'max:255'],

            'video_timestamp_seconds' => ['nullable', 'integer', 'min:0'],
        ]);

        $note = StudentLearningNote::query()->create([
            'student_id' => $studentId,

            'course_id' => $validated['course_id'] ?? null,
            'course_slug' => $validated['course_slug'] ?? $courseSlug,
            'course_title' => $validated['course_title'] ?? null,

            'module_id' => $validated['module_id'] ?? null,
            'module_title' => $validated['module_title'] ?? null,

            'topic_id' => $validated['topic_id'] ?? null,
            'topic_slug' => $validated['topic_slug'] ?? null,
            'topic_title' => $validated['topic_title'] ?? null,

            'sub_topic_id' => $validated['sub_topic_id'] ?? null,
            'sub_topic_slug' => $validated['sub_topic_slug'] ?? $lessonSlug,
            'sub_topic_title' => $validated['sub_topic_title'] ?? null,

            'lesson_id' => $validated['lesson_id'] ?? $validated['sub_topic_id'] ?? null,
            'lesson_slug' => $validated['lesson_slug'] ?? $lessonSlug,
            'lesson_title' => $validated['lesson_title'] ?? $validated['sub_topic_title'] ?? null,

            'title' => $validated['title'],
            'content' => $validated['content'],
            'tags' => $this->normalizeTags($validated['tags'] ?? []),

            'video_timestamp_seconds' => (int) ($validated['video_timestamp_seconds'] ?? 0),
            'status' => 'active',
        ]);

        return response()->json([
            'message' => 'Catatan berhasil disimpan.',
            'data' => [
                'note' => $this->formatNote($note),
            ],
        ], 201);
    }

    /**
     * Detail note.
     */
    public function show(Request $request, StudentLearningNote $note): JsonResponse
    {
        $studentId = $this->resolveStudentId($request);

        if (! $studentId || (int) $note->student_id !== (int) $studentId) {
            return response()->json([
                'message' => 'Catatan tidak ditemukan.',
            ], 404);
        }

        return response()->json([
            'message' => 'Catatan berhasil dimuat.',
            'data' => [
                'note' => $this->formatNote($note),
            ],
        ]);
    }

    /**
     * Update note.
     */
    public function update(Request $request, StudentLearningNote $note): JsonResponse
    {
        $studentId = $this->resolveStudentId($request);

        if (! $studentId || (int) $note->student_id !== (int) $studentId) {
            return response()->json([
                'message' => 'Catatan tidak ditemukan.',
            ], 404);
        }

        $validated = $request->validate([
            'title' => ['required', 'string', 'max:255'],
            'content' => ['required', 'string'],
            'tags' => ['nullable', 'array'],
            'tags.*' => ['nullable', 'string', 'max:50'],
            'video_timestamp_seconds' => ['nullable', 'integer', 'min:0'],
        ]);

        $note->update([
            'title' => $validated['title'],
            'content' => $validated['content'],
            'tags' => $this->normalizeTags($validated['tags'] ?? []),
            'video_timestamp_seconds' => (int) ($validated['video_timestamp_seconds'] ?? $note->video_timestamp_seconds),
        ]);

        return response()->json([
            'message' => 'Catatan berhasil diperbarui.',
            'data' => [
                'note' => $this->formatNote($note->fresh()),
            ],
        ]);
    }

    /**
     * Soft delete note.
     */
    public function destroy(Request $request, StudentLearningNote $note): JsonResponse
    {
        $studentId = $this->resolveStudentId($request);

        if (! $studentId || (int) $note->student_id !== (int) $studentId) {
            return response()->json([
                'message' => 'Catatan tidak ditemukan.',
            ], 404);
        }

        $note->delete();

        return response()->json([
            'message' => 'Catatan berhasil dihapus.',
        ]);
    }

    /**
     * Archive note tanpa menghapus.
     */
    public function archive(Request $request, StudentLearningNote $note): JsonResponse
    {
        $studentId = $this->resolveStudentId($request);

        if (! $studentId || (int) $note->student_id !== (int) $studentId) {
            return response()->json([
                'message' => 'Catatan tidak ditemukan.',
            ], 404);
        }

        $note->update([
            'status' => 'archived',
        ]);

        return response()->json([
            'message' => 'Catatan berhasil diarsipkan.',
            'data' => [
                'note' => $this->formatNote($note->fresh()),
            ],
        ]);
    }

    /**
     * Restore archive note.
     */
    public function restore(Request $request, StudentLearningNote $note): JsonResponse
    {
        $studentId = $this->resolveStudentId($request);

        if (! $studentId || (int) $note->student_id !== (int) $studentId) {
            return response()->json([
                'message' => 'Catatan tidak ditemukan.',
            ], 404);
        }

        $note->update([
            'status' => 'active',
        ]);

        return response()->json([
            'message' => 'Catatan berhasil dikembalikan.',
            'data' => [
                'note' => $this->formatNote($note->fresh()),
            ],
        ]);
    }

    private function resolveStudentId(Request $request): ?int
    {
        $user = $request->user();

        if (! $user) {
            return null;
        }

        if (isset($user->student_id) && $user->student_id) {
            return (int) $user->student_id;
        }

        if (method_exists($user, 'student')) {
            $student = $user->student;

            if ($student?->id) {
                return (int) $student->id;
            }
        }

        if (class_exists(Student::class)) {
            $student = Student::query()
                ->where(function ($query) use ($user) {
                    $query
                        ->where('user_id', $user->id)
                        ->orWhere('email', $user->email ?? null);
                })
                ->first();

            if ($student?->id) {
                return (int) $student->id;
            }
        }

        return null;
    }

    private function normalizeTags(array $tags): array
    {
        return collect($tags)
            ->filter(fn ($tag) => filled($tag))
            ->map(fn ($tag) => trim((string) $tag))
            ->filter()
            ->unique()
            ->values()
            ->all();
    }

    private function formatNote(StudentLearningNote $note): array
    {
        return [
            'id' => $note->id,

            'student_id' => $note->student_id,
            'studentId' => $note->student_id,

            'course_id' => $note->course_id,
            'courseId' => $note->course_id,
            'course_slug' => $note->course_slug,
            'courseSlug' => $note->course_slug,
            'course_title' => $note->course_title,
            'courseTitle' => $note->course_title,

            'module_id' => $note->module_id,
            'moduleId' => $note->module_id,
            'module_title' => $note->module_title,
            'moduleTitle' => $note->module_title,

            'topic_id' => $note->topic_id,
            'topicId' => $note->topic_id,
            'topic_slug' => $note->topic_slug,
            'topicSlug' => $note->topic_slug,
            'topic_title' => $note->topic_title,
            'topicTitle' => $note->topic_title,

            'sub_topic_id' => $note->sub_topic_id,
            'subTopicId' => $note->sub_topic_id,
            'sub_topic_slug' => $note->sub_topic_slug,
            'subTopicSlug' => $note->sub_topic_slug,
            'sub_topic_title' => $note->sub_topic_title,
            'subTopicTitle' => $note->sub_topic_title,

            'lesson_id' => $note->lesson_id,
            'lessonId' => $note->lesson_id,
            'lesson_slug' => $note->lesson_slug,
            'lessonSlug' => $note->lesson_slug,
            'lesson_title' => $note->lesson_title,
            'lessonTitle' => $note->lesson_title,

            'title' => $note->title,
            'content' => $note->content,
            'tags' => $note->tags ?? [],

            'video_timestamp_seconds' => $note->video_timestamp_seconds,
            'videoTimestampSeconds' => $note->video_timestamp_seconds,
            'video_timestamp_label' => $note->video_timestamp_label,
            'videoTimestampLabel' => $note->video_timestamp_label,

            'status' => $note->status,

            'created_at' => optional($note->created_at)->toISOString(),
            'createdAt' => optional($note->created_at)->toISOString(),

            'updated_at' => optional($note->updated_at)->toISOString(),
            'updatedAt' => optional($note->updated_at)->toISOString(),
        ];
    }
}