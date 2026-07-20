<?php

namespace App\Services\Dashboard;

use App\Services\Trello\TrelloDashboardStatsService;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;
use Throwable;

class AcademicDashboardService
{
    public function __construct(
        private readonly TrelloDashboardStatsService $trelloDashboardStatsService
    ) {
    }

    public function getData(): array
    {
        $today = Carbon::today();

        $capacityRows = $this->getBatchCapacityRows();
        $todaySessions = $this->getTodayInstructorSessions($today);
        $trackingRows = $this->getInstructorTrackingRows();
        $curriculumReadiness = $this->getCurriculumReadinessRows();
        $learningProgress = $this->getLearningProgressRows($capacityRows);
        $workloadStats = $this->getAcademicWorkloadStats($today);

        $trialStats = $this->getTrialStats();
        $upcomingTrialSchedules = $this->getUpcomingTrialSchedules();
        $trialParticipantStatusCounts = $this->getTrialParticipantStatusCounts();
        $trialFollowUpProgress = $this->getTrialFollowUpProgress();

        $workshopStats = $this->getWorkshopStats();
        $workshopParticipantStatusCounts = $this->getWorkshopParticipantStatusCounts();
        $workshopFollowUpProgress = $this->getWorkshopFollowUpProgress();
        $upcomingWorkshopSchedules = $this->getUpcomingWorkshopSchedules();

        $mentoringStats = $this->getMentoringStats($today);
        $mentoringStatusCounts = $this->getMentoringStatusCounts();
        $upcomingMentoringSessions = $this->getUpcomingMentoringSessions();

        $assignmentSubmissionStats = $this->getAssignmentSubmissionStats();
        $assignmentSubmissionStatusCounts = $this->getAssignmentSubmissionStatusCounts();
        $recentAssignmentSubmissions = $this->getRecentAssignmentSubmissions();

        $trelloAcademicStats = $this->getTrelloAcademicStats();
        $trelloDashboardStats = [
            'academic' => $trelloAcademicStats,
        ];

        $stats = $this->getStats($capacityRows, $todaySessions, $trackingRows);

        $alerts = $this->getAlerts(
            stats: $stats,
            capacityRows: $capacityRows,
            trackingRows: $trackingRows,
            workloadStats: $workloadStats,
            mentoringStats: $mentoringStats,
            assignmentSubmissionStats: $assignmentSubmissionStats,
            trialStats: $trialStats,
            workshopStats: $workshopStats,
            trelloAcademicStats: $trelloAcademicStats
        );

        $suggestedFocus = $this->getSuggestedFocus($alerts);

        $academicSummaryContext = [
            'stats' => $stats,
            'capacity_rows' => $capacityRows,
            'today_sessions' => $todaySessions,
            'tracking_rows' => $trackingRows,
            'curriculum_readiness' => $curriculumReadiness,
            'learning_progress' => $learningProgress,
            'workload_stats' => $workloadStats,
            'alerts' => $alerts,
            'suggested_focus' => $suggestedFocus,
            'trial_stats' => $trialStats,
            'trial_status_counts' => $trialParticipantStatusCounts,
            'trial_follow_up_progress' => $trialFollowUpProgress,
            'upcoming_trial_schedules' => $upcomingTrialSchedules,
            'workshop_stats' => $workshopStats,
            'workshop_status_counts' => $workshopParticipantStatusCounts,
            'workshop_follow_up_progress' => $workshopFollowUpProgress,
            'upcoming_workshop_schedules' => $upcomingWorkshopSchedules,
            'mentoring_stats' => $mentoringStats,
            'mentoring_status_counts' => $mentoringStatusCounts,
            'upcoming_mentoring_sessions' => $upcomingMentoringSessions,
            'assignment_submission_stats' => $assignmentSubmissionStats,
            'assignment_submission_status_counts' => $assignmentSubmissionStatusCounts,
            'recent_assignment_submissions' => $recentAssignmentSubmissions,

            'trello_academic_stats' => $trelloAcademicStats,
            'trello_dashboard_stats' => $trelloDashboardStats,

            // Backward compatibility for reusable widgets that still read camelCase.
            'trelloAcademicStats' => $trelloAcademicStats,
            'trelloDashboardStats' => $trelloDashboardStats,
        ];

        $academicSummary = $this->getAcademicSummary($academicSummaryContext);
        $academicAiSummaryText = $academicSummary['summary_text'] ?? '';

        return compact(
            'stats',
            'capacityRows',
            'todaySessions',
            'trackingRows',
            'curriculumReadiness',
            'learningProgress',
            'workloadStats',
            'alerts',
            'suggestedFocus',
            'trialStats',
            'upcomingTrialSchedules',
            'trialParticipantStatusCounts',
            'trialFollowUpProgress',
            'workshopStats',
            'workshopParticipantStatusCounts',
            'workshopFollowUpProgress',
            'upcomingWorkshopSchedules',
            'mentoringStats',
            'mentoringStatusCounts',
            'upcomingMentoringSessions',
            'assignmentSubmissionStats',
            'assignmentSubmissionStatusCounts',
            'recentAssignmentSubmissions',
            'trelloAcademicStats',
            'trelloDashboardStats',
            'academicSummaryContext',
            'academicSummary',
            'academicAiSummaryText'
        );
    }

    private function getTrelloAcademicStats(): array
    {
        try {
            return array_replace_recursive(
                $this->emptyTrelloDashboardStats(),
                $this->trelloDashboardStatsService->getStats('academic')
            );
        } catch (Throwable $exception) {
            Log::error('Failed to fetch Academic Trello dashboard stats.', [
                'source_key' => 'academic',
                'message' => $exception->getMessage(),
            ]);

            return $this->emptyTrelloDashboardStats(
                'Data Trello Academic belum bisa ditarik. Dashboard tetap aman, tetapi koneksi Trello atau proses sync perlu dicek.'
            );
        }
    }

    private function emptyTrelloDashboardStats(?string $insight = null): array
    {
        return [
            'source_key' => 'academic',
            'integration_name' => null,
            'department' => 'academic',
            'board_id' => null,
            'board_name' => null,
            'webhook_status' => 'inactive',
            'last_synced_at' => null,
            'last_webhook_at' => null,

            'summary' => [
                'total_open_cards' => 0,
                'active_work' => 0,
                'completed' => 0,
                'due_today' => 0,
                'overdue' => 0,
                'unmapped' => 0,
                'completion_rate' => 0,
                'active_work_rate' => 0,
            ],

            'statuses' => [
                'notes' => 0,
                'todo' => 0,
                'in_progress' => 0,
                'review' => 0,
                'scheduled' => 0,
                'done' => 0,
                'archived' => 0,
                'ignored' => 0,
            ],

            'due_today_cards' => [],
            'overdue_cards' => [],
            'active_cards' => [],
            'recent_cards' => [],

            'insight' => $insight ?: 'Trello Academic belum aktif atau belum memiliki data yang tersinkron.',
        ];
    }

    private function getStats(array $capacityRows, array $todaySessions, array $trackingRows): array
    {
        $activeBatches = collect($capacityRows)
            ->filter(fn ($row) => in_array(Str::lower((string) $row['status']), [
                'active',
                'running',
                'ongoing',
                'open',
                'preparing',
                'scheduled',
            ], true))
            ->count();

        $runningBatches = collect($capacityRows)
            ->filter(fn ($row) => in_array(Str::lower((string) $row['status']), [
                'active',
                'running',
                'ongoing',
            ], true))
            ->count();

        $preparingBatches = collect($capacityRows)
            ->filter(fn ($row) => in_array(Str::lower((string) $row['status']), [
                'preparing',
                'scheduled',
                'open',
            ], true))
            ->count();

        $totalCapacity = collect($capacityRows)->sum('capacity');
        $totalFilled = collect($capacityRows)->sum('filled');
        $seatUtilization = $totalCapacity > 0
            ? round(($totalFilled / $totalCapacity) * 100)
            : 0;

        $todayLiveSessions = collect($todaySessions)
            ->filter(fn ($row) => Str::contains(Str::lower((string) $row['title']), ['live', 'session']))
            ->count();

        $pendingTracking = collect($trackingRows)
            ->filter(fn ($row) => in_array(Str::lower((string) $row['status']), [
                'pending',
                'draft',
                'checked in',
                'checked_in',
                'waiting tracking',
            ], true))
            ->count();

        return [
            [
                'title' => 'Active Batches',
                'value' => number_format($activeBatches),
                'description' => "{$runningBatches} running, {$preparingBatches} preparing",
                'icon' => 'bi-people-fill',
                'raw_value' => $activeBatches,
            ],
            [
                'title' => 'Seat Utilization',
                'value' => $seatUtilization . '%',
                'description' => number_format($totalFilled) . ' filled from ' . number_format($totalCapacity) . ' seats',
                'icon' => 'bi-grid-3x3-gap-fill',
                'raw_value' => $seatUtilization,
            ],
            [
                'title' => 'Today Sessions',
                'value' => number_format(count($todaySessions)),
                'description' => "{$todayLiveSessions} live-related sessions today",
                'icon' => 'bi-calendar-event-fill',
                'raw_value' => count($todaySessions),
            ],
            [
                'title' => 'Pending Tracking',
                'value' => number_format($pendingTracking),
                'description' => 'Need instructor submission',
                'icon' => 'bi-clipboard-check-fill',
                'raw_value' => $pendingTracking,
            ],
        ];
    }

    private function getBatchCapacityRows(): array
    {
        if (! $this->tableExists('batches')) {
            return [];
        }

        $capacityColumn = $this->firstExistingColumn('batches', [
            'capacity',
            'seat_capacity',
            'quota',
            'max_students',
            'total_seats',
        ]);

        $statusColumn = $this->firstExistingColumn('batches', [
            'status',
            'batch_status',
        ]);

        $query = DB::table('batches as b');

        if (
            $this->tableExists('programs')
            && $this->columnExists('batches', 'program_id')
        ) {
            $query->leftJoin('programs as p', 'b.program_id', '=', 'p.id');
        }

        $query->select([
            'b.id',
            DB::raw($this->columnExists('batches', 'name') ? 'b.name as batch' : "CONCAT('Batch #', b.id) as batch"),
            DB::raw($capacityColumn ? "COALESCE(b.{$capacityColumn}, 0) as capacity" : '0 as capacity'),
            DB::raw($statusColumn ? "COALESCE(b.{$statusColumn}, 'Active') as status" : "'Active' as status"),
        ]);

        if (
            $this->tableExists('programs')
            && $this->columnExists('batches', 'program_id')
        ) {
            $query->addSelect(DB::raw("COALESCE(p.name, '-') as program"));
        } else {
            $query->addSelect(DB::raw("'-' as program"));
        }

        if ($statusColumn) {
            $query->whereIn("b.{$statusColumn}", [
                'active',
                'running',
                'ongoing',
                'open',
                'preparing',
                'scheduled',
            ]);
        }

        $batches = $query
            ->orderByDesc('b.id')
            ->limit(8)
            ->get();

        $filledMap = $this->getFilledSeatMap($batches->pluck('id')->all());

        return $batches
            ->map(function ($batch) use ($filledMap) {
                return [
                    'id' => (int) $batch->id,
                    'program' => $batch->program ?: '-',
                    'batch' => $batch->batch ?: 'Batch #' . $batch->id,
                    'capacity' => (int) $batch->capacity,
                    'filled' => (int) ($filledMap[$batch->id] ?? 0),
                    'status' => Str::headline((string) ($batch->status ?: 'Active')),
                ];
            })
            ->values()
            ->all();
    }

    private function getFilledSeatMap(array $batchIds): array
    {
        if (
            empty($batchIds)
            || ! $this->tableExists('student_enrollments')
            || ! $this->columnExists('student_enrollments', 'batch_id')
        ) {
            return [];
        }

        $studentColumn = $this->columnExists('student_enrollments', 'student_id')
            ? 'student_id'
            : 'id';

        $query = DB::table('student_enrollments')
            ->select('batch_id', DB::raw("COUNT(DISTINCT {$studentColumn}) as total"))
            ->whereIn('batch_id', $batchIds)
            ->groupBy('batch_id');

        if ($this->columnExists('student_enrollments', 'status')) {
            $query->whereIn('status', [
                'active',
                'ongoing',
                'enrolled',
                'approved',
                'paid',
                'completed',
            ]);
        }

        return $query
            ->pluck('total', 'batch_id')
            ->map(fn ($value) => (int) $value)
            ->all();
    }

    private function getTodayInstructorSessions(Carbon $today): array
    {
        if (! $this->tableExists('instructor_schedules')) {
            return [];
        }

        $query = DB::table('instructor_schedules as s');

        if ($this->tableExists('batches') && $this->columnExists('instructor_schedules', 'batch_id')) {
            $query->leftJoin('batches as b', 's.batch_id', '=', 'b.id');
        }

        if ($this->tableExists('programs')) {
            if ($this->columnExists('instructor_schedules', 'program_id')) {
                $query->leftJoin('programs as p', 's.program_id', '=', 'p.id');
            } elseif ($this->tableExists('batches') && $this->columnExists('batches', 'program_id')) {
                $query->leftJoin('programs as p', 'b.program_id', '=', 'p.id');
            }
        }

        if ($this->tableExists('instructors') && $this->columnExists('instructor_schedules', 'instructor_id')) {
            $query->leftJoin('instructors as i', 's.instructor_id', '=', 'i.id');
        }

        if ($this->tableExists('instructors') && $this->columnExists('instructor_schedules', 'replacement_instructor_id')) {
            $query->leftJoin('instructors as ri', 's.replacement_instructor_id', '=', 'ri.id');
        }

        $selects = [
            's.id',
            DB::raw($this->columnExists('instructor_schedules', 'session_title') ? "COALESCE(s.session_title, 'Untitled Session') as title" : "'Untitled Session' as title"),
            DB::raw($this->columnExists('instructor_schedules', 'start_time') ? 's.start_time as start_time' : 'NULL as start_time'),
            DB::raw($this->columnExists('instructor_schedules', 'end_time') ? 's.end_time as end_time' : 'NULL as end_time'),
            DB::raw($this->columnExists('instructor_schedules', 'status') ? "COALESCE(s.status, 'scheduled') as status" : "'scheduled' as status"),
        ];

        if ($this->tableExists('batches') && $this->columnExists('instructor_schedules', 'batch_id')) {
            $selects[] = DB::raw("COALESCE(b.name, '-') as batch");
        } else {
            $selects[] = DB::raw("'-' as batch");
        }

        if ($this->tableExists('instructors') && $this->columnExists('instructor_schedules', 'replacement_instructor_id')) {
            $selects[] = DB::raw("COALESCE(ri.name, i.name, '-') as instructor");
        } elseif ($this->tableExists('instructors') && $this->columnExists('instructor_schedules', 'instructor_id')) {
            $selects[] = DB::raw("COALESCE(i.name, '-') as instructor");
        } else {
            $selects[] = DB::raw("'-' as instructor");
        }

        $query->select($selects);

        if ($this->columnExists('instructor_schedules', 'schedule_date')) {
            $query->whereDate('s.schedule_date', $today->toDateString());
        }

        if ($this->columnExists('instructor_schedules', 'start_time')) {
            $query->orderBy('s.start_time');
        } else {
            $query->orderByDesc('s.id');
        }

        return $query
            ->limit(8)
            ->get()
            ->map(function ($session) {
                $start = $session->start_time ? Carbon::parse($session->start_time)->format('H:i') : '-';
                $end = $session->end_time ? Carbon::parse($session->end_time)->format('H:i') : '-';

                return [
                    'id' => (int) $session->id,
                    'time' => "{$start} - {$end}",
                    'title' => $session->title,
                    'batch' => $session->batch,
                    'instructor' => $session->instructor,
                    'status' => Str::headline((string) $session->status),
                ];
            })
            ->values()
            ->all();
    }

    private function getInstructorTrackingRows(): array
    {
        if (! $this->tableExists('instructor_session_trackings')) {
            return [];
        }

        $query = DB::table('instructor_session_trackings as t');

        if ($this->tableExists('instructor_schedules') && $this->columnExists('instructor_session_trackings', 'instructor_schedule_id')) {
            $query->leftJoin('instructor_schedules as s', 't.instructor_schedule_id', '=', 's.id');
        }

        if ($this->tableExists('batches') && $this->columnExists('instructor_session_trackings', 'batch_id')) {
            $query->leftJoin('batches as b', 't.batch_id', '=', 'b.id');
        }

        if ($this->tableExists('instructors') && $this->columnExists('instructor_session_trackings', 'instructor_id')) {
            $query->leftJoin('instructors as i', 't.instructor_id', '=', 'i.id');
        }

        $query->select([
            't.id',
            DB::raw($this->columnExists('instructor_session_trackings', 'coverage_percentage') ? 'COALESCE(t.coverage_percentage, 0) as coverage' : '0 as coverage'),
            DB::raw($this->columnExists('instructor_session_trackings', 'status') ? "COALESCE(t.status, 'pending') as status" : "'pending' as status"),
        ]);

        if ($this->tableExists('instructors') && $this->columnExists('instructor_session_trackings', 'instructor_id')) {
            $query->addSelect(DB::raw("COALESCE(i.name, '-') as instructor"));
        } else {
            $query->addSelect(DB::raw("'-' as instructor"));
        }

        if ($this->tableExists('instructor_schedules') && $this->columnExists('instructor_session_trackings', 'instructor_schedule_id')) {
            $query->addSelect(DB::raw("COALESCE(s.session_title, 'Untitled Session') as session"));
        } else {
            $query->addSelect(DB::raw("'Untitled Session' as session"));
        }

        if ($this->tableExists('batches') && $this->columnExists('instructor_session_trackings', 'batch_id')) {
            $query->addSelect(DB::raw("COALESCE(b.name, '-') as batch"));
        } else {
            $query->addSelect(DB::raw("'-' as batch"));
        }

        if ($this->columnExists('instructor_session_trackings', 'updated_at')) {
            $query->orderByDesc('t.updated_at');
        } else {
            $query->orderByDesc('t.id');
        }

        return $query
            ->limit(8)
            ->get()
            ->map(fn ($row) => [
                'id' => (int) $row->id,
                'instructor' => $row->instructor,
                'session' => $row->session,
                'batch' => $row->batch,
                'coverage' => round((float) $row->coverage, 2),
                'status' => Str::headline((string) $row->status),
            ])
            ->values()
            ->all();
    }

    private function getCurriculumReadinessRows(): array
    {
        if (! $this->tableExists('programs')) {
            return [];
        }

        $programs = DB::table('programs')
            ->select('id', 'name')
            ->orderBy('name')
            ->limit(8)
            ->get();

        return $programs
            ->map(function ($program) {
                $stageIds = $this->getIdsByColumn('stages', 'program_id', $program->id);
                $moduleIds = $this->getIdsByColumn('modules', 'stage_id', $stageIds);
                $topicIds = $this->getIdsByColumn('topics', 'module_id', $moduleIds);

                $subTopicQuery = $this->tableExists('sub_topics')
                    && $this->columnExists('sub_topics', 'topic_id')
                    ? DB::table('sub_topics')->whereIn('topic_id', $topicIds ?: [-1])
                    : null;

                $subTopicTotal = $subTopicQuery ? (clone $subTopicQuery)->count() : 0;

                $videoTotal = 0;
                $videoReady = 0;
                $liveTotal = 0;
                $liveReady = 0;

                if ($subTopicQuery && $this->columnExists('sub_topics', 'lesson_type')) {
                    $videoBase = (clone $subTopicQuery)->where('lesson_type', 'video');
                    $liveBase = (clone $subTopicQuery)->where('lesson_type', 'live_session');

                    $videoTotal = (clone $videoBase)->count();
                    $liveTotal = (clone $liveBase)->count();

                    if ($this->columnExists('sub_topics', 'video_url')) {
                        $videoReady = (clone $videoBase)
                            ->whereNotNull('video_url')
                            ->where('video_url', '<>', '')
                            ->count();
                    }

                    if ($this->columnExists('sub_topics', 'is_active')) {
                        $liveReady = (clone $liveBase)
                            ->where('is_active', true)
                            ->count();
                    } else {
                        $liveReady = $liveTotal;
                    }
                }

                return [
                    'program' => $program->name,
                    'modules' => count($moduleIds),
                    'topics' => count($topicIds),
                    'sub_topics' => $subTopicTotal,
                    'video_ready' => $videoTotal > 0 ? round(($videoReady / $videoTotal) * 100) : 0,
                    'live_ready' => $liveTotal > 0 ? round(($liveReady / $liveTotal) * 100) : 0,
                ];
            })
            ->values()
            ->all();
    }

    private function getLearningProgressRows(array $capacityRows): array
    {
        /**
         * Learning progress yang lebih akurat:
         * progress batch = rata-rata progress student di batch.
         * progress student = completed active sub topics / total active sub topics program.
         *
         * Ini menggantikan logic lama yang AVG(progress video/lesson record),
         * karena AVG progress per record bisa terlalu optimis, misalnya 99% hanya
         * karena video terakhir hampir selesai.
         */
        $enrollmentsTable = $this->firstExistingTable([
            'student_enrollments',
            'batch_students',
            'student_batches',
            'enrollments',
            'batch_enrollments',
        ]);

        $batchesTable = $this->firstExistingTable(['batches']);

        if (! $enrollmentsTable || ! $batchesTable) {
            return $this->fallbackLearningProgressRows($capacityRows);
        }

        $batchIdColumn = $this->firstExistingColumn($enrollmentsTable, ['batch_id']);
        $studentIdColumn = $this->firstExistingColumn($enrollmentsTable, ['student_id', 'user_id']);
        $enrollmentProgramIdColumn = $this->firstExistingColumn($enrollmentsTable, ['program_id']);
        $batchProgramIdColumn = $this->firstExistingColumn($batchesTable, ['program_id']);
        $batchNameColumn = $this->firstExistingColumn($batchesTable, ['name', 'title']);

        if (! $batchIdColumn || ! $studentIdColumn) {
            return $this->fallbackLearningProgressRows($capacityRows);
        }

        $batchNameExpression = $batchNameColumn
            ? 'COALESCE(b.' . $batchNameColumn . ', CONCAT("Batch #", b.id))'
            : 'CONCAT("Batch #", b.id)';

        $programIdExpression = $enrollmentProgramIdColumn
            ? 'se.' . $enrollmentProgramIdColumn
            : ($batchProgramIdColumn ? 'b.' . $batchProgramIdColumn : 'NULL');

        $batchQuery = DB::table($enrollmentsTable . ' as se')
            ->join($batchesTable . ' as b', 'se.' . $batchIdColumn, '=', 'b.id')
            ->select([
                'b.id as batch_id',
                DB::raw($batchNameExpression . ' as batch'),
                DB::raw($programIdExpression . ' as program_id'),
                DB::raw('COUNT(DISTINCT se.' . $studentIdColumn . ') as students'),
            ])
            ->groupBy('b.id')
            ->orderByDesc('b.id')
            ->limit(5);

        if ($batchNameColumn) {
            $batchQuery->groupBy('b.' . $batchNameColumn);
        }

        if ($programIdExpression !== 'NULL') {
            $batchQuery->groupByRaw($programIdExpression);
        }

        $this->applyEnrollmentStatusFilter($batchQuery, $enrollmentsTable, 'se');

        $batchRows = $batchQuery->get();

        if ($batchRows->isEmpty()) {
            return $this->fallbackLearningProgressRows($capacityRows);
        }

        $batchIds = $batchRows
            ->pluck('batch_id')
            ->map(fn ($value) => (int) $value)
            ->filter()
            ->values()
            ->all();

        $enrollmentQuery = DB::table($enrollmentsTable . ' as se')
            ->join($batchesTable . ' as b', 'se.' . $batchIdColumn, '=', 'b.id')
            ->select([
                DB::raw('se.' . $studentIdColumn . ' as student_id'),
                DB::raw('se.' . $batchIdColumn . ' as batch_id'),
                DB::raw($programIdExpression . ' as program_id'),
            ])
            ->whereIn('se.' . $batchIdColumn, $batchIds);

        $this->applyEnrollmentStatusFilter($enrollmentQuery, $enrollmentsTable, 'se');

        $enrollmentRows = $enrollmentQuery->get();

        $programIds = $enrollmentRows
            ->pluck('program_id')
            ->merge($batchRows->pluck('program_id'))
            ->map(fn ($value) => (int) $value)
            ->filter()
            ->unique()
            ->values()
            ->all();

        $studentIds = $enrollmentRows
            ->pluck('student_id')
            ->map(fn ($value) => (int) $value)
            ->filter()
            ->unique()
            ->values()
            ->all();

        $programNames = $this->getProgramNameMap($programIds);
        $subTopicIdsByProgram = $this->getActiveSubTopicIdsByProgram($programIds);

        $allSubTopicIds = collect($subTopicIdsByProgram)
            ->flatten()
            ->map(fn ($value) => (int) $value)
            ->filter()
            ->unique()
            ->values()
            ->all();

        $completedSubTopicMap = $this->getCompletedSubTopicIdsByStudent($studentIds, $allSubTopicIds);

        return $batchRows
            ->map(function ($batchRow) use (
                $enrollmentRows,
                $programNames,
                $subTopicIdsByProgram,
                $completedSubTopicMap
            ) {
                $batchId = (int) $batchRow->batch_id;
                $programId = (int) ($batchRow->program_id ?? 0);

                $batchEnrollmentRows = $enrollmentRows
                    ->filter(fn ($row) => (int) $row->batch_id === $batchId)
                    ->values();

                $studentProgressTotal = 0;
                $measurableStudents = 0;

                foreach ($batchEnrollmentRows as $enrollmentRow) {
                    $studentId = (int) $enrollmentRow->student_id;
                    $studentProgramId = (int) ($enrollmentRow->program_id ?? $programId);

                    $programSubTopicIds = $subTopicIdsByProgram[$studentProgramId] ?? [];
                    $totalSubTopics = count($programSubTopicIds);

                    if ($totalSubTopics <= 0) {
                        continue;
                    }

                    $programSubTopicSet = array_fill_keys($programSubTopicIds, true);
                    $studentCompletedSet = $completedSubTopicMap[$studentId] ?? [];
                    $completedCount = count(array_intersect_key($studentCompletedSet, $programSubTopicSet));

                    $studentProgressTotal += ($completedCount / $totalSubTopics) * 100;
                    $measurableStudents++;
                }

                $progress = $measurableStudents > 0
                    ? (int) round($studentProgressTotal / $measurableStudents)
                    : $this->getLegacyBatchProgressAverage($batchId, $batchEnrollmentRows->pluck('student_id')->all());

                $progress = min(100, max(0, $progress));

                return [
                    'batch' => $batchRow->batch,
                    'program' => $programNames[$programId] ?? '-',
                    'progress' => $progress,
                    'students' => (int) $batchRow->students,
                    'risk' => $progress >= 60 ? 'Low' : ($progress >= 30 ? 'Medium' : 'High'),
                ];
            })
            ->values()
            ->all();
    }

    private function fallbackLearningProgressRows(array $capacityRows): array
    {
        return collect($capacityRows)
            ->take(5)
            ->map(fn ($row) => [
                'batch' => $row['batch'] ?? '-',
                'program' => $row['program'] ?? '-',
                'progress' => 0,
                'students' => (int) ($row['filled'] ?? 0),
                'risk' => ((int) ($row['filled'] ?? 0)) > 0 ? 'Medium' : 'High',
            ])
            ->values()
            ->all();
    }

    private function applyEnrollmentStatusFilter($query, string $table, string $alias): void
    {
        if ($this->columnExists($table, 'status')) {
            $query->whereIn($alias . '.status', [
                'active',
                'ongoing',
                'enrolled',
                'approved',
                'paid',
                'completed',
            ]);
        }

        if ($this->columnExists($table, 'access_status')) {
            $query->whereIn($alias . '.access_status', [
                'active',
                'granted',
                'available',
            ]);
        }
    }

    private function getProgramNameMap(array $programIds): array
    {
        $programIds = collect($programIds)
            ->map(fn ($value) => (int) $value)
            ->filter()
            ->unique()
            ->values()
            ->all();

        if (empty($programIds) || ! $this->tableExists('programs')) {
            return [];
        }

        $nameColumn = $this->firstExistingColumn('programs', ['name', 'title']);
        if (! $nameColumn) {
            return [];
        }

        return DB::table('programs')
            ->whereIn('id', $programIds)
            ->pluck($nameColumn, 'id')
            ->map(fn ($value) => (string) $value)
            ->all();
    }

    private function getActiveSubTopicIdsByProgram(array $programIds): array
    {
        $programIds = collect($programIds)
            ->map(fn ($value) => (int) $value)
            ->filter()
            ->unique()
            ->values()
            ->all();

        if (empty($programIds)) {
            return [];
        }

        $subTopicsTable = $this->firstExistingTable(['sub_topics', 'subtopics', 'lessons']);
        $topicsTable = $this->firstExistingTable(['topics', 'module_topics']);
        $modulesTable = $this->firstExistingTable(['modules', 'program_modules']);
        $stagesTable = $this->firstExistingTable(['program_stages', 'stages']);

        if (! $subTopicsTable || ! $topicsTable || ! $modulesTable) {
            return [];
        }

        $subTopicTopicColumn = $this->firstExistingColumn($subTopicsTable, ['topic_id']);
        $topicModuleColumn = $this->firstExistingColumn($topicsTable, ['module_id']);
        $moduleProgramColumn = $this->firstExistingColumn($modulesTable, ['program_id']);
        $moduleStageColumn = $this->firstExistingColumn($modulesTable, ['program_stage_id', 'stage_id']);

        if (! $subTopicTopicColumn || ! $topicModuleColumn) {
            return [];
        }

        $query = DB::table($subTopicsTable . ' as st')
            ->join($topicsTable . ' as t', 'st.' . $subTopicTopicColumn, '=', 't.id')
            ->join($modulesTable . ' as m', 't.' . $topicModuleColumn, '=', 'm.id')
            ->select([
                'st.id as sub_topic_id',
            ]);

        if ($moduleProgramColumn) {
            $query->addSelect(DB::raw('m.' . $moduleProgramColumn . ' as program_id'))
                ->whereIn('m.' . $moduleProgramColumn, $programIds);
        } elseif ($moduleStageColumn && $stagesTable) {
            $stageProgramColumn = $this->firstExistingColumn($stagesTable, ['program_id']);

            if (! $stageProgramColumn) {
                return [];
            }

            $query->join($stagesTable . ' as ps', 'm.' . $moduleStageColumn, '=', 'ps.id')
                ->addSelect(DB::raw('ps.' . $stageProgramColumn . ' as program_id'))
                ->whereIn('ps.' . $stageProgramColumn, $programIds);

            $this->applyActiveContentFilter($query, $stagesTable, 'ps');
        } else {
            return [];
        }

        $this->applyActiveContentFilter($query, $subTopicsTable, 'st');
        $this->applyActiveContentFilter($query, $topicsTable, 't');
        $this->applyActiveContentFilter($query, $modulesTable, 'm');

        return $query
            ->get()
            ->groupBy('program_id')
            ->map(fn ($rows) => $rows
                ->pluck('sub_topic_id')
                ->map(fn ($value) => (int) $value)
                ->filter()
                ->unique()
                ->values()
                ->all())
            ->all();
    }

    private function applyActiveContentFilter($query, string $table, string $alias): void
    {
        if ($this->columnExists($table, 'is_active')) {
            $query->where($alias . '.is_active', true);
        } elseif ($this->columnExists($table, 'status')) {
            $query->whereIn($alias . '.status', [
                'active',
                'published',
                'open',
                'scheduled',
                'ready',
            ]);
        }
    }

    private function getCompletedSubTopicIdsByStudent(array $studentIds, array $subTopicIds): array
    {
        $studentIds = collect($studentIds)
            ->map(fn ($value) => (int) $value)
            ->filter()
            ->unique()
            ->values()
            ->all();

        $subTopicIds = collect($subTopicIds)
            ->map(fn ($value) => (int) $value)
            ->filter()
            ->unique()
            ->values()
            ->all();

        if (empty($studentIds) || empty($subTopicIds)) {
            return [];
        }

        $progressTable = $this->firstExistingTable([
            'student_lesson_progress',
            'student_lesson_progresses',
            'student_learning_progress',
            'learning_progress',
        ]);

        if (! $progressTable) {
            return [];
        }

        $studentColumn = $this->firstExistingColumn($progressTable, ['student_id', 'user_id']);
        $subTopicColumn = $this->firstExistingColumn($progressTable, [
            'sub_topic_id',
            'subtopic_id',
            'lesson_id',
        ]);

        if (! $studentColumn || ! $subTopicColumn) {
            return [];
        }

        $hasCompletionSignal = $this->hasLearningCompletionSignal($progressTable);
        if (! $hasCompletionSignal) {
            return [];
        }

        $query = DB::table($progressTable)
            ->select([
                DB::raw($studentColumn . ' as student_id'),
                DB::raw($subTopicColumn . ' as sub_topic_id'),
            ])
            ->whereIn($studentColumn, $studentIds)
            ->whereIn($subTopicColumn, $subTopicIds);

        $this->applyLearningCompletionFilter($query, $progressTable);

        $completedMap = [];

        foreach ($query->get() as $row) {
            $studentId = (int) $row->student_id;
            $subTopicId = (int) $row->sub_topic_id;

            $completedMap[$studentId][$subTopicId] = true;
        }

        return $completedMap;
    }

    private function hasLearningCompletionSignal(string $progressTable): bool
    {
        return $this->firstExistingColumn($progressTable, [
            'is_completed',
            'completed',
            'completed_at',
            'status',
            'progress_percentage',
            'percentage_watched',
            'progress',
            'completion_percentage',
        ]) !== null;
    }

    private function applyLearningCompletionFilter($query, string $progressTable): void
    {
        $progressColumns = array_values(array_filter([
            $this->columnExists($progressTable, 'progress_percentage') ? 'progress_percentage' : null,
            $this->columnExists($progressTable, 'percentage_watched') ? 'percentage_watched' : null,
            $this->columnExists($progressTable, 'progress') ? 'progress' : null,
            $this->columnExists($progressTable, 'completion_percentage') ? 'completion_percentage' : null,
        ]));

        $query->where(function ($query) use ($progressTable, $progressColumns) {
            if ($this->columnExists($progressTable, 'is_completed')) {
                $query->orWhere('is_completed', true);
            }

            if ($this->columnExists($progressTable, 'completed')) {
                $query->orWhere('completed', true);
            }

            if ($this->columnExists($progressTable, 'completed_at')) {
                $query->orWhereNotNull('completed_at');
            }

            if ($this->columnExists($progressTable, 'status')) {
                $query->orWhereIn('status', [
                    'completed',
                    'done',
                    'finished',
                    'passed',
                ]);
            }

            foreach ($progressColumns as $progressColumn) {
                $query->orWhere($progressColumn, '>=', 95);
            }
        });
    }

    private function getLegacyBatchProgressAverage(int $batchId, array $studentIds): int
    {
        $studentIds = collect($studentIds)
            ->map(fn ($value) => (int) $value)
            ->filter()
            ->unique()
            ->values()
            ->all();

        if (empty($studentIds)) {
            return 0;
        }

        $progressTable = $this->firstExistingTable([
            'student_lesson_progress',
            'student_lesson_progresses',
            'student_learning_progress',
            'learning_progress',
        ]);

        if (! $progressTable) {
            return 0;
        }

        $studentColumn = $this->firstExistingColumn($progressTable, ['student_id', 'user_id']);
        $progressColumn = $this->firstExistingColumn($progressTable, [
            'progress_percentage',
            'percentage_watched',
            'progress',
            'completion_percentage',
        ]);

        if (! $studentColumn || ! $progressColumn) {
            return 0;
        }

        $query = DB::table($progressTable)
            ->whereIn($studentColumn, $studentIds);

        $progressBatchColumn = $this->firstExistingColumn($progressTable, ['batch_id']);
        if ($progressBatchColumn) {
            $query->where($progressBatchColumn, $batchId);
        }

        return (int) round((float) $query->avg($progressColumn));
    }

    private function getAcademicWorkloadStats(Carbon $today): array
    {
        return [
            'pending_assessments' => $this->countPendingAssignmentSubmissions(),
            'report_cards' => $this->countPendingReportCards(),
            'certificates' => $this->countPendingCertificates(),
            'mentoring' => $this->countMentoringThisWeek($today),
        ];
    }

    private function countPendingAssignmentSubmissions(): int
    {
        if (! $this->tableExists('assignment_submissions')) {
            return 0;
        }

        $query = DB::table('assignment_submissions');

        if ($this->columnExists('assignment_submissions', 'status')) {
            $query->whereIn('status', [
                'submitted',
                'late',
                'pending',
                'waiting_review',
                'need_review',
            ]);
        }

        if ($this->columnExists('assignment_submissions', 'reviewed_at')) {
            $query->whereNull('reviewed_at');
        }

        return (int) $query->count();
    }

    private function countPendingReportCards(): int
    {
        if (! $this->tableExists('report_cards')) {
            return 0;
        }

        $query = DB::table('report_cards');

        if ($this->columnExists('report_cards', 'status')) {
            $query->whereIn('status', [
                'draft',
                'generated',
                'pending',
                'unpublished',
            ]);
        } elseif ($this->columnExists('report_cards', 'published_at')) {
            $query->whereNull('published_at');
        } elseif ($this->columnExists('report_cards', 'pdf_path')) {
            $query->whereNull('pdf_path');
        }

        return (int) $query->count();
    }

    private function countPendingCertificates(): int
    {
        if (! $this->tableExists('certificates')) {
            return 0;
        }

        $query = DB::table('certificates');

        if ($this->columnExists('certificates', 'status')) {
            $query->whereIn('status', [
                'draft',
                'pending',
                'generated',
                'unpublished',
            ]);
        } elseif ($this->columnExists('certificates', 'issued_date')) {
            $query->whereNull('issued_date');
        }

        return (int) $query->count();
    }

    private function countMentoringThisWeek(Carbon $today): int
    {
        if (! $this->tableExists('student_mentoring_sessions')) {
            return 0;
        }

        $dateExpression = $this->buildMentoringDateExpression();
        $query = DB::table('student_mentoring_sessions');

        if ($dateExpression) {
            $query
                ->whereRaw($dateExpression . ' >= ?', [$today->copy()->startOfWeek()->toDateTimeString()])
                ->whereRaw($dateExpression . ' <= ?', [$today->copy()->endOfWeek()->toDateTimeString()]);
        }

        return (int) $query->count();
    }

    private function getTrialStats(): array
    {
        $monthStart = now()->startOfMonth()->toDateString();
        $monthEnd = now()->endOfMonth()->toDateString();

        $themesTable = $this->firstExistingTable(['trial_themes']);
        $schedulesTable = $this->firstExistingTable(['trial_schedules']);
        $participantsTable = $this->firstExistingTable(['trial_participants']);

        $themesTotal = $themesTable ? (int) DB::table($themesTable)->count() : 0;
        $themesActive = 0;

        if ($themesTable) {
            $themesActiveQuery = DB::table($themesTable);

            if ($this->columnExists($themesTable, 'is_active')) {
                $themesActiveQuery->where('is_active', true);
            }

            $themesActive = (int) $themesActiveQuery->count();
        }

        $schedulesAllTime = 0;
        $schedulesThisMonth = 0;
        $schedulesActiveThisMonth = 0;

        if ($schedulesTable) {
            $scheduleDateColumn = $this->firstExistingColumn($schedulesTable, ['schedule_date', 'date', 'start_date', 'created_at']);
            $scheduleActiveColumn = $this->firstExistingColumn($schedulesTable, ['is_active', 'status']);

            $schedulesAllTime = (int) DB::table($schedulesTable)->count();

            $monthQuery = DB::table($schedulesTable);
            if ($scheduleDateColumn) {
                $monthQuery
                    ->whereDate($scheduleDateColumn, '>=', $monthStart)
                    ->whereDate($scheduleDateColumn, '<=', $monthEnd);
            }

            $schedulesThisMonth = (int) (clone $monthQuery)->count();

            $activeMonthQuery = clone $monthQuery;
            if ($scheduleActiveColumn === 'is_active') {
                $activeMonthQuery->where('is_active', true);
            } elseif ($scheduleActiveColumn === 'status') {
                $activeMonthQuery->whereIn('status', ['active', 'open', 'scheduled', 'published']);
            }

            $schedulesActiveThisMonth = (int) $activeMonthQuery->count();
        }

        $participantsAllTime = 0;
        $participantsThisMonth = 0;

        if ($participantsTable) {
            $dateColumn = $this->firstExistingColumn($participantsTable, ['registered_at', 'created_at']);
            $participantsAllTime = (int) DB::table($participantsTable)->count();

            $participantsQuery = DB::table($participantsTable);
            if ($dateColumn) {
                $participantsQuery
                    ->whereDate($dateColumn, '>=', $monthStart)
                    ->whereDate($dateColumn, '<=', $monthEnd);
            }

            $participantsThisMonth = (int) $participantsQuery->count();
        }

        return [
            'month_from' => $monthStart,
            'month_to' => $monthEnd,
            'themes_total' => $themesTotal,
            'themes_active' => $themesActive,
            'schedules_total' => $schedulesThisMonth,
            'schedules_active' => $schedulesActiveThisMonth,
            'participants_total' => $participantsThisMonth,
            'participants_new_this_month' => $participantsThisMonth,
            'schedules_all_time' => $schedulesAllTime,
            'schedules_this_month' => $schedulesThisMonth,
            'schedules_active_this_month' => $schedulesActiveThisMonth,
            'participants_all_time' => $participantsAllTime,
            'participants_this_month' => $participantsThisMonth,
        ];
    }

    private function getUpcomingTrialSchedules(): array
    {
        $table = $this->firstExistingTable(['trial_schedules']);
        if (! $table) {
            return [];
        }

        /**
         * Real schema:
         * trial_schedules: id, program_id, trial_theme_id, name, schedule_date, start_time,
         * end_time, quota, description, is_active.
         * trial_themes: id, program_id, name, slug, description, image, sort_order, is_active.
         */
        $dateColumn = $this->firstExistingColumn($table, ['schedule_date']);
        if (! $dateColumn) {
            return [];
        }

        $nameColumn = $this->firstExistingColumn($table, ['name']);
        $startTimeColumn = $this->firstExistingColumn($table, ['start_time']);
        $endTimeColumn = $this->firstExistingColumn($table, ['end_time']);
        $quotaColumn = $this->firstExistingColumn($table, ['quota']);
        $activeColumn = $this->firstExistingColumn($table, ['is_active']);
        $themeIdColumn = $this->firstExistingColumn($table, ['trial_theme_id']);
        $programIdColumn = $this->firstExistingColumn($table, ['program_id']);

        $query = DB::table($table . ' as ts')
            ->select([
                'ts.id',
                DB::raw(($nameColumn ? 'ts.' . $nameColumn : "'Trial Schedule'") . ' as schedule_title'),
                DB::raw('ts.' . $dateColumn . ' as schedule_date'),
                DB::raw(($startTimeColumn ? 'ts.' . $startTimeColumn : 'NULL') . ' as start_time'),
                DB::raw(($endTimeColumn ? 'ts.' . $endTimeColumn : 'NULL') . ' as end_time'),
                DB::raw(($quotaColumn ? 'ts.' . $quotaColumn : '0') . ' as quota'),
            ])
            ->whereDate('ts.' . $dateColumn, '>=', now()->toDateString());

        if ($activeColumn) {
            $query->where('ts.' . $activeColumn, true);
        }

        if ($themeIdColumn && $this->tableExists('trial_themes')) {
            $query->leftJoin('trial_themes as tt', 'ts.' . $themeIdColumn, '=', 'tt.id');

            $themeNameColumn = $this->firstExistingColumn('trial_themes', ['name']);
            $themeActiveColumn = $this->firstExistingColumn('trial_themes', ['is_active']);

            if ($themeNameColumn) {
                $query->addSelect(DB::raw('COALESCE(tt.' . $themeNameColumn . ', ' . ($nameColumn ? 'ts.' . $nameColumn : "'Trial Schedule'") . ') as theme'));
            } else {
                $query->addSelect(DB::raw(($nameColumn ? 'ts.' . $nameColumn : "'Trial Schedule'") . ' as theme'));
            }

            if ($themeActiveColumn) {
                $query->where(function ($query) use ($themeActiveColumn) {
                    $query
                        ->whereNull('tt.id')
                        ->orWhere('tt.' . $themeActiveColumn, true);
                });
            }
        } else {
            $query->addSelect(DB::raw(($nameColumn ? 'ts.' . $nameColumn : "'Trial Schedule'") . ' as theme'));
        }

        if ($programIdColumn && $this->tableExists('programs')) {
            $query->leftJoin('programs as p', 'ts.' . $programIdColumn, '=', 'p.id');

            $programNameColumn = $this->firstExistingColumn('programs', ['name', 'title']);
            if ($programNameColumn) {
                $query->addSelect(DB::raw('COALESCE(p.' . $programNameColumn . ', \'-\') as program'));
            } else {
                $query->addSelect(DB::raw("'-' as program"));
            }
        } else {
            $query->addSelect(DB::raw("'-' as program"));
        }

        return $query
            ->orderBy('ts.' . $dateColumn)
            ->when($startTimeColumn, fn ($query) => $query->orderBy('ts.' . $startTimeColumn))
            ->limit(5)
            ->get()
            ->map(fn ($row) => [
                'id' => (int) $row->id,

                // Standardized output for Blade.
                'title' => $row->theme ?: ($row->schedule_title ?: 'Trial Schedule'),
                'subtitle' => $row->schedule_title ?: null,
                'theme' => $row->theme ?: '-',
                'program' => $row->program ?? '-',

                'schedule_date' => $row->schedule_date,
                'start_time' => $row->start_time,
                'end_time' => $row->end_time,
                'quota' => (int) $row->quota,
            ])
            ->values()
            ->all();
    }
    private function getTrialParticipantStatusCounts(): array
    {
        $defaults = [
            'registered' => 0,
            'contacted' => 0,
            'confirmed' => 0,
            'attended' => 0,
            'cancelled' => 0,
            'no_show' => 0,
        ];

        $table = $this->firstExistingTable(['trial_participants']);
        if (! $table) {
            return $defaults;
        }

        $statusColumn = $this->firstExistingColumn($table, ['status']);
        if (! $statusColumn) {
            return $defaults;
        }

        $dateColumn = $this->firstExistingColumn($table, ['registered_at', 'created_at']);
        $query = DB::table($table);

        if ($dateColumn) {
            $query
                ->whereDate($dateColumn, '>=', now()->startOfMonth()->toDateString())
                ->whereDate($dateColumn, '<=', now()->endOfMonth()->toDateString());
        }

        $counts = $query
            ->selectRaw($this->wrapColumn($statusColumn) . ' as status, COUNT(*) as total')
            ->groupBy($statusColumn)
            ->pluck('total', 'status')
            ->map(fn ($value) => (int) $value)
            ->all();

        return array_merge($defaults, $counts);
    }

    private function getTrialFollowUpProgress(): int
    {
        $table = $this->firstExistingTable(['trial_participants']);
        if (! $table) {
            return 0;
        }

        $statusColumn = $this->firstExistingColumn($table, ['status']);
        if (! $statusColumn) {
            return 0;
        }

        $dateColumn = $this->firstExistingColumn($table, ['registered_at', 'created_at']);
        $query = DB::table($table);

        if ($dateColumn) {
            $query
                ->whereDate($dateColumn, '>=', now()->startOfMonth()->toDateString())
                ->whereDate($dateColumn, '<=', now()->endOfMonth()->toDateString());
        }

        $total = (int) (clone $query)->count();
        if ($total <= 0) {
            return 0;
        }

        $followedUp = (int) (clone $query)
            ->whereIn($statusColumn, ['contacted', 'confirmed', 'attended'])
            ->count();

        return (int) round(($followedUp / $total) * 100);
    }

    private function getWorkshopStats(): array
    {
        $monthStart = now()->startOfMonth()->toDateString();
        $monthEnd = now()->endOfMonth()->toDateString();

        $workshopsTable = $this->firstExistingTable(['workshops']);
        $schedulesTable = $this->firstExistingTable(['workshop_schedules']);
        $participantsTable = $this->firstExistingTable(['workshop_participants']);
        $paymentSummary = $this->getWorkshopPaidPaymentSummary($monthStart, $monthEnd);
        $statusCounts = $this->getWorkshopParticipantStatusCounts();

        $stats = [
            'month_from' => $monthStart,
            'month_to' => $monthEnd,
            'workshops_total' => 0,
            'workshops_active' => 0,
            'schedules_all_time' => 0,
            'schedules_this_month' => 0,
            'schedules_active_this_month' => 0,
            'upcoming_schedules' => 0,
            'participants_all_time' => 0,
            'participants_this_month' => 0,
            'registered_this_month' => (int) ($statusCounts['registered'] ?? 0),
            'pending_payment_this_month' => (int) ($statusCounts['pending_payment'] ?? 0),
            'confirmed_this_month' => (int) ($statusCounts['confirmed'] ?? 0),
            'attended_this_month' => (int) ($statusCounts['attended'] ?? 0),
            'cancelled_this_month' => (int) ($statusCounts['cancelled'] ?? 0),
            'paid_count_this_month' => (int) ($paymentSummary['count'] ?? 0),
            'revenue_this_month' => (float) ($paymentSummary['total'] ?? 0),
            'conversion_percent' => 0,
            'attendance_percent' => 0,
            'top_source' => null,
            'top_source_total' => 0,
        ];

        if ($workshopsTable) {
            $activeColumn = $this->firstExistingColumn($workshopsTable, ['is_active', 'status']);
            $stats['workshops_total'] = (int) DB::table($workshopsTable)->count();

            $activeQuery = DB::table($workshopsTable);
            if ($activeColumn === 'is_active') {
                $activeQuery->where('is_active', true);
            } elseif ($activeColumn === 'status') {
                $activeQuery->whereIn('status', ['active', 'open', 'published']);
            }

            $stats['workshops_active'] = (int) $activeQuery->count();
        }

        if ($schedulesTable) {
            $dateColumn = $this->firstExistingColumn($schedulesTable, ['schedule_date', 'date', 'start_date', 'created_at']);
            $activeColumn = $this->firstExistingColumn($schedulesTable, ['is_active', 'status']);
            $stats['schedules_all_time'] = (int) DB::table($schedulesTable)->count();

            $monthQuery = DB::table($schedulesTable);
            if ($dateColumn) {
                $monthQuery
                    ->whereDate($dateColumn, '>=', $monthStart)
                    ->whereDate($dateColumn, '<=', $monthEnd);
            }

            $stats['schedules_this_month'] = (int) (clone $monthQuery)->count();

            $activeMonthQuery = clone $monthQuery;
            if ($activeColumn === 'is_active') {
                $activeMonthQuery->where('is_active', true);
            } elseif ($activeColumn === 'status') {
                $activeMonthQuery->whereIn('status', ['active', 'open', 'scheduled', 'published']);
            }

            $stats['schedules_active_this_month'] = (int) $activeMonthQuery->count();

            if ($dateColumn) {
                $upcomingQuery = DB::table($schedulesTable)
                    ->whereDate($dateColumn, '>=', now()->toDateString());

                if ($activeColumn === 'is_active') {
                    $upcomingQuery->where('is_active', true);
                } elseif ($activeColumn === 'status') {
                    $upcomingQuery->whereIn('status', ['active', 'open', 'scheduled', 'published']);
                }

                $stats['upcoming_schedules'] = (int) $upcomingQuery->count();
            }
        }

        if ($participantsTable) {
            $dateColumn = $this->firstExistingColumn($participantsTable, ['registered_at', 'created_at']);
            $sourceColumn = $this->firstExistingColumn($participantsTable, ['utm_source', 'input_source']);

            $stats['participants_all_time'] = (int) DB::table($participantsTable)->count();

            $participantQuery = DB::table($participantsTable);
            if ($dateColumn) {
                $participantQuery
                    ->whereDate($dateColumn, '>=', $monthStart)
                    ->whereDate($dateColumn, '<=', $monthEnd);
            }
            $stats['participants_this_month'] = (int) (clone $participantQuery)->count();

            if ($sourceColumn) {
                $sourceBaseQuery = DB::table($participantsTable)
                    ->selectRaw('COALESCE(NULLIF(' . $this->wrapColumn($sourceColumn) . ', ""), "unknown") as source_name');

                if ($dateColumn) {
                    $sourceBaseQuery
                        ->whereDate($dateColumn, '>=', $monthStart)
                        ->whereDate($dateColumn, '<=', $monthEnd);
                }

                $source = DB::query()
                    ->fromSub($sourceBaseQuery, 'workshop_sources')
                    ->selectRaw('source_name, COUNT(*) as total')
                    ->groupBy('source_name')
                    ->orderByDesc('total')
                    ->first();

                if ($source) {
                    $stats['top_source'] = $source->source_name;
                    $stats['top_source_total'] = (int) $source->total;
                }
            }
        }

        $participantsThisMonth = max((int) $stats['participants_this_month'], 0);
        $convertedThisMonth = (int) $stats['confirmed_this_month'] + (int) $stats['attended_this_month'];

        if ($participantsThisMonth > 0) {
            $stats['conversion_percent'] = (int) round(($convertedThisMonth / $participantsThisMonth) * 100);
            $stats['attendance_percent'] = (int) round(((int) $stats['attended_this_month'] / $participantsThisMonth) * 100);
        }

        return $stats;
    }

    private function getWorkshopParticipantStatusCounts(): array
    {
        $defaults = [
            'registered' => 0,
            'pending_payment' => 0,
            'confirmed' => 0,
            'attended' => 0,
            'cancelled' => 0,
        ];

        $table = $this->firstExistingTable(['workshop_participants']);
        if (! $table) {
            return $defaults;
        }

        $statusColumn = $this->firstExistingColumn($table, ['status']);
        if (! $statusColumn) {
            return $defaults;
        }

        $dateColumn = $this->firstExistingColumn($table, ['registered_at', 'created_at']);
        $query = DB::table($table);

        if ($dateColumn) {
            $query
                ->whereDate($dateColumn, '>=', now()->startOfMonth()->toDateString())
                ->whereDate($dateColumn, '<=', now()->endOfMonth()->toDateString());
        }

        $counts = $query
            ->selectRaw($this->wrapColumn($statusColumn) . ' as status, COUNT(*) as total')
            ->groupBy($statusColumn)
            ->pluck('total', 'status')
            ->map(fn ($value) => (int) $value)
            ->all();

        return array_merge($defaults, $counts);
    }

    private function getWorkshopFollowUpProgress(): int
    {
        $table = $this->firstExistingTable(['workshop_participants']);
        if (! $table) {
            return 0;
        }

        $statusColumn = $this->firstExistingColumn($table, ['status']);
        if (! $statusColumn) {
            return 0;
        }

        $dateColumn = $this->firstExistingColumn($table, ['registered_at', 'created_at']);
        $query = DB::table($table);

        if ($dateColumn) {
            $query
                ->whereDate($dateColumn, '>=', now()->startOfMonth()->toDateString())
                ->whereDate($dateColumn, '<=', now()->endOfMonth()->toDateString());
        }

        $total = (int) (clone $query)->count();
        if ($total <= 0) {
            return 0;
        }

        $converted = (int) (clone $query)
            ->whereIn($statusColumn, ['confirmed', 'attended'])
            ->count();

        return (int) round(($converted / $total) * 100);
    }

    private function getUpcomingWorkshopSchedules(): array
    {
        $table = $this->firstExistingTable(['workshop_schedules']);
        if (! $table) {
            return [];
        }

        /**
         * Real schema:
         * workshop_schedules: id, workshop_id, title, schedule_date, start_time, end_time,
         * location_type, location, quota, registered_count, status, is_active.
         * workshops: id, title, slug, badge, is_active.
         */
        $dateColumn = $this->firstExistingColumn($table, ['schedule_date']);
        if (! $dateColumn) {
            return [];
        }

        $titleColumn = $this->firstExistingColumn($table, ['title']);
        $startTimeColumn = $this->firstExistingColumn($table, ['start_time']);
        $endTimeColumn = $this->firstExistingColumn($table, ['end_time']);
        $locationTypeColumn = $this->firstExistingColumn($table, ['location_type']);
        $locationColumn = $this->firstExistingColumn($table, ['location']);
        $quotaColumn = $this->firstExistingColumn($table, ['quota']);
        $registeredColumn = $this->firstExistingColumn($table, ['registered_count']);
        $statusColumn = $this->firstExistingColumn($table, ['status']);
        $activeColumn = $this->firstExistingColumn($table, ['is_active']);
        $workshopIdColumn = $this->firstExistingColumn($table, ['workshop_id']);

        $query = DB::table($table . ' as ws')
            ->select([
                'ws.id',
                DB::raw(($titleColumn ? 'ws.' . $titleColumn : "'Workshop Schedule'") . ' as schedule_title'),
                DB::raw('ws.' . $dateColumn . ' as schedule_date'),
                DB::raw(($startTimeColumn ? 'ws.' . $startTimeColumn : 'NULL') . ' as start_time'),
                DB::raw(($endTimeColumn ? 'ws.' . $endTimeColumn : 'NULL') . ' as end_time'),
                DB::raw(($locationTypeColumn ? 'ws.' . $locationTypeColumn : "'online'") . ' as location_type'),
                DB::raw(($locationColumn ? 'ws.' . $locationColumn : 'NULL') . ' as location'),
                DB::raw(($quotaColumn ? 'ws.' . $quotaColumn : '0') . ' as quota'),
                DB::raw(($registeredColumn ? 'ws.' . $registeredColumn : '0') . ' as registered_count'),
            ])
            ->whereDate('ws.' . $dateColumn, '>=', now()->toDateString());

        if ($activeColumn) {
            $query->where('ws.' . $activeColumn, true);
        }

        if ($statusColumn) {
            // Workshop schedule schema status: draft, open, closed, completed, cancelled.
            // Upcoming dashboard should only show schedules that are still open.
            $query->where('ws.' . $statusColumn, 'open');
        }

        if ($workshopIdColumn && $this->tableExists('workshops')) {
            $query->leftJoin('workshops as w', 'ws.' . $workshopIdColumn, '=', 'w.id');

            $workshopTitleColumn = $this->firstExistingColumn('workshops', ['title']);
            $workshopSlugColumn = $this->firstExistingColumn('workshops', ['slug']);
            $workshopBadgeColumn = $this->firstExistingColumn('workshops', ['badge']);
            $workshopActiveColumn = $this->firstExistingColumn('workshops', ['is_active']);

            if ($workshopTitleColumn) {
                $query->addSelect(DB::raw('COALESCE(w.' . $workshopTitleColumn . ', ' . ($titleColumn ? 'ws.' . $titleColumn : "'Workshop Schedule'") . ') as workshop_title'));
            } else {
                $query->addSelect(DB::raw(($titleColumn ? 'ws.' . $titleColumn : "'Workshop Schedule'") . ' as workshop_title'));
            }

            if ($workshopSlugColumn) {
                $query->addSelect(DB::raw('w.' . $workshopSlugColumn . ' as workshop_slug'));
            } else {
                $query->addSelect(DB::raw('NULL as workshop_slug'));
            }

            if ($workshopBadgeColumn) {
                $query->addSelect(DB::raw('w.' . $workshopBadgeColumn . ' as workshop_badge'));
            } else {
                $query->addSelect(DB::raw('NULL as workshop_badge'));
            }

            if ($workshopActiveColumn) {
                $query->where(function ($query) use ($workshopActiveColumn) {
                    $query
                        ->whereNull('w.id')
                        ->orWhere('w.' . $workshopActiveColumn, true);
                });
            }
        } else {
            $query->addSelect(DB::raw(($titleColumn ? 'ws.' . $titleColumn : "'Workshop Schedule'") . ' as workshop_title'));
            $query->addSelect(DB::raw('NULL as workshop_slug'));
            $query->addSelect(DB::raw('NULL as workshop_badge'));
        }

        return $query
            ->orderBy('ws.' . $dateColumn)
            ->when($startTimeColumn, fn ($query) => $query->orderBy('ws.' . $startTimeColumn))
            ->limit(5)
            ->get()
            ->map(fn ($row) => [
                'id' => (int) $row->id,

                // Standardized output for Blade.
                'title' => $row->workshop_title ?: ($row->schedule_title ?: 'Workshop Schedule'),
                'subtitle' => $row->schedule_title ?: null,
                'workshop_title' => $row->workshop_title ?: '-',
                'workshop_slug' => $row->workshop_slug ?? null,
                'workshop_badge' => $row->workshop_badge ?? null,

                'schedule_date' => $row->schedule_date,
                'start_time' => $row->start_time,
                'end_time' => $row->end_time,
                'location_type' => $row->location_type ?? null,
                'location' => $row->location ?? null,
                'quota' => (int) $row->quota,
                'registered_count' => (int) $row->registered_count,
            ])
            ->values()
            ->all();
    }
    private function getWorkshopPaidPaymentSummary(string $dateFrom, string $dateTo): array
    {
        $paymentsTable = $this->firstExistingTable(['payments']);
        if (! $paymentsTable) {
            return ['count' => 0, 'total' => 0];
        }

        $amountColumn = $this->firstExistingColumn($paymentsTable, ['amount', 'paid_amount', 'total_amount']);
        $statusColumn = $this->firstExistingColumn($paymentsTable, ['status', 'payment_status']);
        $dateExpression = $this->buildPaymentDateExpression($paymentsTable);
        $orderIdColumn = $this->firstExistingColumn($paymentsTable, ['order_id']);

        if (! $amountColumn || ! $dateExpression) {
            return ['count' => 0, 'total' => 0];
        }

        $query = DB::table($paymentsTable)
            ->whereRaw('DATE(' . $dateExpression . ') >= ?', [$dateFrom])
            ->whereRaw('DATE(' . $dateExpression . ') <= ?', [$dateTo]);

        if ($statusColumn) {
            $query->whereIn($paymentsTable . '.' . $statusColumn, $this->getPaidPaymentStatuses());
        }

        if ($this->tableExists('orders') && $orderIdColumn) {
            $orderTypeColumn = $this->firstExistingColumn('orders', ['order_type']);
            $workshopIdColumn = $this->firstExistingColumn('orders', ['workshop_id']);

            $query->join('orders', 'orders.id', '=', $paymentsTable . '.' . $orderIdColumn);

            if ($orderTypeColumn && $workshopIdColumn) {
                $query->where(function ($query) use ($orderTypeColumn, $workshopIdColumn) {
                    $query
                        ->where('orders.' . $orderTypeColumn, 'workshop')
                        ->orWhereNotNull('orders.' . $workshopIdColumn);
                });
            } elseif ($orderTypeColumn) {
                $query->where('orders.' . $orderTypeColumn, 'workshop');
            } elseif ($workshopIdColumn) {
                $query->whereNotNull('orders.' . $workshopIdColumn);
            }
        }

        return [
            'count' => (int) (clone $query)->count(),
            'total' => (float) (clone $query)->sum($paymentsTable . '.' . $amountColumn),
        ];
    }

    private function getMentoringStats(Carbon $today): array
    {
        $defaults = [
            'total' => 0,
            'this_month' => 0,
            'this_week' => 0,
            'pending' => 0,
            'approved' => 0,
            'rescheduled' => 0,
            'completed' => 0,
            'cancelled' => 0,
            'rejected' => 0,
            'completion_rate' => 0,
            'pending_rate' => 0,
            'topic_counts' => [],
            'oldest_pending_days' => null,
        ];

        $table = $this->firstExistingTable(['student_mentoring_sessions']);
        if (! $table) {
            return $defaults;
        }

        $statusCounts = $this->getMentoringStatusCounts();
        $dateExpression = $this->buildMentoringDateExpression();
        $monthStart = $today->copy()->startOfMonth()->toDateString();
        $monthEnd = $today->copy()->endOfMonth()->toDateString();
        $weekStart = $today->copy()->startOfWeek()->toDateTimeString();
        $weekEnd = $today->copy()->endOfWeek()->toDateTimeString();

        $total = (int) DB::table($table)->count();
        $thisMonth = 0;
        $thisWeek = 0;

        if ($dateExpression) {
            $thisMonth = (int) DB::table($table)
                ->whereRaw('DATE(' . $dateExpression . ') >= ?', [$monthStart])
                ->whereRaw('DATE(' . $dateExpression . ') <= ?', [$monthEnd])
                ->count();

            $thisWeek = (int) DB::table($table)
                ->whereRaw($dateExpression . ' >= ?', [$weekStart])
                ->whereRaw($dateExpression . ' <= ?', [$weekEnd])
                ->count();
        }

        $topicCounts = [];
        if ($this->columnExists($table, 'topic_type')) {
            $topicCounts = DB::table($table)
                ->selectRaw('topic_type, COUNT(*) as total')
                ->groupBy('topic_type')
                ->orderByDesc('total')
                ->pluck('total', 'topic_type')
                ->map(fn ($value) => (int) $value)
                ->all();
        }

        $oldestPendingDays = null;
        if ($dateExpression && $this->columnExists($table, 'status')) {
            $oldestPending = DB::table($table)
                ->where('status', 'pending')
                ->selectRaw($dateExpression . ' as requested_date')
                ->orderByRaw($dateExpression . ' asc')
                ->first();

            if ($oldestPending?->requested_date) {
                $oldestPendingDays = Carbon::parse($oldestPending->requested_date)->startOfDay()->diffInDays($today->copy()->startOfDay());
            }
        }

        $completed = (int) ($statusCounts['completed'] ?? 0);
        $pending = (int) ($statusCounts['pending'] ?? 0);

        return array_merge($defaults, $statusCounts, [
            'total' => $total,
            'this_month' => $thisMonth,
            'this_week' => $thisWeek,
            'completion_rate' => $total > 0 ? (int) round(($completed / $total) * 100) : 0,
            'pending_rate' => $total > 0 ? (int) round(($pending / $total) * 100) : 0,
            'topic_counts' => $topicCounts,
            'oldest_pending_days' => $oldestPendingDays,
        ]);
    }

    private function getMentoringStatusCounts(): array
    {
        $defaults = [
            'pending' => 0,
            'approved' => 0,
            'rescheduled' => 0,
            'completed' => 0,
            'cancelled' => 0,
            'rejected' => 0,
        ];

        $table = $this->firstExistingTable(['student_mentoring_sessions']);
        if (! $table || ! $this->columnExists($table, 'status')) {
            return $defaults;
        }

        $counts = DB::table($table)
            ->selectRaw('status, COUNT(*) as total')
            ->groupBy('status')
            ->pluck('total', 'status')
            ->map(fn ($value) => (int) $value)
            ->all();

        return array_merge($defaults, $counts);
    }

    private function getUpcomingMentoringSessions(): array
    {
        $table = $this->firstExistingTable(['student_mentoring_sessions']);
        if (! $table) {
            return [];
        }

        $dateExpression = $this->buildMentoringDateExpression('sms');
        $query = DB::table($table . ' as sms')
            ->select([
                'sms.id',
                DB::raw($this->columnExists($table, 'topic_type') ? 'sms.topic_type as topic_type' : "'other' as topic_type"),
                DB::raw($this->columnExists($table, 'status') ? 'sms.status as status' : "'pending' as status"),
                DB::raw($this->columnExists($table, 'meeting_url') ? 'sms.meeting_url as meeting_url' : 'NULL as meeting_url'),
                DB::raw($dateExpression ? $dateExpression . ' as requested_date' : 'NULL as requested_date'),
            ]);

        if ($this->columnExists($table, 'status')) {
            $query->whereIn('sms.status', ['pending', 'approved', 'rescheduled']);
        }

        if ($this->tableExists('students') && $this->columnExists($table, 'student_id')) {
            $query->leftJoin('students as st', 'sms.student_id', '=', 'st.id');
            $studentNameColumn = $this->firstExistingColumn('students', ['full_name', 'name']);
            if ($studentNameColumn) {
                $query->addSelect(DB::raw('COALESCE(st.' . $studentNameColumn . ', CONCAT(\'Student #\', sms.student_id)) as student_name'));
            }
        } else {
            $query->addSelect(DB::raw("'-' as student_name"));
        }

        if ($this->tableExists('instructors') && $this->columnExists($table, 'instructor_id')) {
            $query->leftJoin('instructors as i', 'sms.instructor_id', '=', 'i.id');
            $instructorNameColumn = $this->firstExistingColumn('instructors', ['name', 'full_name']);
            if ($instructorNameColumn) {
                $query->addSelect(DB::raw('COALESCE(i.' . $instructorNameColumn . ', \'-\') as instructor_name'));
            }
        } else {
            $query->addSelect(DB::raw("'-' as instructor_name"));
        }

        if ($dateExpression) {
            $query->orderByRaw($dateExpression . ' desc');
        } else {
            $query->orderByDesc('sms.id');
        }

        return $query
            ->limit(8)
            ->get()
            ->map(fn ($row) => [
                'id' => (int) $row->id,
                'student' => $row->student_name ?? '-',
                'instructor' => $row->instructor_name ?? '-',
                'topic_type' => Str::headline((string) $row->topic_type),
                'status' => Str::headline((string) $row->status),
                'requested_date' => $row->requested_date,
                'meeting_url' => $row->meeting_url,
            ])
            ->values()
            ->all();
    }

    private function getAssignmentSubmissionStats(): array
    {
        $defaults = [
            'total' => 0,
            'this_month' => 0,
            'draft' => 0,
            'submitted' => 0,
            'late' => 0,
            'reviewed' => 0,
            'returned' => 0,
            'pending_review' => 0,
            'reviewed_this_month' => 0,
            'avg_score' => null,
            'review_rate' => 0,
        ];

        $table = $this->firstExistingTable(['assignment_submissions']);
        if (! $table) {
            return $defaults;
        }

        $statusCounts = $this->getAssignmentSubmissionStatusCounts();
        $dateExpression = $this->buildAssignmentSubmissionDateExpression();
        $monthStart = now()->startOfMonth()->toDateString();
        $monthEnd = now()->endOfMonth()->toDateString();
        $total = (int) DB::table($table)->count();

        $thisMonth = 0;
        if ($dateExpression) {
            $thisMonth = (int) DB::table($table)
                ->whereRaw('DATE(' . $dateExpression . ') >= ?', [$monthStart])
                ->whereRaw('DATE(' . $dateExpression . ') <= ?', [$monthEnd])
                ->count();
        }

        $pendingReviewQuery = DB::table($table);
        if ($this->columnExists($table, 'status')) {
            $pendingReviewQuery->whereIn('status', ['submitted', 'late']);
        }
        if ($this->columnExists($table, 'reviewed_at')) {
            $pendingReviewQuery->whereNull('reviewed_at');
        }
        $pendingReview = (int) $pendingReviewQuery->count();

        $reviewedThisMonth = 0;
        if ($this->columnExists($table, 'reviewed_at')) {
            $reviewedThisMonth = (int) DB::table($table)
                ->whereDate('reviewed_at', '>=', $monthStart)
                ->whereDate('reviewed_at', '<=', $monthEnd)
                ->count();
        } elseif ($this->columnExists($table, 'status')) {
            $reviewedThisMonth = (int) ($statusCounts['reviewed'] ?? 0);
        }

        $avgScore = null;
        if ($this->columnExists($table, 'score')) {
            $avgScoreValue = DB::table($table)
                ->whereNotNull('score')
                ->avg('score');
            $avgScore = $avgScoreValue !== null ? round((float) $avgScoreValue, 1) : null;
        }

        $reviewed = (int) ($statusCounts['reviewed'] ?? 0);

        return array_merge($defaults, $statusCounts, [
            'total' => $total,
            'this_month' => $thisMonth,
            'pending_review' => $pendingReview,
            'reviewed_this_month' => $reviewedThisMonth,
            'avg_score' => $avgScore,
            'review_rate' => $total > 0 ? (int) round(($reviewed / $total) * 100) : 0,
        ]);
    }

    private function getAssignmentSubmissionStatusCounts(): array
    {
        $defaults = [
            'draft' => 0,
            'submitted' => 0,
            'late' => 0,
            'reviewed' => 0,
            'returned' => 0,
        ];

        $table = $this->firstExistingTable(['assignment_submissions']);
        if (! $table || ! $this->columnExists($table, 'status')) {
            return $defaults;
        }

        $counts = DB::table($table)
            ->selectRaw('status, COUNT(*) as total')
            ->groupBy('status')
            ->pluck('total', 'status')
            ->map(fn ($value) => (int) $value)
            ->all();

        return array_merge($defaults, $counts);
    }

    private function getRecentAssignmentSubmissions(): array
    {
        $table = $this->firstExistingTable(['assignment_submissions']);
        if (! $table) {
            return [];
        }

        $dateExpression = $this->buildAssignmentSubmissionDateExpression('sub');
        $query = DB::table($table . ' as sub')
            ->select([
                'sub.id',
                DB::raw($this->columnExists($table, 'status') ? 'sub.status as status' : "'submitted' as status"),
                DB::raw($this->columnExists($table, 'score') ? 'sub.score as score' : 'NULL as score'),
                DB::raw($dateExpression ? $dateExpression . ' as submitted_date' : 'NULL as submitted_date'),
            ]);

        if ($this->tableExists('students') && $this->columnExists($table, 'student_id')) {
            $query->leftJoin('students as st', 'sub.student_id', '=', 'st.id');
            $studentNameColumn = $this->firstExistingColumn('students', ['full_name', 'name']);
            if ($studentNameColumn) {
                $query->addSelect(DB::raw('COALESCE(st.' . $studentNameColumn . ', CONCAT(\'Student #\', sub.student_id)) as student_name'));
            }
        } else {
            $query->addSelect(DB::raw("'-' as student_name"));
        }

        if ($this->tableExists('assignments') && $this->columnExists($table, 'assignment_id')) {
            $query->leftJoin('assignments as a', 'sub.assignment_id', '=', 'a.id');
            $assignmentTitleColumn = $this->firstExistingColumn('assignments', ['title', 'name']);
            if ($assignmentTitleColumn) {
                $query->addSelect(DB::raw('COALESCE(a.' . $assignmentTitleColumn . ', CONCAT(\'Assignment #\', sub.assignment_id)) as assignment_title'));
            }
        } else {
            $query->addSelect(DB::raw("'-' as assignment_title"));
        }

        if ($this->columnExists($table, 'status')) {
            $query->whereIn('sub.status', ['submitted', 'late', 'reviewed', 'returned']);
        }

        if ($dateExpression) {
            $query->orderByRaw($dateExpression . ' desc');
        } else {
            $query->orderByDesc('sub.id');
        }

        return $query
            ->limit(8)
            ->get()
            ->map(fn ($row) => [
                'id' => (int) $row->id,
                'student' => $row->student_name ?? '-',
                'assignment' => $row->assignment_title ?? '-',
                'status' => Str::headline((string) $row->status),
                'score' => $row->score,
                'submitted_date' => $row->submitted_date,
            ])
            ->values()
            ->all();
    }

    private function getAcademicSummary(array $context): array
    {
        $items = [];

        $stats = $context['stats'] ?? [];
        $capacityRows = $context['capacity_rows'] ?? [];
        $trackingRows = $context['tracking_rows'] ?? [];
        $learningProgress = $context['learning_progress'] ?? [];
        $workloadStats = $context['workload_stats'] ?? [];
        $trialStats = $context['trial_stats'] ?? [];
        $trialProgress = (int) ($context['trial_follow_up_progress'] ?? 0);
        $workshopStats = $context['workshop_stats'] ?? [];
        $workshopProgress = (int) ($context['workshop_follow_up_progress'] ?? 0);
        $mentoringStats = $context['mentoring_stats'] ?? [];
        $assignmentStats = $context['assignment_submission_stats'] ?? [];
        $trelloAcademicStats = $context['trello_academic_stats']
            ?? $context['trelloAcademicStats']
            ?? [];
        $trelloSummary = $trelloAcademicStats['summary'] ?? [];

        $seatUtilization = (int) ($stats[1]['raw_value'] ?? 0);
        $pendingTracking = (int) ($stats[3]['raw_value'] ?? 0);
        $pendingAssessments = (int) ($assignmentStats['pending_review'] ?? $workloadStats['pending_assessments'] ?? 0);
        $pendingMentoring = (int) ($mentoringStats['pending'] ?? 0);
        $approvedMentoring = (int) ($mentoringStats['approved'] ?? 0);
        $oldestPendingMentoringDays = $mentoringStats['oldest_pending_days'] ?? null;

        $trelloOverdue = max((int) ($trelloSummary['overdue'] ?? 0), 0);
        $trelloDueToday = max((int) ($trelloSummary['due_today'] ?? 0), 0);
        $trelloActiveWork = max((int) ($trelloSummary['active_work'] ?? 0), 0);
        $trelloUnmapped = max((int) ($trelloSummary['unmapped'] ?? 0), 0);
        $trelloCompletionRate = min(max((int) ($trelloSummary['completion_rate'] ?? 0), 0), 100);
        $trelloWebhookStatus = Str::lower((string) ($trelloAcademicStats['webhook_status'] ?? 'inactive'));
        $trelloBoardName = trim((string) ($trelloAcademicStats['board_name'] ?? ''));

        $lowProgressBatch = collect($learningProgress)
            ->sortBy('progress')
            ->first();
        if ($trelloDueToday > 0) {
            $alerts[] = [
                'type' => 'warning',
                'icon' => 'bi-calendar-event-fill',
                'title' => number_format($trelloDueToday) . ' Trello Academic task due today',
                'description' => 'Pastikan card yang jatuh tempo hari ini sudah memiliki owner dan progress yang jelas.',
            ];
        }

        if ($trelloUnmapped > 0) {
            $alerts[] = [
                'type' => 'info',
                'icon' => 'bi-diagram-3-fill',
                'title' => number_format($trelloUnmapped) . ' Trello card belum terpetakan',
                'description' => 'Mapping normalized status perlu dirapikan agar workload dan completion rate terbaca akurat.',
            ];
        }

        if (
            ! in_array($trelloWebhookStatus, ['active', 'synced'], true)
            && $trelloBoardName !== ''
        ) {
            $alerts[] = [
                'type' => 'info',
                'icon' => 'bi-arrow-repeat',
                'title' => 'Sync Trello Academic perlu dicek',
                'description' => 'Board sudah terhubung, tetapi status webhook atau sinkronisasi belum aktif.',
            ];
        }

        $lowCapacityBatch = collect($capacityRows)
            ->filter(fn ($row) => (int) ($row['capacity'] ?? 0) > 0)
            ->map(function ($row) {
                $row['utilization'] = (int) ($row['capacity'] ?? 0) > 0
                    ? round(((int) ($row['filled'] ?? 0) / (int) $row['capacity']) * 100)
                    : 0;

                return $row;
            })
            ->sortBy('utilization')
            ->first();

        if ($pendingTracking > 0) {
            $items[] = $this->academicSummaryItem(
                'warning',
                'Instructor tracking perlu difollow-up',
                number_format($pendingTracking) . ' tracking instructor masih belum selesai. Academic team perlu memastikan coverage session tercatat supaya monitoring kelas tetap akurat.',
                950
            );
        }

        if ($trelloOverdue > 0) {
            $items[] = $this->academicSummaryItem(
                'warning',
                'Trello Academic memiliki task overdue',
                number_format($trelloOverdue) . ' card Academic sudah melewati due date. Prioritaskan pengecekan owner, blocker, dan target penyelesaiannya agar pekerjaan akademik tidak tertunda.',
                940
            );
        }

        if ($pendingAssessments > 0) {
            $items[] = $this->academicSummaryItem(
                'warning',
                'Submission assignment menunggu review',
                number_format($pendingAssessments) . ' submission assignment masih menunggu review. Prioritasnya adalah membagi workload koreksi agar feedback ke student tidak tertunda.',
                910
            );
        }

        if ($pendingMentoring > 0) {
            $message = number_format($pendingMentoring) . ' request mentoring masih pending. Academic team perlu cek approval instructor dan jadwal agar student mendapat bantuan tepat waktu.';

            if ($oldestPendingMentoringDays !== null && $oldestPendingMentoringDays >= 3) {
                $message .= ' Request terlama sudah sekitar ' . number_format((int) $oldestPendingMentoringDays) . ' hari.';
            }

            $items[] = $this->academicSummaryItem(
                'warning',
                'Request mentoring perlu diproses',
                $message,
                900
            );
        } elseif ($approvedMentoring > 0) {
            $items[] = $this->academicSummaryItem(
                'good',
                'Mentoring sudah terjadwal',
                number_format($approvedMentoring) . ' mentoring sudah approved. Pastikan meeting link, instructor, dan kebutuhan student sudah jelas sebelum sesi berjalan.',
                520
            );
        }

        if ($trelloDueToday > 0) {
            $items[] = $this->academicSummaryItem(
                'warning',
                'Ada task Trello Academic jatuh tempo hari ini',
                number_format($trelloDueToday) . ' card Academic memiliki due date hari ini. Pastikan task penting sudah memiliki owner dan status pengerjaan yang jelas.',
                870
            );
        }

        if ($trelloActiveWork > 0 && $trelloCompletionRate < 50) {
            $items[] = $this->academicSummaryItem(
                'info',
                'Work progress Trello Academic perlu dijaga',
                number_format($trelloActiveWork) . ' card sedang aktif dengan completion rate sekitar ' . number_format($trelloCompletionRate) . '%. Academic team perlu menjaga perpindahan task dari active work menuju done.',
                610
            );
        }

        if ($trelloUnmapped > 0) {
            $items[] = $this->academicSummaryItem(
                'info',
                'Status Trello Academic belum seluruhnya terpetakan',
                number_format($trelloUnmapped) . ' card belum memiliki normalized status. Cek mapping list Trello supaya perhitungan workload dan completion rate tetap akurat.',
                570
            );
        }

        if (
            ! in_array($trelloWebhookStatus, ['active', 'synced'], true)
            && ($trelloBoardName !== '' || $trelloActiveWork > 0 || $trelloOverdue > 0 || $trelloDueToday > 0)
        ) {
            $items[] = $this->academicSummaryItem(
                'info',
                'Sinkronisasi Trello Academic perlu dicek',
                'Board Trello Academic ditemukan, tetapi status webhook atau sync belum aktif. Pastikan scheduler dan webhook tetap berjalan agar data dashboard tidak tertinggal.',
                550
            );
        }

        if ($lowProgressBatch && (int) ($lowProgressBatch['students'] ?? 0) > 0 && (int) ($lowProgressBatch['progress'] ?? 0) < 40) {
            $items[] = $this->academicSummaryItem(
                'warning',
                'Progress belajar batch perlu perhatian',
                ($lowProgressBatch['batch'] ?? 'Batch') . ' memiliki rata-rata progress sekitar ' . number_format((int) $lowProgressBatch['progress']) . '%. Academic team bisa cek blocker materi, attendance, atau kebutuhan mentoring.',
                840
            );
        }

        if ($seatUtilization < 50 && $seatUtilization > 0) {
            $items[] = $this->academicSummaryItem(
                'info',
                'Utilisasi seat masih rendah',
                'Seat utilization berada di ' . number_format($seatUtilization) . '%. Dari sisi akademik, batch tetap perlu disiapkan sambil koordinasi dengan Sales untuk intake berikutnya.',
                560
            );
        } elseif ($seatUtilization >= 80) {
            $items[] = $this->academicSummaryItem(
                'good',
                'Utilisasi batch cukup sehat',
                'Seat utilization sudah mencapai ' . number_format($seatUtilization) . '%. Fokus akademik berikutnya adalah menjaga kualitas delivery dan monitoring progress student.',
                480
            );
        } elseif ($lowCapacityBatch && (int) ($lowCapacityBatch['utilization'] ?? 0) < 65) {
            $items[] = $this->academicSummaryItem(
                'info',
                'Ada batch dengan utilisasi rendah',
                ($lowCapacityBatch['batch'] ?? 'Batch') . ' masih memiliki utilisasi sekitar ' . number_format((int) $lowCapacityBatch['utilization']) . '%. Data ini bisa jadi bahan koordinasi akademik dan sales.',
                430
            );
        }

        if ((int) ($trialStats['participants_this_month'] ?? 0) > 0 && $trialProgress < 50) {
            $items[] = $this->academicSummaryItem(
                'info',
                'Follow-up trial perlu dikawal',
                'Trial bulan ini punya ' . number_format((int) ($trialStats['participants_this_month'] ?? 0)) . ' peserta, tapi follow-up progress baru ' . number_format($trialProgress) . '%. Academic bisa bantu memastikan jadwal dan materi trial siap.',
                420
            );
        }

        if ((int) ($workshopStats['participants_this_month'] ?? 0) > 0 && $workshopProgress < 50) {
            $items[] = $this->academicSummaryItem(
                'info',
                'Workshop perlu dikawal sampai delivery',
                'Workshop bulan ini punya ' . number_format((int) ($workshopStats['participants_this_month'] ?? 0)) . ' peserta, namun conversion progress baru ' . number_format($workshopProgress) . '%. Pastikan jadwal, instructor, dan materi workshop siap.',
                410
            );
        }

        if (empty($items)) {
            $items[] = $this->academicSummaryItem(
                'good',
                'Academic operation terlihat stabil',
                'Belum ada alert akademik yang menonjol. Tetap pantau instructor tracking, progress student, submission assignment, mentoring request, dan kesiapan sesi berjalan.',
                300
            );
        }

        usort($items, fn ($a, $b) => ($b['score'] ?? 0) <=> ($a['score'] ?? 0));

        $headline = $items[0]['title'] ?? 'Academic Insight';
        $summaryText = $this->buildAcademicSummaryText($items);

        return [
            'generated_at' => now()->format('d M Y H:i'),
            'source' => 'local',
            'source_label' => 'Smart Academic Insight',
            'mode' => 'local_smart',
            'headline' => $headline,
            'summary_text' => $summaryText,
            'items' => array_slice($items, 0, 6),
            'focus' => array_slice($this->buildAcademicFocus($items), 0, 4),
        ];
    }

    private function academicSummaryItem(string $type, string $title, string $message, int $score = 0): array
    {
        return [
            'type' => $type,
            'level' => $type,
            'title' => $title,
            'message' => $message,
            'description' => $message,
            'score' => $score,
        ];
    }

    private function buildAcademicSummaryText(array $items): string
    {
        $primary = $items[0] ?? null;
        $secondary = $items[1] ?? null;
        $tertiary = $items[2] ?? null;

        if (! $primary) {
            return 'Academic operation siap dipantau. Fokus utama tetap pada progress student, instructor tracking, assignment review, dan mentoring request.';
        }

        $sentences = [$primary['message']];

        if ($secondary) {
            $sentences[] = 'Selain itu, ' . Str::lower($secondary['title']) . ' juga perlu diperhatikan. ' . $secondary['message'];
        }

        if ($tertiary && ($tertiary['type'] ?? '') !== ($secondary['type'] ?? null)) {
            $sentences[] = 'Fokus tambahan: ' . $tertiary['message'];
        }

        return $this->limitWords(implode(' ', array_filter($sentences)), 90);
    }

    private function buildAcademicFocus(array $items): array
    {
        $focus = [];

        foreach ($items as $item) {
            $title = Str::lower((string) ($item['title'] ?? ''));
            $type = (string) ($item['type'] ?? 'info');

            if (Str::contains($title, ['trello', 'overdue', 'jatuh tempo', 'mapping'])) {
                $focus[] = $this->academicSummaryItem(
                    $type,
                    'Rapikan prioritas Trello Academic',
                    'Tuntaskan card overdue dan due today, pastikan setiap task memiliki owner, due date, dan normalized status.'
                );
            } elseif (Str::contains($title, ['tracking', 'instructor'])) {
                $focus[] = $this->academicSummaryItem($type, 'Follow-up instructor tracking', 'Pastikan coverage session dan catatan kelas sudah tersubmit dengan benar.');
            } elseif (Str::contains($title, ['assignment', 'submission'])) {
                $focus[] = $this->academicSummaryItem($type, 'Selesaikan review assignment', 'Prioritaskan submission yang sudah submitted atau late agar feedback ke student tidak tertunda.');
            } elseif (Str::contains($title, ['mentoring'])) {
                $focus[] = $this->academicSummaryItem($type, 'Proses request mentoring', 'Cek pending mentoring, jadwal instructor, dan kebutuhan student sebelum sesi berjalan.');
            } elseif (Str::contains($title, ['progress'])) {
                $focus[] = $this->academicSummaryItem($type, 'Pantau progress belajar', 'Cek batch dengan progress rendah dan identifikasi blocker student.');
            } elseif (Str::contains($title, ['seat', 'utilisasi'])) {
                $focus[] = $this->academicSummaryItem($type, 'Koordinasi kapasitas batch', 'Sinkronkan data seat dengan readiness akademik dan kebutuhan intake.');
            } elseif (Str::contains($title, ['trial'])) {
                $focus[] = $this->academicSummaryItem($type, 'Siapkan delivery trial', 'Pastikan jadwal, materi, dan follow-up peserta trial siap.');
            } elseif (Str::contains($title, ['workshop'])) {
                $focus[] = $this->academicSummaryItem($type, 'Siapkan delivery workshop', 'Pastikan instructor, materi, dan jadwal workshop sudah siap.');
            }
        }

        if (empty($focus)) {
            $focus = [
                $this->academicSummaryItem('info', 'Pantau academic operation', 'Review tracking, assignment, mentoring, dan progress student secara rutin.'),
            ];
        }

        return collect($focus)
            ->unique('title')
            ->values()
            ->all();
    }


    private function getAlerts(
        array $stats,
        array $capacityRows,
        array $trackingRows,
        array $workloadStats,
        array $mentoringStats = [],
        array $assignmentSubmissionStats = [],
        array $trialStats = [],
        array $workshopStats = [],
        array $trelloAcademicStats = []
    ): array {
        $alerts = [];

        $pendingTracking = (int) ($stats[3]['raw_value'] ?? 0);
        $pendingAssessments = (int) ($assignmentSubmissionStats['pending_review'] ?? $workloadStats['pending_assessments'] ?? 0);
        $pendingMentoring = (int) ($mentoringStats['pending'] ?? 0);
        $oldestPendingMentoringDays = $mentoringStats['oldest_pending_days'] ?? null;

        $trelloSummary = $trelloAcademicStats['summary'] ?? [];
        $trelloOverdue = max((int) ($trelloSummary['overdue'] ?? 0), 0);
        $trelloDueToday = max((int) ($trelloSummary['due_today'] ?? 0), 0);
        $trelloUnmapped = max((int) ($trelloSummary['unmapped'] ?? 0), 0);
        $trelloWebhookStatus = Str::lower((string) ($trelloAcademicStats['webhook_status'] ?? 'inactive'));
        $trelloBoardName = trim((string) ($trelloAcademicStats['board_name'] ?? ''));

        if ($pendingTracking > 0) {
            $alerts[] = [
                'type' => 'warning',
                'icon' => 'bi-exclamation-triangle-fill',
                'title' => "{$pendingTracking} instructor tracking belum selesai",
                'description' => 'Academic team perlu follow-up instructor agar coverage session tetap tercatat.',
            ];
        }

        if ($trelloOverdue > 0) {
            $alerts[] = [
                'type' => 'danger',
                'icon' => 'bi-kanban-fill',
                'title' => number_format($trelloOverdue) . ' Trello Academic task overdue',
                'description' => 'Ada card yang sudah melewati due date. Cek owner, blocker, dan target penyelesaiannya.',
            ];
        }

        if ($pendingAssessments > 0) {
            $alerts[] = [
                'type' => 'warning',
                'icon' => 'bi-journal-check',
                'title' => number_format($pendingAssessments) . ' assignment submission perlu review',
                'description' => 'Ada submission berstatus submitted/late yang belum direview. Feedback student perlu diprioritaskan.',
            ];
        }

        if ($pendingMentoring > 0) {
            $description = 'Ada request mentoring yang masih pending. Academic team perlu cek jadwal, instructor, dan kebutuhan student.';

            if ($oldestPendingMentoringDays !== null && (int) $oldestPendingMentoringDays >= 3) {
                $description .= ' Request terlama sudah sekitar ' . number_format((int) $oldestPendingMentoringDays) . ' hari.';
            }

            $alerts[] = [
                'type' => 'warning',
                'icon' => 'bi-person-video3',
                'title' => number_format($pendingMentoring) . ' mentoring request pending',
                'description' => $description,
            ];
        }

        $lowCapacityBatch = collect($capacityRows)
            ->filter(function ($row) {
                if ((int) $row['capacity'] <= 0) {
                    return false;
                }

                $utilization = ((int) $row['filled'] / (int) $row['capacity']) * 100;

                return $utilization < 65;
            })
            ->first();

        if ($lowCapacityBatch) {
            $alerts[] = [
                'type' => 'info',
                'icon' => 'bi-info-circle-fill',
                'title' => $lowCapacityBatch['batch'] . ' masih rendah utilisasinya',
                'description' => 'Seat utilization masih di bawah 65%. Bisa dikoordinasikan dengan Sales untuk intake berikutnya.',
            ];
        }

        if (($workloadStats['report_cards'] ?? 0) > 0) {
            $alerts[] = [
                'type' => 'danger',
                'icon' => 'bi-x-circle-fill',
                'title' => $workloadStats['report_cards'] . ' report card perlu follow-up',
                'description' => 'Ada report card yang belum publish/final. Academic team perlu cek sebelum sertifikat diproses.',
            ];
        }

        if ((int) ($trialStats['participants_this_month'] ?? 0) > 0 && (int) ($trialStats['schedules_active_this_month'] ?? 0) <= 0) {
            $alerts[] = [
                'type' => 'info',
                'icon' => 'bi-broadcast-pin',
                'title' => 'Peserta trial masuk, jadwal aktif perlu dicek',
                'description' => 'Ada peserta trial bulan ini, tapi jadwal aktif belum terlihat dari data. Pastikan jadwal dan delivery trial siap.',
            ];
        }

        if ((int) ($workshopStats['participants_this_month'] ?? 0) > 0 && (int) ($workshopStats['schedules_active_this_month'] ?? 0) <= 0) {
            $alerts[] = [
                'type' => 'info',
                'icon' => 'bi-tools',
                'title' => 'Peserta workshop masuk, jadwal aktif perlu dicek',
                'description' => 'Ada peserta workshop bulan ini, tapi jadwal aktif belum terlihat dari data. Pastikan instructor dan materi workshop siap.',
            ];
        }

        if (empty($alerts)) {
            $alerts[] = [
                'type' => 'success',
                'icon' => 'bi-check-circle-fill',
                'title' => 'Academic operation terlihat aman',
                'description' => 'Belum ada alert prioritas tinggi dari data dashboard saat ini.',
            ];
        }

        $severityOrder = [
            'danger' => 4,
            'warning' => 3,
            'info' => 2,
            'success' => 1,
        ];

        usort(
            $alerts,
            fn (array $left, array $right) => ($severityOrder[$right['type'] ?? 'success'] ?? 0)
                <=> ($severityOrder[$left['type'] ?? 'success'] ?? 0)
        );

        return array_slice($alerts, 0, 6);
    }

    private function getSuggestedFocus(array $alerts): string
    {
        $firstAlert = $alerts[0] ?? null;

        if (! $firstAlert) {
            return 'Pantau jadwal instructor hari ini dan pastikan semua sesi punya scheduled material.';
        }

        $title = Str::lower((string) ($firstAlert['title'] ?? ''));

        if (Str::contains($title, ['trello', 'overdue', 'due today', 'jatuh tempo'])) {
            return 'Prioritaskan card Trello Academic yang overdue atau jatuh tempo hari ini, lalu pastikan owner dan blocker-nya jelas.';
        }

        if (Str::contains($title, ['assignment', 'submission'])) {
            return 'Prioritaskan review assignment yang masih menunggu agar feedback student tidak tertunda.';
        }

        if (Str::contains($title, ['mentoring'])) {
            return 'Prioritaskan request mentoring yang pending dan pastikan jadwal serta instructor tersedia.';
        }

        if (Str::contains($title, ['tracking', 'instructor'])) {
            return 'Prioritaskan follow-up instructor tracking yang belum selesai agar data coverage tetap akurat.';
        }

        return match ($firstAlert['type'] ?? 'info') {
            'danger' => 'Prioritaskan item akademik yang blocking dan membutuhkan tindakan segera.',
            'warning' => 'Prioritaskan pekerjaan akademik yang belum selesai atau mendekati deadline.',
            'info' => 'Review informasi operasional yang perlu ditindaklanjuti oleh Academic Ops.',
            default => 'Pantau jadwal instructor hari ini dan pastikan semua sesi punya scheduled material.',
        };
    }

    private function buildMentoringDateExpression(?string $prefix = null): ?string
    {
        $table = $this->firstExistingTable(['student_mentoring_sessions']);
        if (! $table) {
            return null;
        }

        $columns = [];
        foreach (['approved_at', 'requested_at', 'created_at'] as $column) {
            if ($this->columnExists($table, $column)) {
                $columns[] = $this->wrapColumn(($prefix ?: $table) . '.' . $column);
            }
        }

        if (empty($columns)) {
            return null;
        }

        return count($columns) === 1
            ? $columns[0]
            : 'COALESCE(' . implode(', ', $columns) . ')';
    }

    private function buildAssignmentSubmissionDateExpression(?string $prefix = null): ?string
    {
        $table = $this->firstExistingTable(['assignment_submissions']);
        if (! $table) {
            return null;
        }

        $columns = [];
        foreach (['submitted_at', 'created_at'] as $column) {
            if ($this->columnExists($table, $column)) {
                $columns[] = $this->wrapColumn(($prefix ?: $table) . '.' . $column);
            }
        }

        if (empty($columns)) {
            return null;
        }

        return count($columns) === 1
            ? $columns[0]
            : 'COALESCE(' . implode(', ', $columns) . ')';
    }

    private function buildPaymentDateExpression(string $paymentsTable): ?string
    {
        if (! $this->tableExists($paymentsTable)) {
            return null;
        }

        $columns = [];
        foreach (['paid_at', 'payment_date', 'created_at'] as $column) {
            if ($this->columnExists($paymentsTable, $column)) {
                $columns[] = $this->wrapColumn($paymentsTable . '.' . $column);
            }
        }

        if (empty($columns)) {
            return null;
        }

        return count($columns) === 1
            ? $columns[0]
            : 'COALESCE(' . implode(', ', $columns) . ')';
    }

    private function getPaidPaymentStatuses(): array
    {
        return [
            'paid',
            'success',
            'settled',
            'completed',
            'confirmed',
            'verified',
        ];
    }

    private function wrapColumn(string $column): string
    {
        return DB::connection()->getQueryGrammar()->wrap($column);
    }

    private function limitWords(string $text, int $limit): string
    {
        $words = preg_split('/\s+/', trim($text));

        if (! $words || count($words) <= $limit) {
            return trim($text);
        }

        return implode(' ', array_slice($words, 0, $limit)) . '...';
    }


    private function getIdsByColumn(string $table, string $column, mixed $value): array
    {
        if (! $this->tableExists($table) || ! $this->columnExists($table, $column)) {
            return [];
        }

        $values = is_array($value) ? $value : [$value];

        if (empty($values)) {
            return [];
        }

        return DB::table($table)
            ->whereIn($column, $values)
            ->pluck('id')
            ->map(fn ($id) => (int) $id)
            ->all();
    }

    private function tableExists(string $table): bool
    {
        return Schema::hasTable($table);
    }

    private function columnExists(string $table, string $column): bool
    {
        return $this->tableExists($table) && Schema::hasColumn($table, $column);
    }

    private function firstExistingColumn(string $table, array $columns): ?string
    {
        foreach ($columns as $column) {
            if ($this->columnExists($table, $column)) {
                return $column;
            }
        }

        return null;
    }

    private function firstExistingTable(array $tables): ?string
    {
        foreach ($tables as $table) {
            if ($this->tableExists($table)) {
                return $table;
            }
        }

        return null;
    }
}