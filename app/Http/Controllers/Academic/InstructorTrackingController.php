<?php

namespace App\Http\Controllers\Academic;

use App\Http\Controllers\Controller;
use App\Models\Batch;
use App\Models\Instructor;
use App\Models\InstructorSchedule;
use App\Models\InstructorSessionTracking;
use App\Models\InstructorSessionTrackingItem;
use App\Models\SubTopic;
use Illuminate\Contracts\View\View;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Validation\ValidationException;
use Throwable;

class InstructorTrackingController extends Controller
{
    public function index(Request $request): View
    {
        $status = $request->input('status');
        $batchId = $request->input('batch_id');
        $instructorId = $request->input('instructor_id');
        $dateFrom = $request->input('date_from');
        $dateTo = $request->input('date_to');

        $query = InstructorSchedule::query()
            ->with([
                'instructor:id,name,email',
                'batch:id,name,program_id',
                'batch.program:id,name',
            ])
            ->when($batchId, fn ($q) => $q->where('batch_id', $batchId))
            ->when($instructorId, fn ($q) => $q->where('instructor_id', $instructorId))
            ->when($dateFrom, fn ($q) => $q->whereDate('schedule_date', '>=', $dateFrom))
            ->when($dateTo, fn ($q) => $q->whereDate('schedule_date', '<=', $dateTo));

        if ($status) {
            $trackingScheduleIds = InstructorSessionTracking::query()
                ->where('status', $status)
                ->pluck('instructor_schedule_id')
                ->all();

            $query->whereIn('id', $trackingScheduleIds ?: [-1]);
        }

        $schedules = $query
            ->orderByDesc('schedule_date')
            ->orderByDesc('start_time')
            ->paginate(15)
            ->withQueryString();

        $trackingMap = InstructorSessionTracking::query()
            ->whereIn('instructor_schedule_id', collect($schedules->items())->pluck('id'))
            ->get()
            ->keyBy('instructor_schedule_id');

        $batches = Batch::query()
            ->with('program:id,name')
            ->orderByDesc('id')
            ->get(['id', 'name', 'program_id']);

        $instructors = Instructor::query()
            ->orderBy('name')
            ->get(['id', 'name', 'email']);

        $stats = [
            'total' => $schedules->total(),
            'checked_in' => $trackingMap->where('status', InstructorSessionTracking::STATUS_CHECKED_IN)->count(),
            'submitted' => $trackingMap->where('status', InstructorSessionTracking::STATUS_SUBMITTED)->count(),
            'reviewed' => $trackingMap->where('status', InstructorSessionTracking::STATUS_REVIEWED)->count(),
        ];

        return view('academic.instructor-tracking.index', compact(
            'schedules',
            'trackingMap',
            'batches',
            'instructors',
            'stats'
        ));
    }

    public function show(InstructorSchedule $schedule): View
    {
        $this->loadScheduleContext($schedule);

        $tracking = $this->findOrCreateTracking($schedule);
        $this->syncTrackingItems($tracking, $schedule);

        $tracking->load([
            'items' => fn ($query) => $query
                ->with(['subTopic.topic.module.stage'])
                ->orderBy('sort_order')
                ->orderBy('id'),
            'instructor:id,name,email',
            'batch:id,name,program_id',
            'program:id,name',
        ]);

        $curriculumTree = $this->buildCurriculumTree($tracking);

        return view('academic.instructor-tracking.show', compact(
            'schedule',
            'tracking',
            'curriculumTree'
        ));
    }

    public function checkIn(Request $request, InstructorSchedule $schedule): JsonResponse
    {
        try {
            $tracking = DB::transaction(function () use ($schedule) {
                $this->loadScheduleContext($schedule);

                $tracking = $this->findOrCreateTracking($schedule);

                if ($tracking->checked_in_at) {
                    $this->syncTrackingItems($tracking, $schedule);

                    return $tracking->fresh(['items']);
                }

                $checkedInAt = now();

                $tracking->forceFill([
                    'checked_in_at' => $checkedInAt,
                    'late_minutes' => $this->calculateLateMinutes($schedule, $checkedInAt),
                    'status' => InstructorSessionTracking::STATUS_CHECKED_IN,
                ])->save();

                $this->syncTrackingItems($tracking, $schedule);

                return $tracking->fresh(['items']);
            });

            return response()->json([
                'success' => true,
                'message' => 'Check-in berhasil. Silakan lanjut isi coverage materi sesi ini.',
                'data' => $this->trackingPayload($tracking),
            ]);
        } catch (Throwable $e) {
            report($e);

            return response()->json([
                'success' => false,
                'message' => 'Check-in gagal. Silakan coba lagi.',
            ], 500);
        }
    }

    public function saveDraft(Request $request, InstructorSchedule $schedule): JsonResponse
    {
        try {
            $validated = $this->validateTrackingRequest($request, false);

            $tracking = DB::transaction(function () use ($schedule, $validated) {
                $this->loadScheduleContext($schedule);

                $tracking = $this->findOrCreateTracking($schedule);
                $this->ensureCheckedIn($tracking);
                $this->syncTrackingItems($tracking, $schedule);

                $this->saveTrackingContent($tracking, $validated, false);

                if (! in_array($tracking->status, [
                    InstructorSessionTracking::STATUS_SUBMITTED,
                    InstructorSessionTracking::STATUS_REVIEWED,
                ], true)) {
                    $tracking->status = InstructorSessionTracking::STATUS_DRAFT;
                    $tracking->save();
                }

                return $tracking->fresh(['items']);
            });

            return response()->json([
                'success' => true,
                'message' => 'Draft tracking berhasil disimpan.',
                'data' => $this->trackingPayload($tracking),
            ]);
        } catch (ValidationException $e) {
            throw $e;
        } catch (Throwable $e) {
            report($e);

            return response()->json([
                'success' => false,
                'message' => $e->getMessage() ?: 'Draft gagal disimpan.',
            ], 500);
        }
    }

    public function checkOut(Request $request, InstructorSchedule $schedule): JsonResponse
    {
        try {
            $validated = $this->validateTrackingRequest($request, true);

            $tracking = DB::transaction(function () use ($schedule, $validated) {
                $this->loadScheduleContext($schedule);

                $tracking = $this->findOrCreateTracking($schedule);
                $this->ensureCheckedIn($tracking);
                $this->syncTrackingItems($tracking, $schedule);

                $this->saveTrackingContent($tracking, $validated, true);
                $this->recalculateCoverage($tracking);

                $checkedOutAt = now();
                $actualDuration = $tracking->checked_in_at
                    ? max(0, $tracking->checked_in_at->diffInMinutes($checkedOutAt))
                    : 0;

                $tracking->forceFill([
                    'checked_out_at' => $checkedOutAt,
                    'actual_duration_minutes' => $actualDuration,
                    'status' => InstructorSessionTracking::STATUS_SUBMITTED,
                    'submitted_at' => $checkedOutAt,
                ])->save();

                return $tracking->fresh(['items']);
            });

            return response()->json([
                'success' => true,
                'message' => 'Check-out berhasil. Tracking sesi sudah dikirim ke Academic Team.',
                'data' => $this->trackingPayload($tracking),
                'redirect_url' => route('instructor-tracking.show', $schedule),
            ]);
        } catch (ValidationException $e) {
            throw $e;
        } catch (Throwable $e) {
            report($e);

            return response()->json([
                'success' => false,
                'message' => $e->getMessage() ?: 'Check-out gagal. Silakan coba lagi.',
            ], 500);
        }
    }

    private function loadScheduleContext(InstructorSchedule $schedule): void
    {
        $relations = [
            'instructor:id,name,email',
            'batch:id,name,program_id',
            'batch.program:id,name',
        ];

        if (method_exists($schedule, 'replacementInstructor')) {
            $relations[] = 'replacementInstructor:id,name,email';
        }

        if (method_exists($schedule, 'program')) {
            $relations[] = 'program:id,name';
        }

        $schedule->loadMissing($relations);

        if (method_exists($schedule, 'subTopics')) {
            $schedule->loadMissing([
                'subTopics' => function ($query) {
                    $query
                        ->with(['topic.module.stage'])
                        ->when(Schema::hasColumn('sub_topics', 'is_active'), function ($query) {
                            $query->where('sub_topics.is_active', true);
                        })
                        ->orderBy('sub_topics.sort_order')
                        ->orderBy('sub_topics.id');
                },
            ]);
        }
    }

    private function findOrCreateTracking(InstructorSchedule $schedule): InstructorSessionTracking
    {
        $schedule->loadMissing(['batch:id,name,program_id']);

        $programId = $this->resolveProgramId($schedule);
        $instructorId = $schedule->replacement_instructor_id ?: $schedule->instructor_id;

        $tracking = InstructorSessionTracking::query()->firstOrNew([
            'instructor_schedule_id' => $schedule->id,
        ]);

        $payload = [
            'instructor_id' => $instructorId,
            'batch_id' => $schedule->batch_id,
            'program_id' => $programId,
            'session_date' => $schedule->schedule_date,
            'scheduled_start_time' => $schedule->start_time,
            'scheduled_end_time' => $schedule->end_time,
        ];

        if (! $tracking->exists) {
            $tracking->forceFill([
                ...$payload,
                'status' => InstructorSessionTracking::STATUS_PENDING,
            ])->save();

            return $tracking;
        }

        if (! in_array($tracking->status, [
            InstructorSessionTracking::STATUS_SUBMITTED,
            InstructorSessionTracking::STATUS_REVIEWED,
        ], true)) {
            $tracking->forceFill($payload)->save();
        }

        return $tracking;
    }

    private function syncTrackingItems(
        InstructorSessionTracking $tracking,
        ?InstructorSchedule $schedule = null
    ): void {
        if (in_array($tracking->status, [
            InstructorSessionTracking::STATUS_SUBMITTED,
            InstructorSessionTracking::STATUS_REVIEWED,
        ], true)) {
            return;
        }

        $schedule ??= InstructorSchedule::query()->find($tracking->instructor_schedule_id);

        if (! $schedule) {
            return;
        }

        $this->loadScheduleContext($schedule);

        $scheduledSubTopics = $this->resolveScheduledSubTopics($schedule);
        $scheduledSubTopicIds = $scheduledSubTopics
            ->pluck('id')
            ->map(fn ($id) => (int) $id)
            ->filter()
            ->unique()
            ->values();

        if ($scheduledSubTopicIds->isEmpty()) {
            $tracking->items()->delete();
            $this->recalculateCoverage($tracking);

            return;
        }

        $tracking->items()
            ->whereNotIn('sub_topic_id', $scheduledSubTopicIds->all())
            ->delete();

        $existingItems = $tracking->items()
            ->get()
            ->keyBy(fn ($item) => (int) $item->sub_topic_id);

        $sortOrder = 1;

        foreach ($scheduledSubTopics as $subTopic) {
            $subTopicId = (int) $subTopic->id;
            $existingItem = $existingItems->get($subTopicId);

            if ($existingItem) {
                if ((int) $existingItem->sort_order !== $sortOrder) {
                    $existingItem->forceFill([
                        'sort_order' => $sortOrder,
                    ])->save();
                }

                $sortOrder++;
                continue;
            }

            $tracking->items()->create([
                'sub_topic_id' => $subTopicId,
                'delivery_status' => InstructorSessionTrackingItem::STATUS_PENDING,
                'is_delivered' => false,
                'sort_order' => $sortOrder,
            ]);

            $sortOrder++;
        }

        $this->recalculateCoverage($tracking);
    }

    private function resolveScheduledSubTopics(InstructorSchedule $schedule)
    {
        $subTopics = collect();

        if ($schedule->relationLoaded('subTopics')) {
            $subTopics = collect($schedule->getRelation('subTopics'));
        }

        if ($subTopics->isEmpty()) {
            $scheduledSubTopicIds = $this->resolveScheduledSubTopicIds($schedule);

            if ($scheduledSubTopicIds->isNotEmpty()) {
                $subTopics = SubTopic::query()
                    ->whereIn('id', $scheduledSubTopicIds->all())
                    ->when(Schema::hasColumn('sub_topics', 'is_active'), function ($query) {
                        $query->where('is_active', true);
                    })
                    ->with(['topic.module.stage'])
                    ->get();
            }
        }

        if ($subTopics->isEmpty() && filled($schedule->sub_topic_id ?? null)) {
            $subTopics = SubTopic::query()
                ->where('id', $schedule->sub_topic_id)
                ->when(Schema::hasColumn('sub_topics', 'is_active'), function ($query) {
                    $query->where('is_active', true);
                })
                ->with(['topic.module.stage'])
                ->get();
        }

        return $this->sortScheduledSubTopics($subTopics);
    }

    private function resolveScheduledSubTopicIds(InstructorSchedule $schedule)
    {
        if (! Schema::hasTable('instructor_schedule_sub_topics')) {
            return collect();
        }

        if (
            ! Schema::hasColumn('instructor_schedule_sub_topics', 'instructor_schedule_id')
            || ! Schema::hasColumn('instructor_schedule_sub_topics', 'sub_topic_id')
        ) {
            return collect();
        }

        return DB::table('instructor_schedule_sub_topics')
            ->where('instructor_schedule_id', $schedule->id)
            ->pluck('sub_topic_id')
            ->map(fn ($id) => (int) $id)
            ->filter()
            ->unique()
            ->values();
    }

    private function sortScheduledSubTopics($subTopics)
    {
        return collect($subTopics)
            ->filter()
            ->sort(function ($a, $b) {
                $aOrder = [
                    $a->topic?->module?->stage?->sort_order ?? 0,
                    $a->topic?->module?->stage?->id ?? 0,
                    $a->topic?->module?->sort_order ?? 0,
                    $a->topic?->module?->id ?? 0,
                    $a->topic?->sort_order ?? 0,
                    $a->topic?->id ?? 0,
                    $a->sort_order ?? 0,
                    $a->id ?? 0,
                ];

                $bOrder = [
                    $b->topic?->module?->stage?->sort_order ?? 0,
                    $b->topic?->module?->stage?->id ?? 0,
                    $b->topic?->module?->sort_order ?? 0,
                    $b->topic?->module?->id ?? 0,
                    $b->topic?->sort_order ?? 0,
                    $b->topic?->id ?? 0,
                    $b->sort_order ?? 0,
                    $b->id ?? 0,
                ];

                return $aOrder <=> $bOrder;
            })
            ->values();
    }

    private function buildCurriculumTree(InstructorSessionTracking $tracking): array
    {
        $tree = [];

        foreach ($tracking->items as $item) {
            $subTopic = $item->subTopic;
            $topic = $subTopic?->topic;
            $module = $topic?->module;
            $stage = $module?->stage;

            $stageKey = $stage?->id ?: 'stage-empty';
            $moduleKey = $module?->id ?: 'module-empty';
            $topicKey = $topic?->id ?: 'topic-empty';

            $tree[$stageKey] ??= [
                'id' => $stage?->id,
                'name' => $stage?->name ?: 'Scheduled Materials',
                'modules' => [],
            ];

            $tree[$stageKey]['modules'][$moduleKey] ??= [
                'id' => $module?->id,
                'name' => $module?->name ?: 'Scheduled Module',
                'topics' => [],
            ];

            $tree[$stageKey]['modules'][$moduleKey]['topics'][$topicKey] ??= [
                'id' => $topic?->id,
                'name' => $topic?->name ?: 'Scheduled Topic',
                'items' => [],
            ];

            $tree[$stageKey]['modules'][$moduleKey]['topics'][$topicKey]['items'][] = $item;
        }

        return collect($tree)
            ->map(function ($stage) {
                $stage['modules'] = collect($stage['modules'])
                    ->map(function ($module) {
                        $module['topics'] = collect($module['topics'])
                            ->values()
                            ->all();

                        return $module;
                    })
                    ->values()
                    ->all();

                return $stage;
            })
            ->values()
            ->all();
    }

    private function validateTrackingRequest(Request $request, bool $isCheckout): array
    {
        $rules = [
            'session_notes' => [$isCheckout ? 'required' : 'nullable', 'string'],
            'issue_notes' => ['nullable', 'string'],
            'follow_up_notes' => ['nullable', 'string'],
            'items' => ['required', 'array'],
            'items.*.id' => ['required', 'integer', 'exists:instructor_session_tracking_items,id'],
            'items.*.sub_topic_id' => ['required', 'integer', 'exists:sub_topics,id'],
            'items.*.delivery_status' => ['required', 'in:pending,delivered,partial,not_delivered'],
            'items.*.not_delivered_reason' => ['nullable', 'string'],
            'items.*.delivery_notes' => ['nullable', 'string'],
        ];

        $validated = $request->validate($rules, [
            'session_notes.required' => 'Session notes wajib diisi sebelum check-out.',
            'items.required' => 'Scheduled material belum tersedia. Tambahkan sub topic di jadwal terlebih dahulu.',
            'items.array' => 'Format checklist scheduled material tidak valid.',
            'items.*.delivery_status.required' => 'Status delivery setiap sub topic wajib dipilih.',
        ]);

        if ($isCheckout) {
            foreach ($validated['items'] as $index => $item) {
                if (
                    in_array($item['delivery_status'], [
                        InstructorSessionTrackingItem::STATUS_PARTIAL,
                        InstructorSessionTrackingItem::STATUS_NOT_DELIVERED,
                    ], true)
                    && trim((string) ($item['not_delivered_reason'] ?? '')) === ''
                ) {
                    throw ValidationException::withMessages([
                        "items.{$index}.not_delivered_reason" => 'Reason wajib diisi untuk sub topic Partial atau Not Delivered.',
                    ]);
                }
            }
        }

        return $validated;
    }

    private function saveTrackingContent(InstructorSessionTracking $tracking, array $validated, bool $isCheckout): void
    {
        $tracking->forceFill([
            'session_notes' => $validated['session_notes'] ?? null,
            'issue_notes' => $validated['issue_notes'] ?? null,
            'follow_up_notes' => $validated['follow_up_notes'] ?? null,
        ])->save();

        foreach ($validated['items'] as $itemPayload) {
            $item = InstructorSessionTrackingItem::query()
                ->where('instructor_session_tracking_id', $tracking->id)
                ->where('id', $itemPayload['id'])
                ->where('sub_topic_id', $itemPayload['sub_topic_id'])
                ->first();

            if (! $item) {
                continue;
            }

            $deliveryStatus = $itemPayload['delivery_status'];

            $item->forceFill([
                'delivery_status' => $deliveryStatus,
                'is_delivered' => $deliveryStatus === InstructorSessionTrackingItem::STATUS_DELIVERED,
                'not_delivered_reason' => $itemPayload['not_delivered_reason'] ?? null,
                'delivery_notes' => $itemPayload['delivery_notes'] ?? null,
            ])->save();
        }

        $this->recalculateCoverage($tracking);
    }

    private function recalculateCoverage(InstructorSessionTracking $tracking): void
    {
        $items = $tracking->items()->get();
        $total = $items->count();

        if ($total === 0) {
            $tracking->coverage_percentage = 0;
            $tracking->save();

            return;
        }

        $coveredScore = $items->sum(function (InstructorSessionTrackingItem $item) {
            return match ($item->delivery_status) {
                InstructorSessionTrackingItem::STATUS_DELIVERED => 1,
                InstructorSessionTrackingItem::STATUS_PARTIAL => 0.5,
                default => 0,
            };
        });

        $tracking->coverage_percentage = round(($coveredScore / $total) * 100, 2);
        $tracking->save();
    }

    private function ensureCheckedIn(InstructorSessionTracking $tracking): void
    {
        if (! $tracking->checked_in_at) {
            throw ValidationException::withMessages([
                'checked_in_at' => 'Instructor harus check-in dulu sebelum mengisi tracking sesi.',
            ]);
        }

        if (in_array($tracking->status, [
            InstructorSessionTracking::STATUS_SUBMITTED,
            InstructorSessionTracking::STATUS_REVIEWED,
        ], true)) {
            throw ValidationException::withMessages([
                'status' => 'Tracking sesi ini sudah dikirim dan tidak bisa diubah.',
            ]);
        }
    }

    private function calculateLateMinutes(InstructorSchedule $schedule, Carbon $checkedInAt): int
    {
        if (! $schedule->schedule_date || ! $schedule->start_time) {
            return 0;
        }

        $sessionDate = $schedule->schedule_date instanceof \Carbon\CarbonInterface
            ? $schedule->schedule_date->format('Y-m-d')
            : Carbon::parse($schedule->schedule_date)->format('Y-m-d');

        $scheduledStart = Carbon::parse($sessionDate . ' ' . $schedule->start_time);

        if ($checkedInAt->lessThanOrEqualTo($scheduledStart)) {
            return 0;
        }

        return (int) $scheduledStart->diffInMinutes($checkedInAt);
    }

    private function resolveProgramId(InstructorSchedule $schedule): ?int
    {
        return $schedule->program_id
            ?? data_get($schedule, 'batch.program_id')
            ?? data_get($schedule, 'batch.program.id');
    }

    private function trackingPayload(InstructorSessionTracking $tracking): array
    {
        return [
            'id' => $tracking->id,
            'status' => $tracking->status,
            'status_label' => $tracking->status_label,
            'checked_in_at' => optional($tracking->checked_in_at)->format('d M Y H:i'),
            'checked_out_at' => optional($tracking->checked_out_at)->format('d M Y H:i'),
            'late_minutes' => $tracking->late_minutes,
            'actual_duration_minutes' => $tracking->actual_duration_minutes,
            'coverage_percentage' => (float) $tracking->coverage_percentage,
        ];
    }
}