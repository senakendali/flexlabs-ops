<?php

namespace App\Http\Controllers\Academic;

use App\Http\Controllers\Controller;
use App\Models\Batch;
use App\Models\InstructorSchedule;
use App\Models\Student;
use App\Models\StudentAttendance;
use App\Models\StudentLessonProgress;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Validation\Rule;

class StudentAttendanceController extends Controller
{
    public function index(Request $request)
    {
        $dateColumn = $this->scheduleDateColumn();
        $startTimeColumn = $this->scheduleStartTimeColumn();
        $endTimeColumn = $this->scheduleEndTimeColumn();

        $batches = Batch::query()
            ->with('program')
            ->orderByDesc('id')
            ->get();

        $schedulesQuery = InstructorSchedule::query()
            ->with($this->availableScheduleRelations())
            ->when($request->filled('batch_id'), function ($query) use ($request) {
                $query->where('batch_id', $request->batch_id);
            })
            ->when($request->filled('date') && $dateColumn, function ($query) use ($request, $dateColumn) {
                $query->whereDate($dateColumn, $request->date);
            });

        if ($dateColumn) {
            $schedulesQuery->orderByDesc($dateColumn);
        } else {
            $schedulesQuery->orderByDesc('id');
        }

        if ($startTimeColumn) {
            $schedulesQuery->orderBy($startTimeColumn);
        }

        $schedules = $schedulesQuery
            ->paginate(15)
            ->withQueryString();

        $scheduleIds = $schedules
            ->getCollection()
            ->pluck('id')
            ->filter()
            ->values();

        $attendanceSummaries = StudentAttendance::query()
            ->whereIn('instructor_schedule_id', $scheduleIds)
            ->selectRaw('instructor_schedule_id, status, attendance_mode, COUNT(*) as total')
            ->groupBy('instructor_schedule_id', 'status', 'attendance_mode')
            ->get()
            ->groupBy('instructor_schedule_id');

        return view('academic.attendances.index', compact(
            'batches',
            'schedules',
            'attendanceSummaries',
            'dateColumn',
            'startTimeColumn',
            'endTimeColumn'
        ));
    }

    public function record(InstructorSchedule $instructorSchedule)
    {
        $batchId = (int) ($instructorSchedule->batch_id ?? 0);

        abort_if(!$batchId, 404, 'Schedule ini belum terhubung ke batch.');

        $batch = Batch::query()
            ->with('program')
            ->findOrFail($batchId);

        $students = $this->getStudentsForBatch($batchId);

        $attendances = StudentAttendance::query()
            ->where('instructor_schedule_id', $instructorSchedule->id)
            ->get()
            ->keyBy('student_id');

        $dateColumn = $this->scheduleDateColumn();
        $startTimeColumn = $this->scheduleStartTimeColumn();
        $endTimeColumn = $this->scheduleEndTimeColumn();

        return view('academic.attendances.record', compact(
            'instructorSchedule',
            'batch',
            'students',
            'attendances',
            'dateColumn',
            'startTimeColumn',
            'endTimeColumn'
        ));
    }

    public function store(Request $request, InstructorSchedule $instructorSchedule)
    {
        $batchId = (int) ($instructorSchedule->batch_id ?? 0);

        abort_if(!$batchId, 404, 'Schedule ini belum terhubung ke batch.');

        $validated = $request->validate([
            'attendances' => ['required', 'array'],
            'attendances.*.student_id' => [
                'required',
                'integer',
                'exists:students,id',
            ],
            'attendances.*.status' => [
                'required',
                Rule::in(array_keys(StudentAttendance::STATUSES)),
            ],
            'attendances.*.attendance_mode' => [
                'nullable',
                Rule::in(array_keys(StudentAttendance::ATTENDANCE_MODES)),
            ],
            'attendances.*.notes' => [
                'nullable',
                'string',
            ],
        ]);

        $allowedStudentIds = $this->getStudentIdsForBatch($batchId);

        /*
        * Load semua lesson/subtopic yang terkait dengan live session sekali.
        *
        * Relation akan tetap tersedia selama proses attendance sehingga
        * tidak perlu query pivot berulang untuk setiap student.
        */
        $instructorSchedule->loadMissing('subTopics:id');

        DB::transaction(function () use (
            $validated,
            $instructorSchedule,
            $batchId,
            $allowedStudentIds
        ) {
            foreach ($validated['attendances'] as $row) {
                $studentId = (int) $row['student_id'];

                if (!in_array($studentId, $allowedStudentIds, true)) {
                    continue;
                }

                $status = $row['status'];

                $isPresentLike = in_array(
                    $status,
                    StudentAttendance::COUNTED_AS_PRESENT,
                    true
                );

                $attendanceMode = $isPresentLike
                    ? ($row['attendance_mode'] ?? StudentAttendance::MODE_OFFLINE)
                    : null;

                $attendance = StudentAttendance::query()->firstOrNew([
                    'instructor_schedule_id' => $instructorSchedule->id,
                    'student_id' => $studentId,
                ]);

                if (!$attendance->exists) {
                    $attendance->created_by = Auth::id();
                }

                $attendance->fill([
                    'batch_id' => $batchId,
                    'status' => $status,
                    'attendance_mode' => $attendanceMode,
                    'attended_at' => $isPresentLike
                        ? ($attendance->attended_at ?: now())
                        : null,
                    'notes' => $row['notes'] ?? null,
                    'updated_by' => Auth::id(),
                ]);

                $attendance->save();

                /*
                * Present / Late:
                * otomatis tandai seluruh lesson pada live session sebagai
                * completed.
                *
                * Absent / Excused:
                * tidak melakukan perubahan apa pun terhadap lesson progress.
                *
                * Completion juga tidak pernah di-rollback supaya progress VOD
                * atau manual completion yang sudah valid tidak ikut terhapus.
                */
                if ($isPresentLike) {
                    $this->completeLiveSessionLessons(
                        $instructorSchedule,
                        $studentId
                    );
                }
            }
        });

        if ($request->expectsJson()) {
            return response()->json([
                'message' => 'Attendance berhasil disimpan.',
                'redirect_url' => route(
                    'academic.attendances.record',
                    $instructorSchedule
                ),
            ]);
        }

        return redirect()
            ->route('academic.attendances.record', $instructorSchedule)
            ->with('success', 'Attendance berhasil disimpan.');
    }

    private function getStudentsForBatch(int $batchId)
    {
        $studentIds = $this->getStudentIdsForBatch($batchId);

        return Student::query()
            ->whereIn('id', $studentIds)
            ->get()
            ->sortBy(fn ($student) => $this->studentDisplayName($student))
            ->values();
    }

    private function getStudentIdsForBatch(int $batchId): array
    {
        if (Schema::hasTable('student_enrollments')) {
            $query = DB::table('student_enrollments')
                ->where('batch_id', $batchId);

            if (Schema::hasColumn('student_enrollments', 'status')) {
                $query->whereIn('status', [
                    'active',
                    'ongoing',
                    'enrolled',
                    'approved',
                    'paid',
                ]);
            }

            $studentIds = $query
                ->pluck('student_id')
                ->filter()
                ->unique()
                ->map(fn ($id) => (int) $id)
                ->values()
                ->all();

            if (!empty($studentIds)) {
                return $studentIds;
            }
        }

        if (Schema::hasColumn('students', 'batch_id')) {
            return Student::query()
                ->where('batch_id', $batchId)
                ->pluck('id')
                ->filter()
                ->unique()
                ->map(fn ($id) => (int) $id)
                ->values()
                ->all();
        }

        return [];
    }

    private function scheduleDateColumn(): ?string
    {
        foreach ([
            'schedule_date',
            'session_date',
            'date',
        ] as $column) {
            if (Schema::hasColumn('instructor_schedules', $column)) {
                return $column;
            }
        }

        return null;
    }

    private function scheduleStartTimeColumn(): ?string
    {
        foreach ([
            'start_time',
            'started_at',
            'session_start_time',
        ] as $column) {
            if (Schema::hasColumn('instructor_schedules', $column)) {
                return $column;
            }
        }

        return null;
    }

    private function scheduleEndTimeColumn(): ?string
    {
        foreach ([
            'end_time',
            'ended_at',
            'session_end_time',
        ] as $column) {
            if (Schema::hasColumn('instructor_schedules', $column)) {
                return $column;
            }
        }

        return null;
    }

    private function availableScheduleRelations(): array
    {
        $relations = [];

        if (method_exists(InstructorSchedule::class, 'batch')) {
            $relations[] = 'batch';
        }

        if (method_exists(InstructorSchedule::class, 'instructor')) {
            $relations[] = 'instructor';
        }

        return $relations;
    }

    private function studentDisplayName(Student $student): string
    {
        foreach ([
            'full_name',
            'student_name',
            'name',
            'email',
            'phone',
        ] as $column) {
            if (isset($student->{$column}) && filled($student->{$column})) {
                return (string) $student->{$column};
            }
        }

        return 'Student #' . $student->id;
    }

    private function completeLiveSessionLessons(
        InstructorSchedule $instructorSchedule,
        int $studentId
    ): void {
        /*
        * Ambil semua subtopic yang memang terkait dengan live session.
        *
        * loadMissing() penting supaya ketika method ini dipanggil berulang
        * untuk beberapa student, relation tidak query ulang terus-menerus.
        */
        $instructorSchedule->loadMissing('subTopics:id');

        $subTopicIds = $instructorSchedule->subTopics
            ->pluck('id')
            ->filter()
            ->map(fn ($id) => (int) $id)
            ->unique()
            ->values();

        /*
        * Fallback untuk schedule lama yang mungkin masih hanya memakai
        * instructor_schedules.sub_topic_id dan belum mempunyai data pivot.
        */
        if ($subTopicIds->isEmpty() && $instructorSchedule->sub_topic_id) {
            $subTopicIds = collect([
                (int) $instructorSchedule->sub_topic_id,
            ]);
        }

        if ($subTopicIds->isEmpty()) {
            return;
        }

        foreach ($subTopicIds as $subTopicId) {
            $progress = StudentLessonProgress::query()->firstOrNew([
                'student_id' => $studentId,
                'sub_topic_id' => $subTopicId,
            ]);

            /*
            * Kalau progress belum pernah ada, buat row minimum.
            *
            * Field VOD sengaja tidak dibuat seolah-olah student sudah
            * menonton video. Attendance hanya menjadi trigger completion.
            */
            if (!$progress->exists) {
                $progress->last_position_seconds = 0;
                $progress->duration_seconds = 0;
                $progress->progress_percentage = 0;
            }

            /*
            * Hanya completion state yang disentuh.
            *
            * Jangan ubah:
            * - last_position_seconds
            * - duration_seconds
            * - progress_percentage existing
            * - last_watched_at
            */
            $progress->is_completed = true;

            if (!$progress->completed_at) {
                $progress->completed_at = now();
            }

            $progress->save();
        }
    }
}