<?php

namespace App\Http\Controllers\Academic;

use App\Http\Controllers\Controller;
use App\Models\Student;
use App\Models\SubTopic;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder as EloquentBuilder;
use Illuminate\Http\Request;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\View\View;

class StudentProgressMonitoringController extends Controller
{
    private int $perPage = 15;

    /**
     * Cache lesson berdasarkan kombinasi program + stage filter.
     */
    private array $assignedLessonCache = [];

    public function index(Request $request): View
    {
        $filters = [
            'search' => trim((string) $request->get('search', '')),
            'batch_id' => $request->get('batch_id'),
            'stage_id' => $request->get('stage_id'),
            'progress_range' => $request->get('progress_range'),
            'activity' => $request->get('activity'),
            'sort' => $request->get('sort', 'last_activity_desc'),
        ];

        $studentsQuery = $this->buildBaseStudentQuery($filters);

        if ($filters['search'] !== '') {
            $search = $filters['search'];

            $studentsQuery->where(function ($query) use ($search) {
                $query->where('students.full_name', 'like', "%{$search}%")
                    ->orWhere('students.email', 'like', "%{$search}%")
                    ->orWhere('students.phone', 'like', "%{$search}%");
            });
        }

        $mappedStudents = $studentsQuery
            ->orderBy('students.full_name')
            ->get()
            ->map(fn ($student) => $this->mapStudentProgressRow($student, $filters));

        $mappedStudents = $this->applyCollectionProgressRangeFilter(
            $mappedStudents,
            $filters['progress_range']
        );

        $mappedStudents = $this->applyCollectionActivityFilter(
            $mappedStudents,
            $filters['activity']
        );

        $mappedStudents = $this->applyCollectionSorting(
            $mappedStudents,
            $filters['sort']
        );

        $students = $this->paginateCollection($mappedStudents, $request);

        return view('academic.student-progress.index', [
            'students' => $students,
            'summary' => $this->getSummary($mappedStudents),
            'filters' => $filters,
            'totalLessons' => $mappedStudents->sum('total_lessons'),
            'batchOptions' => $this->getBatchOptions(),
            'stageOptions' => $this->getStageOptions(),
            'progressRangeOptions' => $this->getProgressRangeOptions(),
            'activityOptions' => $this->getActivityOptions(),
            'sortOptions' => $this->getSortOptions(),
        ]);
    }

    public function show(Student $student): View
    {
        $this->abortIfWorkshopStudent($student);

        $filters = [
            'batch_id' => request('batch_id'),
            'stage_id' => request('stage_id'),
        ];

        $assignedLessons = $this->getAssignedLessonsForStudent($student, $filters);

        $progressRows = $this->getProgressRowsForStudent(
            $student,
            $assignedLessons->pluck('id')->values()->all()
        );

        $lessons = $assignedLessons->map(function ($lesson) use ($progressRows) {
            $progress = $progressRows->get((int) $lesson->id);

            $progressPercentage = $progress
                ? $this->normalizePercentage((float) $progress->progress_percentage)
                : 0;

            $isCompleted = $progress
                ? ((bool) $progress->is_completed || $progressPercentage >= 95)
                : false;

            $durationSeconds = $progress?->duration_seconds
                ?: $lesson->video_duration_seconds
                ?: ($lesson->video_duration_minutes ? ((int) $lesson->video_duration_minutes * 60) : 0);

            $lesson->last_position_seconds = $progress?->last_position_seconds ?? 0;
            $lesson->duration_seconds = $durationSeconds;
            $lesson->progress_percentage = $progressPercentage;
            $lesson->progress_label = $this->formatPercentage($progressPercentage);
            $lesson->is_completed = $isCompleted;
            $lesson->completed_at = $progress?->completed_at;
            $lesson->last_watched_at = $progress?->last_watched_at;
            $lesson->duration_label = $this->formatDuration((int) $durationSeconds);
            $lesson->last_position_label = $this->formatDuration((int) ($progress?->last_position_seconds ?? 0));

            $lesson->lesson_status = $this->resolveLessonStatus(
                $progressPercentage,
                $isCompleted,
                $progress?->last_watched_at
            );

            $lesson->lesson_status_badge = $this->getLessonStatusBadge($lesson->lesson_status);
            $lesson->last_watched_label = $this->formatDateTime($progress?->last_watched_at);
            $lesson->completed_at_label = $this->formatDateTime($progress?->completed_at);

            return $lesson;
        });

        $totalLessons = $lessons->count();
        $completedLessons = $lessons->filter(fn ($lesson) => (bool) $lesson->is_completed)->count();
        $openedLessons = $lessons->filter(fn ($lesson) => ! empty($lesson->last_watched_at))->count();
        $totalProgressPercentage = $lessons->sum('progress_percentage');

        $overallProgress = $totalLessons > 0
            ? round($totalProgressPercentage / $totalLessons, 2)
            : 0;

        $lastActivity = $lessons
            ->pluck('last_watched_at')
            ->filter()
            ->sortDesc()
            ->first();

        $studentSummary = [
            'program_names' => $this->getStudentProgramNames($student, $filters),
            'batch_names' => $this->getStudentBatchNames($student, $filters),
            'total_lessons' => $totalLessons,
            'opened_lessons' => $openedLessons,
            'completed_lessons' => $completedLessons,
            'overall_progress' => $overallProgress,
            'overall_progress_label' => $this->formatPercentage($overallProgress),
            'last_activity' => $lastActivity,
            'last_activity_label' => $this->formatDateTime($lastActivity),
            'inactive_days' => $this->getInactiveDays($lastActivity),
            'monitoring_status' => $this->resolveMonitoringStatus(
                $overallProgress,
                $lastActivity,
                $completedLessons,
                $totalLessons
            ),
        ];

        $studentSummary['monitoring_status_badge'] = $this->getMonitoringStatusBadge(
            $studentSummary['monitoring_status']
        );

        return view('academic.student-progress.show', [
            'student' => $student,
            'lessons' => $lessons,
            'studentSummary' => $studentSummary,
        ]);
    }

    private function buildBaseStudentQuery(array $filters = []): EloquentBuilder
    {
        $selects = [
            'students.id',
            'students.user_id',
            'students.full_name',
            'students.email',
            'students.phone',
            'students.city',
            'students.current_status',
            'students.status',
            'students.created_at',
        ];

        if (Schema::hasColumn('students', 'source')) {
            $selects[] = 'students.source';
        }

        return Student::query()
            ->select($selects)
            ->when(Schema::hasColumn('students', 'source'), function ($query) {
                $query->where(function ($q) {
                    $q->whereNull('students.source')
                        ->orWhereRaw(
                            "LOWER(COALESCE(students.source, '')) NOT LIKE ?",
                            ['%workshop%']
                        );
                });
            })
            ->whereExists(function ($subQuery) use ($filters) {
                $subQuery->select(DB::raw(1))
                    ->from('student_enrollments')
                    ->join('programs', 'programs.id', '=', 'student_enrollments.program_id')
                    ->whereColumn('student_enrollments.student_id', 'students.id')
                    ->whereNotNull('student_enrollments.program_id')
                    ->whereIn('student_enrollments.status', ['active', 'completed', 'on_hold'])
                    ->where('student_enrollments.access_status', 'active')
                    ->where('programs.is_active', 1)
                    ->where(function ($q) {
                        $q->whereNull('programs.slug')
                            ->orWhereRaw("LOWER(programs.slug) NOT LIKE ?", ['%workshop%']);
                    })
                    ->where(function ($q) {
                        $q->whereNull('programs.name')
                            ->orWhereRaw("LOWER(programs.name) NOT LIKE ?", ['%workshop%']);
                    });

                if (! empty($filters['batch_id'])) {
                    $subQuery->where('student_enrollments.batch_id', $filters['batch_id']);
                }

                if (! empty($filters['stage_id'])) {
                    $subQuery->join('program_stages', 'program_stages.program_id', '=', 'programs.id')
                        ->where('program_stages.id', $filters['stage_id'])
                        ->where('program_stages.is_active', 1);
                }
            })
            ->when(Schema::hasTable('workshop_participants'), function ($query) {
                if (Schema::hasColumn('workshop_participants', 'student_id')) {
                    $query->whereNotExists(function ($subQuery) {
                        $subQuery->select(DB::raw(1))
                            ->from('workshop_participants')
                            ->whereColumn('workshop_participants.student_id', 'students.id');
                    });
                }

                if (
                    Schema::hasColumn('workshop_participants', 'email')
                    && Schema::hasColumn('students', 'email')
                ) {
                    $query->whereNotExists(function ($subQuery) {
                        $subQuery->select(DB::raw(1))
                            ->from('workshop_participants')
                            ->whereNotNull('workshop_participants.email')
                            ->whereRaw('LOWER(workshop_participants.email) = LOWER(students.email)');
                    });
                }
            });
    }

    private function mapStudentProgressRow(Student $student, array $filters = []): Student
    {
        $assignedLessons = $this->getAssignedLessonsForStudent($student, $filters);

        $lessonIds = $assignedLessons
            ->pluck('id')
            ->map(fn ($id) => (int) $id)
            ->filter()
            ->values()
            ->all();

        $progressRows = $this->getProgressRowsForStudent($student, $lessonIds);

        $totalLessons = count($lessonIds);
        $openedLessons = 0;
        $completedLessons = 0;
        $totalProgressPercentage = 0;
        $lastActivity = null;

        foreach ($lessonIds as $lessonId) {
            $progress = $progressRows->get($lessonId);

            if (! $progress) {
                continue;
            }

            $progressPercentage = $this->normalizePercentage((float) $progress->progress_percentage);

            $totalProgressPercentage += $progressPercentage;

            if (! empty($progress->last_watched_at) || $progressPercentage > 0 || (bool) $progress->is_completed) {
                $openedLessons++;
            }

            if ((bool) $progress->is_completed || $progressPercentage >= 95) {
                $completedLessons++;
            }

            if (! empty($progress->last_watched_at)) {
                if (
                    empty($lastActivity)
                    || Carbon::parse($progress->last_watched_at)->gt(Carbon::parse($lastActivity))
                ) {
                    $lastActivity = $progress->last_watched_at;
                }
            }
        }

        $overallProgress = $totalLessons > 0
            ? round($totalProgressPercentage / $totalLessons, 2)
            : 0;

        $student->program_names = $this->getStudentProgramNames($student, $filters);
        $student->program_names_label = $student->program_names->isNotEmpty()
            ? $student->program_names->implode(', ')
            : '-';

        $student->batch_names = $this->getStudentBatchNames($student, $filters);
        $student->batch_names_label = $student->batch_names->isNotEmpty()
            ? $student->batch_names->implode(', ')
            : '-';

        $student->total_lessons = $totalLessons;
        $student->opened_lessons = $openedLessons;
        $student->completed_lessons = $completedLessons;
        $student->total_progress_percentage = round($totalProgressPercentage, 2);
        $student->overall_progress = $overallProgress;
        $student->overall_progress_label = $this->formatPercentage($overallProgress);
        $student->last_activity = $lastActivity;
        $student->last_activity_label = $this->formatDateTime($lastActivity);
        $student->inactive_days = $this->getInactiveDays($lastActivity);

        $student->monitoring_status = $this->resolveMonitoringStatus(
            $overallProgress,
            $lastActivity,
            $completedLessons,
            $totalLessons
        );

        $student->monitoring_status_badge = $this->getMonitoringStatusBadge(
            $student->monitoring_status
        );

        return $student;
    }

    private function getAssignedLessonsForStudent(Student $student, array $filters = []): Collection
    {
        $programIds = $this->resolveStudentProgramIds($student, $filters);

        if (empty($programIds)) {
            return collect();
        }

        sort($programIds);

        $stageId = ! empty($filters['stage_id']) ? (int) $filters['stage_id'] : null;

        $cacheKey = implode('-', $programIds) . '|stage:' . ($stageId ?: 'all');

        if (array_key_exists($cacheKey, $this->assignedLessonCache)) {
            return $this->assignedLessonCache[$cacheKey];
        }

        $lessons = SubTopic::query()
            ->from('sub_topics')
            ->join('topics', 'topics.id', '=', 'sub_topics.topic_id')
            ->join('modules', 'modules.id', '=', 'topics.module_id')
            ->join('program_stages', 'program_stages.id', '=', 'modules.program_stage_id')
            ->join('programs', 'programs.id', '=', 'program_stages.program_id')
            ->whereIn('programs.id', $programIds)
            ->when($stageId, fn ($query) => $query->where('program_stages.id', $stageId))
            ->where('programs.is_active', 1)
            ->where('program_stages.is_active', 1)
            ->where('modules.is_active', 1)
            ->where('topics.is_active', 1)
            ->where('sub_topics.is_active', 1)
            ->where(function ($query) {
                $query->whereNull('programs.slug')
                    ->orWhereRaw("LOWER(programs.slug) NOT LIKE ?", ['%workshop%']);
            })
            ->where(function ($query) {
                $query->whereNull('programs.name')
                    ->orWhereRaw("LOWER(programs.name) NOT LIKE ?", ['%workshop%']);
            })
            ->select([
                'sub_topics.id',
                'sub_topics.topic_id',
                'sub_topics.name',
                'sub_topics.description',
                'sub_topics.content_format',
                'sub_topics.lesson_type',
                'sub_topics.video_provider',
                'sub_topics.video_duration_minutes',
                'sub_topics.video_duration_seconds',
                'sub_topics.sort_order',

                DB::raw('topics.name as topic_name'),
                DB::raw('modules.name as module_name'),
                DB::raw('program_stages.name as stage_name'),
                DB::raw('programs.name as program_name'),

                DB::raw('COALESCE(topics.sort_order, 0) as topic_sort_order'),
                DB::raw('COALESCE(modules.sort_order, 0) as module_sort_order'),
                DB::raw('COALESCE(program_stages.sort_order, 0) as stage_sort_order'),
            ])
            ->distinct()
            ->orderBy('stage_sort_order')
            ->orderBy('module_sort_order')
            ->orderBy('topic_sort_order')
            ->orderBy('sub_topics.sort_order')
            ->orderBy('sub_topics.id')
            ->get();

        $this->assignedLessonCache[$cacheKey] = $lessons;

        return $lessons;
    }

    private function resolveStudentProgramIds(Student $student, array $filters = []): array
    {
        if (! Schema::hasTable('student_enrollments')) {
            return [];
        }

        $query = DB::table('student_enrollments')
            ->where('student_enrollments.student_id', $student->id)
            ->whereNotNull('student_enrollments.program_id')
            ->whereIn('student_enrollments.status', [
                'active',
                'completed',
                'on_hold',
            ])
            ->where('student_enrollments.access_status', 'active');

        if (! empty($filters['batch_id'])) {
            $query->where('student_enrollments.batch_id', $filters['batch_id']);
        }

        if (! empty($filters['stage_id'])) {
            $query->join('program_stages', 'program_stages.program_id', '=', 'student_enrollments.program_id')
                ->where('program_stages.id', $filters['stage_id'])
                ->where('program_stages.is_active', 1);
        }

        $programIds = $query
            ->pluck('student_enrollments.program_id')
            ->map(fn ($id) => (int) $id)
            ->filter(fn ($id) => $id > 0)
            ->unique()
            ->values()
            ->all();

        return $this->excludeWorkshopProgramIds($programIds);
    }

    private function excludeWorkshopProgramIds(array $programIds): array
    {
        if (empty($programIds)) {
            return [];
        }

        return DB::table('programs')
            ->whereIn('id', $programIds)
            ->where('is_active', 1)
            ->where(function ($query) {
                $query->whereNull('slug')
                    ->orWhereRaw("LOWER(slug) NOT LIKE ?", ['%workshop%']);
            })
            ->where(function ($query) {
                $query->whereNull('name')
                    ->orWhereRaw("LOWER(name) NOT LIKE ?", ['%workshop%']);
            })
            ->pluck('id')
            ->map(fn ($id) => (int) $id)
            ->values()
            ->all();
    }

    private function getStudentProgramNames(Student $student, array $filters = []): Collection
    {
        $programIds = $this->resolveStudentProgramIds($student, $filters);

        if (empty($programIds)) {
            return collect();
        }

        return DB::table('programs')
            ->whereIn('id', $programIds)
            ->where('is_active', 1)
            ->orderBy('name')
            ->pluck('name')
            ->filter()
            ->values();
    }

    private function getStudentBatchNames(Student $student, array $filters = []): Collection
    {
        if (! Schema::hasTable('student_enrollments')) {
            return collect();
        }

        $query = DB::table('student_enrollments')
            ->where('student_enrollments.student_id', $student->id)
            ->whereNotNull('student_enrollments.batch_id')
            ->whereIn('student_enrollments.status', ['active', 'completed', 'on_hold'])
            ->where('student_enrollments.access_status', 'active');

        if (! empty($filters['batch_id'])) {
            $query->where('student_enrollments.batch_id', $filters['batch_id']);
        }

        if (! empty($filters['stage_id'])) {
            $query->join('program_stages', 'program_stages.program_id', '=', 'student_enrollments.program_id')
                ->where('program_stages.id', $filters['stage_id'])
                ->where('program_stages.is_active', 1);
        }

        if (Schema::hasTable('batches')) {
            $query->leftJoin('batches', 'batches.id', '=', 'student_enrollments.batch_id');

            $batchNameColumn = collect([
                'name',
                'title',
                'batch_name',
                'label',
            ])->first(fn ($column) => Schema::hasColumn('batches', $column));

            if ($batchNameColumn) {
                return $query
                    ->orderBy("batches.{$batchNameColumn}")
                    ->pluck("batches.{$batchNameColumn}")
                    ->filter()
                    ->unique()
                    ->values();
            }
        }

        return $query
            ->pluck('student_enrollments.batch_id')
            ->map(fn ($id) => 'Batch #' . $id)
            ->unique()
            ->values();
    }

    private function getProgressRowsForStudent(Student $student, array $lessonIds): Collection
    {
        if (empty($lessonIds)) {
            return collect();
        }

        return DB::table('student_lesson_progresses')
            ->where('student_id', $student->id)
            ->whereIn('sub_topic_id', $lessonIds)
            ->get()
            ->keyBy(fn ($row) => (int) $row->sub_topic_id);
    }

    private function getBatchOptions(): Collection
    {
        if (! Schema::hasTable('student_enrollments')) {
            return collect();
        }

        $query = DB::table('student_enrollments')
            ->whereNotNull('student_enrollments.batch_id')
            ->where(function ($q) {
                $q->whereNull('student_enrollments.status')
                    ->orWhere('student_enrollments.status', '!=', 'cancelled');
            });

        if (Schema::hasTable('batches')) {
            $query->leftJoin('batches', 'batches.id', '=', 'student_enrollments.batch_id');

            $batchNameColumn = collect([
                'name',
                'title',
                'batch_name',
                'label',
            ])->first(fn ($column) => Schema::hasColumn('batches', $column));

            $nameSelect = $batchNameColumn
                ? "COALESCE(batches.{$batchNameColumn}, CONCAT('Batch #', student_enrollments.batch_id)) as name"
                : "CONCAT('Batch #', student_enrollments.batch_id) as name";

            return $query
                ->select([
                    DB::raw('student_enrollments.batch_id as id'),
                    DB::raw($nameSelect),
                ])
                ->distinct()
                ->orderBy('name')
                ->get();
        }

        return $query
            ->select([
                DB::raw('student_enrollments.batch_id as id'),
                DB::raw("CONCAT('Batch #', student_enrollments.batch_id) as name"),
            ])
            ->distinct()
            ->orderBy('student_enrollments.batch_id')
            ->get();
    }

    private function getStageOptions(): Collection
    {
        if (
            ! Schema::hasTable('program_stages')
            || ! Schema::hasTable('programs')
            || ! Schema::hasTable('student_enrollments')
        ) {
            return collect();
        }

        $programIds = DB::table('student_enrollments')
            ->whereNotNull('program_id')
            ->where(function ($q) {
                $q->whereNull('status')
                    ->orWhere('status', '!=', 'cancelled');
            })
            ->pluck('program_id')
            ->map(fn ($id) => (int) $id)
            ->filter(fn ($id) => $id > 0)
            ->unique()
            ->values();

        $query = DB::table('program_stages')
            ->join('programs', 'programs.id', '=', 'program_stages.program_id');

        if ($programIds->isNotEmpty()) {
            $query->whereIn('program_stages.program_id', $programIds);
        }

        if (Schema::hasColumn('programs', 'is_active')) {
            $query->where(function ($q) {
                $q->whereNull('programs.is_active')
                    ->orWhere('programs.is_active', 1);
            });
        }

        if (Schema::hasColumn('program_stages', 'is_active')) {
            $query->where(function ($q) {
                $q->whereNull('program_stages.is_active')
                    ->orWhere('program_stages.is_active', 1);
            });
        }

        $query
            ->where(function ($q) {
                $q->whereNull('programs.slug')
                    ->orWhereRaw("LOWER(programs.slug) NOT LIKE ?", ['%workshop%']);
            })
            ->where(function ($q) {
                $q->whereNull('programs.name')
                    ->orWhereRaw("LOWER(programs.name) NOT LIKE ?", ['%workshop%']);
            });

        return $query
            ->select([
                'program_stages.id',
                'program_stages.program_id',
                DB::raw('programs.name as program_name'),
                DB::raw('program_stages.name as stage_name'),
                DB::raw('COALESCE(program_stages.sort_order, 0) as stage_sort_order'),
                DB::raw("CONCAT(programs.name, ' - ', program_stages.name) as name"),
            ])
            ->groupBy(
                'program_stages.id',
                'program_stages.program_id',
                'programs.name',
                'program_stages.name',
                'program_stages.sort_order'
            )
            ->orderBy('program_name')
            ->orderBy('stage_sort_order')
            ->orderBy('stage_name')
            ->get();
    }

    private function abortIfWorkshopStudent(Student $student): void
    {
        if (Schema::hasColumn('students', 'source')) {
            $source = strtolower((string) $student->source);

            if (str_contains($source, 'workshop')) {
                abort(404);
            }
        }

        if (Schema::hasTable('workshop_participants')) {
            if (
                Schema::hasColumn('workshop_participants', 'student_id')
                && DB::table('workshop_participants')->where('student_id', $student->id)->exists()
            ) {
                abort(404);
            }

            if (
                Schema::hasColumn('workshop_participants', 'email')
                && ! empty($student->email)
                && DB::table('workshop_participants')
                    ->whereRaw('LOWER(email) = LOWER(?)', [$student->email])
                    ->exists()
            ) {
                abort(404);
            }
        }
    }

    private function getSummary(Collection $rows): array
    {
        $students = $rows->count();

        $averageProgress = $students > 0
            ? round((float) $rows->avg('overall_progress'), 2)
            : 0;

        $completedStudents = $rows
            ->filter(fn ($student) => (float) $student->overall_progress >= 95)
            ->count();

        $needFollowUp = $rows
            ->filter(fn ($student) => $student->monitoring_status === 'Need Follow Up')
            ->count();

        $atRisk = $rows
            ->filter(fn ($student) => $student->monitoring_status === 'At Risk')
            ->count();

        $notStarted = $rows
            ->filter(fn ($student) => $student->monitoring_status === 'Not Started')
            ->count();

        $inactiveStudents = $rows
            ->filter(fn ($student) => is_null($student->last_activity) || (int) $student->inactive_days >= 7)
            ->count();

        return [
            'active_students' => $students,
            'total_lessons' => $rows->sum('total_lessons'),
            'average_progress' => $averageProgress,
            'average_progress_label' => $this->formatPercentage($averageProgress),
            'completed_students' => $completedStudents,
            'need_follow_up' => $needFollowUp,
            'at_risk' => $atRisk,
            'not_started' => $notStarted,
            'inactive_students' => $inactiveStudents,
        ];
    }

    private function applyCollectionProgressRangeFilter(Collection $students, ?string $progressRange): Collection
    {
        if (empty($progressRange)) {
            return $students;
        }

        return $students->filter(function ($student) use ($progressRange) {
            $progress = (float) $student->overall_progress;

            return match ($progressRange) {
                'not_started' => $progress <= 0,
                '0_25' => $progress > 0 && $progress <= 25,
                '26_50' => $progress > 25 && $progress <= 50,
                '51_75' => $progress > 50 && $progress <= 75,
                '76_94' => $progress > 75 && $progress < 95,
                'completed' => $progress >= 95,
                default => true,
            };
        })->values();
    }

    private function applyCollectionActivityFilter(Collection $students, ?string $activity): Collection
    {
        if (empty($activity)) {
            return $students;
        }

        return $students->filter(function ($student) use ($activity) {
            $lastActivity = $student->last_activity;

            return match ($activity) {
                'today' => ! empty($lastActivity)
                    && Carbon::parse($lastActivity)->isSameDay(now()),

                'inactive_3_days' => empty($lastActivity)
                    || Carbon::parse($lastActivity)->lt(now()->subDays(3)->startOfDay()),

                'inactive_7_days' => empty($lastActivity)
                    || Carbon::parse($lastActivity)->lt(now()->subDays(7)->startOfDay()),

                'no_activity' => empty($lastActivity),

                default => true,
            };
        })->values();
    }

    private function applyCollectionSorting(Collection $students, ?string $sort): Collection
    {
        $sorted = match ($sort) {
            'name_asc' => $students->sortBy(fn ($student) => strtolower((string) $student->full_name)),

            'progress_asc' => $students->sortBy(fn ($student) => (float) $student->overall_progress),

            'progress_desc' => $students->sortByDesc(fn ($student) => (float) $student->overall_progress),

            'completed_desc' => $students->sortByDesc(fn ($student) => (int) $student->completed_lessons),

            'last_activity_asc' => $students->sortBy(function ($student) {
                return $student->last_activity
                    ? Carbon::parse($student->last_activity)->timestamp
                    : PHP_INT_MAX;
            }),

            default => $students->sortByDesc(function ($student) {
                return $student->last_activity
                    ? Carbon::parse($student->last_activity)->timestamp
                    : 0;
            }),
        };

        return $sorted->values();
    }

    private function paginateCollection(Collection $items, Request $request): LengthAwarePaginator
    {
        $page = LengthAwarePaginator::resolveCurrentPage();
        $perPage = $this->perPage;

        $pageItems = $items
            ->slice(($page - 1) * $perPage, $perPage)
            ->values();

        return new LengthAwarePaginator(
            $pageItems,
            $items->count(),
            $perPage,
            $page,
            [
                'path' => $request->url(),
                'query' => $request->query(),
            ]
        );
    }

    private function resolveMonitoringStatus(
        float $progress,
        $lastActivity,
        int $completedLessons,
        int $totalLessons
    ): string {
        if ($totalLessons > 0 && $completedLessons >= $totalLessons) {
            return 'Completed';
        }

        if ($progress >= 95) {
            return 'Completed';
        }

        if (empty($lastActivity)) {
            return 'Not Started';
        }

        $inactiveDays = $this->getInactiveDays($lastActivity);

        if ($inactiveDays >= 7 && $progress < 80) {
            return 'Need Follow Up';
        }

        if ($inactiveDays >= 3 && $progress < 50) {
            return 'At Risk';
        }

        if ($progress >= 70) {
            return 'On Track';
        }

        return 'In Progress';
    }

    private function resolveLessonStatus(float $progress, bool $isCompleted, $lastWatchedAt): string
    {
        if ($isCompleted || $progress >= 95) {
            return 'Completed';
        }

        if (empty($lastWatchedAt) || $progress <= 0) {
            return 'Not Started';
        }

        return 'In Progress';
    }

    private function getInactiveDays($lastActivity): ?int
    {
        if (empty($lastActivity)) {
            return null;
        }

        return max(0, (int) Carbon::parse($lastActivity)->diffInDays(now()));
    }

    private function normalizePercentage(float $value): float
    {
        return round(max(0, min(100, $value)), 2);
    }

    private function formatPercentage(float $value): string
    {
        return rtrim(rtrim(number_format($value, 2), '0'), '.') . '%';
    }

    private function formatDateTime($value): string
    {
        if (empty($value)) {
            return '-';
        }

        return Carbon::parse($value)->format('d M Y, H:i');
    }

    private function formatDuration(int $seconds): string
    {
        if ($seconds <= 0) {
            return '-';
        }

        $hours = intdiv($seconds, 3600);
        $minutes = intdiv($seconds % 3600, 60);
        $remainingSeconds = $seconds % 60;

        if ($hours > 0) {
            return sprintf('%02d:%02d:%02d', $hours, $minutes, $remainingSeconds);
        }

        return sprintf('%02d:%02d', $minutes, $remainingSeconds);
    }

    private function getMonitoringStatusBadge(string $status): string
    {
        return match ($status) {
            'Completed' => 'success',
            'On Track' => 'primary',
            'In Progress' => 'info',
            'At Risk' => 'warning',
            'Need Follow Up' => 'danger',
            'Not Started' => 'secondary',
            default => 'secondary',
        };
    }

    private function getLessonStatusBadge(string $status): string
    {
        return match ($status) {
            'Completed' => 'success',
            'In Progress' => 'info',
            'Not Started' => 'secondary',
            default => 'secondary',
        };
    }

    private function getProgressRangeOptions(): array
    {
        return [
            '' => 'All Progress',
            'not_started' => 'Not Started',
            '0_25' => '1% - 25%',
            '26_50' => '26% - 50%',
            '51_75' => '51% - 75%',
            '76_94' => '76% - 94%',
            'completed' => 'Completed',
        ];
    }

    private function getActivityOptions(): array
    {
        return [
            '' => 'All Activity',
            'today' => 'Active Today',
            'inactive_3_days' => 'Inactive 3+ Days',
            'inactive_7_days' => 'Inactive 7+ Days',
            'no_activity' => 'No Activity',
        ];
    }

    private function getSortOptions(): array
    {
        return [
            'last_activity_desc' => 'Latest Activity',
            'last_activity_asc' => 'Oldest Activity',
            'progress_desc' => 'Highest Progress',
            'progress_asc' => 'Lowest Progress',
            'completed_desc' => 'Most Completed Lessons',
            'name_asc' => 'Student Name A-Z',
        ];
    }
}