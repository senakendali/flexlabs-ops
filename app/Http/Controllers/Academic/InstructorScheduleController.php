<?php

namespace App\Http\Controllers\Academic;

use App\Http\Controllers\Controller;
use App\Models\Batch;
use App\Models\Instructor;
use App\Models\InstructorSchedule;
use App\Models\Program;
use App\Models\SubTopic;
use App\Models\Topic;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class InstructorScheduleController extends Controller
{
    public function index(Request $request): View
    {
        $perPage = (int) $request->input('per_page', 10);

        if (! in_array($perPage, [10, 25, 50, 100], true)) {
            $perPage = 10;
        }

        $search = trim((string) $request->input('search'));
        $instructorId = $request->input('instructor_id');
        $replacementInstructorId = $request->input('replacement_instructor_id');
        $batchId = $request->input('batch_id');
        $programId = $request->input('program_id');
        $status = $request->input('status');
        $deliveryMode = $request->input('delivery_mode');
        $dateFrom = $request->input('date_from');
        $dateTo = $request->input('date_to');
        $isMakeupSession = $request->input('is_makeup_session');

        $schedules = InstructorSchedule::query()
            ->with([
                'instructor:id,name',
                'replacementInstructor:id,name',
                'batch:id,name,program_id',
                'batch.program:id,name',
                'program:id,name',
                'subTopic:id,name,topic_id',
                'subTopics:id,name,topic_id,lesson_type',
                'rescheduledFrom:id,session_title,schedule_date,start_time,end_time',
            ])
            ->withCount('subTopics as scheduled_materials_count')
            ->when($search, function ($query) use ($search) {
                $query->where(function ($subQuery) use ($search) {
                    $subQuery->where('session_title', 'like', '%' . $search . '%')
                        ->orWhere('location', 'like', '%' . $search . '%')
                        ->orWhere('meeting_link', 'like', '%' . $search . '%')
                        ->orWhere('notes', 'like', '%' . $search . '%');
                });
            })
            ->when($instructorId, fn ($query) => $query->where('instructor_id', $instructorId))
            ->when($replacementInstructorId, fn ($query) => $query->where('replacement_instructor_id', $replacementInstructorId))
            ->when($batchId, fn ($query) => $query->where('batch_id', $batchId))
            ->when($programId, fn ($query) => $query->where('program_id', $programId))
            ->when($status, fn ($query) => $query->where('status', $status))
            ->when($deliveryMode, fn ($query) => $query->where('delivery_mode', $deliveryMode))
            ->when($dateFrom, fn ($query) => $query->whereDate('schedule_date', '>=', $dateFrom))
            ->when($dateTo, fn ($query) => $query->whereDate('schedule_date', '<=', $dateTo))
            ->when($isMakeupSession !== null && $isMakeupSession !== '', function ($query) use ($isMakeupSession) {
                $query->where('is_makeup_session', (bool) $isMakeupSession);
            })
            ->orderBy('schedule_date')
            ->orderBy('start_time')
            ->paginate($perPage)
            ->withQueryString();

        return view('academic.instructor-schedules.index', [
            'schedules' => $schedules,
            'instructors' => Instructor::query()->orderBy('name')->get(['id', 'name']),
            'batches' => $this->batchOptions(),
            'programs' => Program::query()->orderBy('name')->get(['id', 'name']),
            'filters' => [
                'search' => $search,
                'instructor_id' => $instructorId,
                'replacement_instructor_id' => $replacementInstructorId,
                'batch_id' => $batchId,
                'program_id' => $programId,
                'status' => $status,
                'delivery_mode' => $deliveryMode,
                'date_from' => $dateFrom,
                'date_to' => $dateTo,
                'is_makeup_session' => $isMakeupSession,
                'per_page' => $perPage,
            ],
            'statusOptions' => $this->statusOptions(),
            'deliveryModeOptions' => $this->deliveryModeOptions(),
        ]);
    }

    public function create(): View
    {
        $schedule = new InstructorSchedule([
            'status' => 'scheduled',
            'delivery_mode' => 'online',
            'is_makeup_session' => false,
        ]);

        $programId = old('program_id');
        $programId = $programId ? (int) $programId : null;

        $materialTopics = $this->getMaterialTopics($programId);
        $selectedSubTopicIds = $this->normalizeSubTopicIds(old('sub_topic_ids', []));

        return view('academic.instructor-schedules.form', [
            'schedule' => $schedule,
            'instructors' => Instructor::query()->orderBy('name')->get(['id', 'name']),
            'replacementInstructors' => Instructor::query()->orderBy('name')->get(['id', 'name']),
            'batches' => $this->batchOptions(),
            'programs' => Program::query()->orderBy('name')->get(['id', 'name']),
            'subTopics' => $this->getLiveSessionSubTopics($programId),
            'materialTopics' => $materialTopics,
            'materialTopicsPayload' => $this->materialTopicsPayload($materialTopics),
            'selectedSubTopicIds' => $selectedSubTopicIds,
            'reschedulableSchedules' => $this->getReschedulableSchedules(),
            'statusOptions' => $this->statusOptions(),
            'deliveryModeOptions' => $this->deliveryModeOptions(),
            'submitMode' => 'create',
        ]);
    }

    public function store(Request $request): JsonResponse|RedirectResponse
    {
        $validated = $this->validateRequest($request);

        $this->ensureNoConflict($validated);

        $scheduledSubTopicIds = $this->validateScheduledMaterials(
            $this->extractScheduledSubTopicIdsFromRequest($request),
            $this->resolveProgramIdFromPayload($validated)
        );

        $this->ensureScheduledMaterialsSelected($scheduledSubTopicIds);

        $payload = $this->buildPayload($validated);
        $payload['sub_topic_id'] = $scheduledSubTopicIds->first();

        $schedule = null;

        DB::transaction(function () use (&$schedule, $payload, $scheduledSubTopicIds) {
            $schedule = new InstructorSchedule();
            $schedule->fill($payload);
            $schedule->save();

            $this->syncScheduledMaterials($schedule, $scheduledSubTopicIds->toArray());
        });

        return $this->successResponse(
            $request,
            'Jadwal instruktur berhasil dibuat.',
            route('instructor-schedules.edit', $schedule),
            $schedule
        );
    }

    public function show(InstructorSchedule $instructorSchedule): View
    {
        $instructorSchedule->load([
            'instructor',
            'replacementInstructor',
            'batch.program',
            'program',
            'subTopic.topic',
            'subTopics.topic.module.stage',
            'rescheduledFrom.instructor',
            'rescheduledFrom.batch.program',
        ]);

        return view('academic.instructor-schedules.show', [
            'schedule' => $instructorSchedule,
        ]);
    }

    public function edit(InstructorSchedule $instructorSchedule): View
    {
        $instructorSchedule->load([
            'instructor',
            'replacementInstructor',
            'batch.program',
            'program',
            'subTopic.topic',
            'subTopics.topic.module.stage',
            'rescheduledFrom',
        ]);

        $programId = old('program_id')
            ?: $instructorSchedule->program_id
            ?: data_get($instructorSchedule, 'batch.program_id');

        $programId = $programId ? (int) $programId : null;

        $materialTopics = $this->getMaterialTopics($programId);
        $selectedSubTopicIds = $this->getSelectedSubTopicIds($instructorSchedule);

        return view('academic.instructor-schedules.form', [
            'schedule' => $instructorSchedule,
            'instructors' => Instructor::query()->orderBy('name')->get(['id', 'name']),
            'replacementInstructors' => Instructor::query()->orderBy('name')->get(['id', 'name']),
            'batches' => $this->batchOptions(),
            'programs' => Program::query()->orderBy('name')->get(['id', 'name']),
            'subTopics' => $this->getLiveSessionSubTopics($programId),
            'materialTopics' => $materialTopics,
            'materialTopicsPayload' => $this->materialTopicsPayload($materialTopics),
            'selectedSubTopicIds' => $selectedSubTopicIds,
            'reschedulableSchedules' => $this->getReschedulableSchedules($instructorSchedule->id),
            'statusOptions' => $this->statusOptions(),
            'deliveryModeOptions' => $this->deliveryModeOptions(),
            'submitMode' => 'edit',
        ]);
    }

    public function update(Request $request, InstructorSchedule $instructorSchedule): JsonResponse|RedirectResponse
    {
        $validated = $this->validateRequest($request, $instructorSchedule->id);

        $this->ensureNoConflict($validated, $instructorSchedule->id);

        $scheduledSubTopicIds = $this->validateScheduledMaterials(
            $this->extractScheduledSubTopicIdsFromRequest($request),
            $this->resolveProgramIdFromPayload($validated, $instructorSchedule)
        );

        $this->ensureScheduledMaterialsSelected($scheduledSubTopicIds);

        $payload = $this->buildPayload($validated);
        $payload['sub_topic_id'] = $scheduledSubTopicIds->first();

        DB::transaction(function () use ($instructorSchedule, $payload, $scheduledSubTopicIds) {
            $instructorSchedule->update($payload);

            $this->syncScheduledMaterials($instructorSchedule, $scheduledSubTopicIds->toArray());
        });

        return $this->successResponse(
            $request,
            'Jadwal instruktur berhasil diperbarui.',
            route('instructor-schedules.edit', $instructorSchedule),
            $instructorSchedule
        );
    }

    public function destroy(Request $request, InstructorSchedule $instructorSchedule): JsonResponse|RedirectResponse
    {
        $instructorSchedule->delete();

        return $this->successResponse(
            $request,
            'Jadwal instruktur berhasil dihapus.',
            route('instructor-schedules.index')
        );
    }

    public function materialTopics(Request $request): JsonResponse
    {
        $programId = $request->integer('program_id') ?: null;

        $topics = $this->materialTopicsPayload(
            $this->getMaterialTopics($programId)
        );

        return response()->json([
            'success' => true,
            'message' => count($topics) > 0
                ? 'Topic live session berhasil dimuat.'
                : 'Belum ada topic live session untuk program ini.',
            'topics' => $topics,
        ]);
    }

    protected function validateRequest(Request $request, ?int $ignoreId = null): array
    {
        $normalizedStartTime = $this->normalizeTimeInput($request->input('start_time'));
        $normalizedEndTime = $this->normalizeTimeInput($request->input('end_time'));

        $request->merge([
            'start_time' => $normalizedStartTime ?? $request->input('start_time'),
            'end_time' => $normalizedEndTime ?? $request->input('end_time'),
        ]);

        $validated = $request->validate([
            'instructor_id' => ['required', 'exists:instructors,id'],
            'replacement_instructor_id' => [
                'nullable',
                'exists:instructors,id',
                'different:instructor_id',
            ],
            'batch_id' => ['required', 'exists:batches,id'],
            'program_id' => ['nullable', 'exists:programs,id'],
            'sub_topic_id' => ['nullable', 'exists:sub_topics,id'],
            'sub_topic_ids' => ['nullable', 'array'],
            'sub_topic_ids.*' => ['integer', 'exists:sub_topics,id'],
            'rescheduled_from_id' => ['nullable', 'exists:instructor_schedules,id'],
            'session_title' => ['required', 'string', 'max:255'],
            'schedule_date' => ['required', 'date'],
            'start_time' => ['required', 'date_format:H:i'],
            'end_time' => ['required', 'date_format:H:i', 'after:start_time'],
            'delivery_mode' => ['required', Rule::in(array_keys($this->deliveryModeOptions()))],
            'meeting_link' => ['nullable', 'url', 'max:255'],
            'location' => ['nullable', 'string', 'max:255'],
            'is_makeup_session' => ['nullable', 'boolean'],
            'status' => ['required', Rule::in(array_keys($this->statusOptions()))],
            'notes' => ['nullable', 'string'],
        ]);

        $validated['is_makeup_session'] = $request->boolean('is_makeup_session');
        $validated['start_time'] = $this->normalizeTimeInput($validated['start_time']) ?? $validated['start_time'];
        $validated['end_time'] = $this->normalizeTimeInput($validated['end_time']) ?? $validated['end_time'];

        if (
            $ignoreId
            && ! empty($validated['rescheduled_from_id'])
            && (int) $validated['rescheduled_from_id'] === $ignoreId
        ) {
            throw ValidationException::withMessages([
                'rescheduled_from_id' => 'Sesi asal reschedule tidak boleh memilih jadwal yang sama.',
            ]);
        }

        return $validated;
    }

    protected function normalizeTimeInput(mixed $value): ?string
    {
        if ($value === null || $value === '') {
            return null;
        }

        $value = trim((string) $value);

        if (preg_match('/^\d{2}:\d{2}$/', $value)) {
            return $value;
        }

        if (preg_match('/^\d{2}:\d{2}:\d{2}$/', $value)) {
            return substr($value, 0, 5);
        }

        return null;
    }

    protected function buildPayload(array $validated): array
    {
        return [
            'instructor_id' => $validated['instructor_id'],
            'replacement_instructor_id' => $validated['replacement_instructor_id'] ?? null,
            'batch_id' => $validated['batch_id'],
            'program_id' => $validated['program_id'] ?? null,
            'sub_topic_id' => $validated['sub_topic_id'] ?? null,
            'rescheduled_from_id' => $validated['rescheduled_from_id'] ?? null,
            'session_title' => $validated['session_title'],
            'schedule_date' => $validated['schedule_date'],
            'start_time' => $validated['start_time'],
            'end_time' => $validated['end_time'],
            'delivery_mode' => $validated['delivery_mode'],
            'meeting_link' => $validated['meeting_link'] ?? null,
            'location' => $validated['location'] ?? null,
            'is_makeup_session' => (bool) ($validated['is_makeup_session'] ?? false),
            'status' => $validated['status'],
            'notes' => $validated['notes'] ?? null,
        ];
    }

    protected function ensureNoConflict(array $validated, ?int $ignoreId = null): void
    {
        $teachingInstructorId = $validated['replacement_instructor_id'] ?? $validated['instructor_id'];

        if (! $teachingInstructorId) {
            return;
        }

        $query = InstructorSchedule::query()
            ->whereDate('schedule_date', $validated['schedule_date'])
            ->where(function ($query) use ($teachingInstructorId) {
                $query->where('instructor_id', $teachingInstructorId)
                    ->orWhere('replacement_instructor_id', $teachingInstructorId);
            })
            ->where(function ($query) use ($validated) {
                $query->where('start_time', '<', $validated['end_time'])
                    ->where('end_time', '>', $validated['start_time']);
            })
            ->whereNotIn('status', ['cancelled', 'canceled']);

        if ($ignoreId) {
            $query->whereKeyNot($ignoreId);
        }

        if ($query->exists()) {
            throw ValidationException::withMessages([
                'start_time' => 'Instruktur sudah memiliki jadwal lain pada rentang waktu tersebut.',
                'end_time' => 'Instruktur sudah memiliki jadwal lain pada rentang waktu tersebut.',
            ]);
        }
    }

    protected function batchOptions()
    {
        return Batch::query()
            ->with('program:id,name')
            ->orderBy('name')
            ->get(['id', 'name', 'program_id']);
    }

    protected function getReschedulableSchedules(?int $excludeId = null)
    {
        return InstructorSchedule::query()
            ->with([
                'instructor:id,name',
                'replacementInstructor:id,name',
                'batch:id,name,program_id',
                'batch.program:id,name',
            ])
            ->when($excludeId, fn ($query) => $query->whereKeyNot($excludeId))
            ->whereNotIn('status', ['cancelled', 'canceled'])
            ->orderByDesc('schedule_date')
            ->orderByDesc('start_time')
            ->limit(80)
            ->get([
                'id',
                'instructor_id',
                'replacement_instructor_id',
                'batch_id',
                'session_title',
                'schedule_date',
                'start_time',
                'end_time',
            ]);
    }

    private function getMaterialTopics(?int $programId = null)
    {
        $liveTypes = $this->liveSessionLessonTypes();

        return Topic::query()
            ->with([
                'module.stage',
                'subTopics' => function ($query) use ($liveTypes) {
                    $query
                        ->whereIn('lesson_type', $liveTypes)
                        ->when(Schema::hasColumn('sub_topics', 'is_active'), function ($q) {
                            $q->where('is_active', true);
                        })
                        ->when(Schema::hasColumn('sub_topics', 'sort_order'), function ($q) {
                            $q->orderBy('sort_order');
                        })
                        ->orderBy('id');
                },
            ])
            ->whereHas('subTopics', function ($query) use ($liveTypes) {
                $query
                    ->whereIn('lesson_type', $liveTypes)
                    ->when(Schema::hasColumn('sub_topics', 'is_active'), function ($q) {
                        $q->where('is_active', true);
                    });
            })
            ->when($programId, function ($query) use ($programId) {
                $query->where(function ($programQuery) use ($programId) {
                    $programQuery->whereHas('module.stage', function ($stageQuery) use ($programId) {
                        $stageQuery->where('program_id', $programId);
                    });

                    if (Schema::hasColumn('modules', 'program_id')) {
                        $programQuery->orWhereHas('module', function ($moduleQuery) use ($programId) {
                            $moduleQuery->where('program_id', $programId);
                        });
                    }
                });
            })
            ->when(Schema::hasColumn('topics', 'sort_order'), function ($query) {
                $query->orderBy('sort_order');
            })
            ->orderBy('id')
            ->get()
            ->filter(fn ($topic) => $topic->subTopics->isNotEmpty())
            ->values();
    }

    private function materialTopicsPayload($topics): array
    {
        return collect($topics)
            ->map(function ($topic) {
                return [
                    'id' => (string) $topic->id,
                    'name' => $topic->name,
                    'module' => data_get($topic, 'module.name'),
                    'stage' => data_get($topic, 'module.stage.name'),
                    'program_id' => (string) (
                        data_get($topic, 'module.stage.program_id')
                        ?? data_get($topic, 'program_id')
                        ?? ''
                    ),
                    'sub_topics' => collect($topic->subTopics)
                        ->map(function ($subTopic) {
                            return [
                                'id' => (string) $subTopic->id,
                                'name' => $subTopic->name,
                                'description' => $subTopic->description ?? null,
                                'lesson_type' => $subTopic->lesson_type ?? null,
                                'sort_order' => (int) ($subTopic->sort_order ?? 0),
                            ];
                        })
                        ->values()
                        ->toArray(),
                ];
            })
            ->values()
            ->toArray();
    }

    private function getLiveSessionSubTopics(?int $programId = null)
    {
        $liveTypes = $this->liveSessionLessonTypes();

        return SubTopic::query()
            ->with('topic.module.stage')
            ->whereIn('lesson_type', $liveTypes)
            ->when(Schema::hasColumn('sub_topics', 'is_active'), function ($query) {
                $query->where('is_active', true);
            })
            ->when($programId, function ($query) use ($programId) {
                $query->whereHas('topic', function ($topicQuery) use ($programId) {
                    $topicQuery->where(function ($programQuery) use ($programId) {
                        $programQuery->whereHas('module.stage', function ($stageQuery) use ($programId) {
                            $stageQuery->where('program_id', $programId);
                        });

                        if (Schema::hasColumn('modules', 'program_id')) {
                            $programQuery->orWhereHas('module', function ($moduleQuery) use ($programId) {
                                $moduleQuery->where('program_id', $programId);
                            });
                        }
                    });
                });
            })
            ->when(Schema::hasColumn('sub_topics', 'sort_order'), function ($query) {
                $query->orderBy('sort_order');
            })
            ->orderBy('id')
            ->get();
    }

    private function validateScheduledMaterials(array $subTopicIds, ?int $programId = null): Collection
    {
        $subTopicIds = $this->normalizeSubTopicIds($subTopicIds);

        if ($subTopicIds->isEmpty()) {
            return collect();
        }

        $liveTypes = $this->liveSessionLessonTypes();

        $validSubTopicIds = SubTopic::query()
            ->whereIn('id', $subTopicIds)
            ->whereIn('lesson_type', $liveTypes)
            ->when(Schema::hasColumn('sub_topics', 'is_active'), function ($query) {
                $query->where('is_active', true);
            })
            ->when($programId, function ($query) use ($programId) {
                $query->whereHas('topic', function ($topicQuery) use ($programId) {
                    $topicQuery->where(function ($programQuery) use ($programId) {
                        $programQuery->whereHas('module.stage', function ($stageQuery) use ($programId) {
                            $stageQuery->where('program_id', $programId);
                        });

                        if (Schema::hasColumn('modules', 'program_id')) {
                            $programQuery->orWhereHas('module', function ($moduleQuery) use ($programId) {
                                $moduleQuery->where('program_id', $programId);
                            });
                        }
                    });
                });
            })
            ->pluck('id')
            ->map(fn ($id) => (int) $id)
            ->values();

        $invalidIds = $subTopicIds->diff($validSubTopicIds);

        if ($invalidIds->isNotEmpty()) {
            throw ValidationException::withMessages([
                'sub_topic_ids' => 'Materi yang dipilih tidak valid atau bukan live session.',
            ]);
        }

        return $subTopicIds;
    }

    private function ensureScheduledMaterialsSelected(Collection $scheduledSubTopicIds): void
    {
        if ($scheduledSubTopicIds->isNotEmpty()) {
            return;
        }

        throw ValidationException::withMessages([
            'sub_topic_ids' => 'Pilih minimal satu sub topic live session untuk jadwal ini.',
        ]);
    }

    private function extractScheduledSubTopicIdsFromRequest(Request $request): array
    {
        $ids = $request->input('sub_topic_ids', []);

        if (empty($ids)) {
            $ids = $request->input('sub_topic_ids[]', []);
        }

        if (! is_array($ids)) {
            $ids = [$ids];
        }

        if (empty($ids) && $request->filled('sub_topic_id')) {
            $ids = [$request->input('sub_topic_id')];
        }

        return $this->normalizeSubTopicIds($ids)->toArray();
    }

    private function syncScheduledMaterials(InstructorSchedule $schedule, array $subTopicIds): void
    {
        $syncData = $this->normalizeSubTopicIds($subTopicIds)
            ->mapWithKeys(function ($subTopicId, $index) {
                return [
                    $subTopicId => [
                        'sort_order' => $index + 1,
                        'created_at' => now(),
                        'updated_at' => now(),
                    ],
                ];
            })
            ->toArray();

        $schedule->subTopics()->sync($syncData);
    }

    private function getSelectedSubTopicIds(?InstructorSchedule $schedule = null): Collection
    {
        if (request()->old('sub_topic_ids') !== null) {
            return $this->normalizeSubTopicIds(request()->old('sub_topic_ids', []));
        }

        if (! $schedule || ! $schedule->exists) {
            return collect();
        }

        return $schedule->subTopics()
            ->pluck('sub_topics.id')
            ->map(fn ($id) => (int) $id)
            ->values();
    }

    private function normalizeSubTopicIds(array $subTopicIds): Collection
    {
        return collect($subTopicIds)
            ->flatten()
            ->filter(fn ($id) => $id !== null && $id !== '')
            ->map(fn ($id) => (int) $id)
            ->filter(fn ($id) => $id > 0)
            ->unique()
            ->values();
    }

    private function resolveProgramIdFromPayload(array $validated, ?InstructorSchedule $schedule = null): ?int
    {
        if (! empty($validated['program_id'])) {
            return (int) $validated['program_id'];
        }

        if (! empty($validated['batch_id'])) {
            $batchProgramId = Batch::query()
                ->whereKey($validated['batch_id'])
                ->value('program_id');

            return $batchProgramId ? (int) $batchProgramId : null;
        }

        if ($schedule?->program_id) {
            return (int) $schedule->program_id;
        }

        if ($schedule?->batch_id) {
            $batchProgramId = Batch::query()
                ->whereKey($schedule->batch_id)
                ->value('program_id');

            return $batchProgramId ? (int) $batchProgramId : null;
        }

        return null;
    }

    private function liveSessionLessonTypes(): array
    {
        return [
            'live_session',
            'live',
            'Live Session',
            'live-session',
        ];
    }

    protected function statusOptions(): array
    {
        return [
            'draft' => 'Draft',
            'scheduled' => 'Scheduled',
            'ongoing' => 'Ongoing',
            'completed' => 'Completed',
            'cancelled' => 'Cancelled',
            'rescheduled' => 'Rescheduled',
        ];
    }

    protected function deliveryModeOptions(): array
    {
        return [
            'online' => 'Online',
            'offline' => 'Offline',
            'hybrid' => 'Hybrid',
        ];
    }

    protected function successResponse(
        Request $request,
        string $message,
        string $redirectUrl,
        ?InstructorSchedule $schedule = null
    ): JsonResponse|RedirectResponse {
        if ($request->expectsJson() || $request->ajax()) {
            return response()->json([
                'success' => true,
                'message' => $message,
                'redirect' => $redirectUrl,
                'redirect_url' => $redirectUrl,
                'schedule' => $schedule,
            ]);
        }

        return redirect($redirectUrl)->with('success', $message);
    }
}