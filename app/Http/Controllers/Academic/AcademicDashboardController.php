<?php

namespace App\Http\Controllers\Academic;

use App\Http\Controllers\Controller;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

class AcademicDashboardController extends Controller
{
    public function index(): View
    {
        $today = Carbon::today();

        $capacityRows = $this->getBatchCapacityRows();
        $todaySessions = $this->getTodayInstructorSessions($today);
        $trackingRows = $this->getInstructorTrackingRows();
        $curriculumReadiness = $this->getCurriculumReadinessRows();
        $learningProgress = $this->getLearningProgressRows($capacityRows);
        $workloadStats = $this->getAcademicWorkloadStats($today);

        $stats = $this->getStats($capacityRows, $todaySessions, $trackingRows);

        $alerts = $this->getAlerts(
            stats: $stats,
            capacityRows: $capacityRows,
            trackingRows: $trackingRows,
            workloadStats: $workloadStats
        );

        $suggestedFocus = $this->getSuggestedFocus($alerts);

        return view('academic.dashboard.index', compact(
            'stats',
            'capacityRows',
            'todaySessions',
            'trackingRows',
            'curriculumReadiness',
            'learningProgress',
            'workloadStats',
            'alerts',
            'suggestedFocus'
        ));
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
        $progressTable = $this->firstExistingTable([
            'student_lesson_progress',
            'student_lesson_progresses',
            'student_learning_progress',
            'learning_progress',
        ]);

        if (! $progressTable || ! $this->tableExists('student_enrollments')) {
            return collect($capacityRows)
                ->take(5)
                ->map(fn ($row) => [
                    'batch' => $row['batch'],
                    'program' => $row['program'],
                    'progress' => 0,
                    'students' => $row['filled'],
                    'risk' => $row['filled'] > 0 ? 'Medium' : 'High',
                ])
                ->values()
                ->all();
        }

        $progressColumn = $this->firstExistingColumn($progressTable, [
            'progress_percentage',
            'percentage_watched',
            'progress',
            'completion_percentage',
        ]);

        if (! $progressColumn) {
            return [];
        }

        $query = DB::table('student_enrollments as se')
            ->join('batches as b', 'se.batch_id', '=', 'b.id')
            ->leftJoin($progressTable . ' as lp', 'se.student_id', '=', 'lp.student_id')
            ->select([
                'b.id as batch_id',
                DB::raw('COALESCE(b.name, CONCAT("Batch #", b.id)) as batch'),
                DB::raw('COUNT(DISTINCT se.student_id) as students'),
                DB::raw("ROUND(AVG(COALESCE(lp.{$progressColumn}, 0)), 0) as progress"),
            ])
            ->groupBy('b.id', 'b.name')
            ->orderByDesc('b.id')
            ->limit(5);

        if ($this->tableExists('programs') && $this->columnExists('batches', 'program_id')) {
            $query->leftJoin('programs as p', 'b.program_id', '=', 'p.id')
                ->addSelect(DB::raw("COALESCE(p.name, '-') as program"))
                ->groupBy('p.name');
        } else {
            $query->addSelect(DB::raw("'-' as program"));
        }

        if ($this->columnExists('student_enrollments', 'status')) {
            $query->whereIn('se.status', [
                'active',
                'ongoing',
                'enrolled',
                'approved',
                'paid',
            ]);
        }

        return $query
            ->get()
            ->map(function ($row) {
                $progress = min(100, max(0, (int) $row->progress));

                return [
                    'batch' => $row->batch,
                    'program' => $row->program,
                    'progress' => $progress,
                    'students' => (int) $row->students,
                    'risk' => $progress >= 60 ? 'Low' : ($progress >= 30 ? 'Medium' : 'High'),
                ];
            })
            ->values()
            ->all();
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
                'pending',
                'waiting_review',
                'need_review',
            ]);
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

        $query = DB::table('student_mentoring_sessions');

        $dateColumn = $this->firstExistingColumn('student_mentoring_sessions', [
            'session_date',
            'schedule_date',
            'scheduled_at',
            'start_time',
        ]);

        if ($dateColumn) {
            $query->whereBetween($dateColumn, [
                $today->copy()->startOfWeek()->toDateTimeString(),
                $today->copy()->endOfWeek()->toDateTimeString(),
            ]);
        }

        return (int) $query->count();
    }

    private function getAlerts(array $stats, array $capacityRows, array $trackingRows, array $workloadStats): array
    {
        $alerts = [];

        $pendingTracking = (int) ($stats[3]['raw_value'] ?? 0);

        if ($pendingTracking > 0) {
            $alerts[] = [
                'type' => 'warning',
                'icon' => 'bi-exclamation-triangle-fill',
                'title' => "{$pendingTracking} instructor tracking belum selesai",
                'description' => 'Academic team perlu follow-up instructor agar coverage session tetap tercatat.',
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

        if (empty($alerts)) {
            $alerts[] = [
                'type' => 'success',
                'icon' => 'bi-check-circle-fill',
                'title' => 'Academic operation terlihat aman',
                'description' => 'Belum ada alert prioritas tinggi dari data dashboard saat ini.',
            ];
        }

        return $alerts;
    }

    private function getSuggestedFocus(array $alerts): string
    {
        $firstAlert = $alerts[0] ?? null;

        if (! $firstAlert) {
            return 'Pantau jadwal instructor hari ini dan pastikan semua sesi punya scheduled material.';
        }

        return match ($firstAlert['type']) {
            'danger' => 'Prioritaskan item akademik yang blocking, terutama report card dan sertifikat.',
            'warning' => 'Prioritaskan follow-up instructor tracking yang belum selesai agar data coverage tetap akurat.',
            'info' => 'Review batch dengan utilisasi rendah dan koordinasikan dengan Sales atau Academic Ops.',
            default => 'Pantau jadwal instructor hari ini dan pastikan semua sesi punya scheduled material.',
        };
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