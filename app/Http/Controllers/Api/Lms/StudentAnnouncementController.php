<?php

namespace App\Http\Controllers\Api\Lms;

use App\Http\Controllers\Controller;
use App\Models\Announcement;
use App\Models\Student;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;

class StudentAnnouncementController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $student = $this->resolveStudent($request->user());

        [$programIds, $batchIds] = $this->resolveEnrollmentTargets($student);

        $announcements = Announcement::query()
            ->with([
                'program',
                'batch.program',
            ])
            ->visibleNow()
            ->forProgramsAndBatches($programIds, $batchIds)
            ->orderByDesc('is_pinned')
            ->latest('publish_at')
            ->latest('id')
            ->limit(10)
            ->get()
            ->map(fn (Announcement $announcement) => $this->formatAnnouncement($announcement))
            ->values();

        $payload = [
            'success' => true,
            'data' => [
                'announcements' => $announcements,
            ],
        ];

        /*
         * Optional debug:
         * GET /api/lms/student/announcements?debug=1
         */
        if ($request->boolean('debug')) {
            $payload['debug'] = [
                'student_id' => $student->id,
                'program_ids' => $programIds->values(),
                'batch_ids' => $batchIds->values(),
                'enrollments' => $student->enrollments
                    ->map(fn ($enrollment) => [
                        'id' => $enrollment->id,
                        'student_id' => $enrollment->student_id,
                        'program_id' => $enrollment->program_id,
                        'batch_id' => $enrollment->batch_id,
                        'status' => $enrollment->status,
                        'access_status' => $enrollment->access_status,
                        'program_name' => $enrollment->program?->name,
                        'batch_name' => $enrollment->batch?->name,
                        'batch_program_id' => $enrollment->batch?->program_id,
                        'batch_program_name' => $enrollment->batch?->program?->name,
                    ])
                    ->values(),
            ];
        }

        return response()->json($payload);
    }

    public function show(Request $request, Announcement $announcement): JsonResponse
    {
        $student = $this->resolveStudent($request->user());

        [$programIds, $batchIds] = $this->resolveEnrollmentTargets($student);

        $isAllowed = Announcement::query()
            ->whereKey($announcement->id)
            ->visibleNow()
            ->forProgramsAndBatches($programIds, $batchIds)
            ->exists();

        if (!$isAllowed) {
            return response()->json([
                'success' => false,
                'message' => 'Announcement tidak ditemukan atau tidak tersedia untuk akun ini.',
            ], 404);
        }

        $announcement->load([
            'program',
            'batch.program',
        ]);

        return response()->json([
            'success' => true,
            'data' => [
                'announcement' => $this->formatAnnouncement($announcement, true),
            ],
        ]);
    }

    private function resolveStudent(?User $user): Student
    {
        if (!$user || !$this->isStudentUser($user)) {
            abort(response()->json([
                'success' => false,
                'message' => 'Unauthorized student account.',
            ], 403));
        }

        /*
         * Jangan pakai activeEnrollments di sini.
         * Untuk announcement, kita cukup baca enrollments lalu filter status manual.
         * Ini menghindari announcement hilang karena accessor is_accessible / access expiry.
         */
        $user->loadMissing([
            'student.enrollments.program',
            'student.enrollments.batch.program',
        ]);

        if (!$user->student) {
            abort(response()->json([
                'success' => false,
                'message' => 'Student profile tidak ditemukan.',
            ], 403));
        }

        return $user->student;
    }

    private function isStudentUser(User $user): bool
    {
        return ($user->user_type ?? null) === 'student'
            || ($user->role ?? null) === 'student';
    }

    private function resolveEnrollmentTargets(Student $student): array
    {
        $activeEnrollments = $this->getAnnouncementEligibleEnrollments($student);

        $programIds = $activeEnrollments
            ->map(function ($enrollment) {
                return $enrollment->program?->id
                    ?? $enrollment->program_id
                    ?? $enrollment->batch?->program?->id
                    ?? $enrollment->batch?->program_id;
            })
            ->filter()
            ->map(fn ($id) => (int) $id)
            ->unique()
            ->values();

        $batchIds = $activeEnrollments
            ->pluck('batch_id')
            ->filter()
            ->map(fn ($id) => (int) $id)
            ->unique()
            ->values();

        return [$programIds, $batchIds];
    }

    private function getAnnouncementEligibleEnrollments(Student $student): Collection
    {
        return $student->enrollments
            ->filter(function ($enrollment) {
                $status = strtolower((string) ($enrollment->status ?? ''));
                $accessStatus = strtolower((string) ($enrollment->access_status ?? ''));

                $validStatuses = [
                    'active',
                    'enrolled',
                    'ongoing',
                    'paid',
                    'confirmed',
                ];

                $validAccessStatuses = [
                    '',
                    'active',
                    'enabled',
                    'open',
                ];

                return in_array($status, $validStatuses, true)
                    && in_array($accessStatus, $validAccessStatuses, true);
            })
            ->values();
    }

    private function formatAnnouncement(Announcement $announcement, bool $includeContent = false): array
    {
        $content = (string) ($announcement->content ?? '');
        $plainContent = trim(strip_tags($content));

        return [
            'id' => $announcement->id,
            'title' => $announcement->title,
            'slug' => $announcement->slug,

            'content' => $includeContent ? $announcement->content : null,
            'excerpt' => Str::limit($plainContent, 140),

            'target_type' => $announcement->target_type,
            'target_label' => $announcement->target_label,

            'program_id' => $announcement->program_id,
            'program_name' => $announcement->program?->name,

            'batch_id' => $announcement->batch_id,
            'batch_name' => $announcement->batch?->name,
            'batch_program_name' => $announcement->batch?->program?->name,

            'is_pinned' => (bool) $announcement->is_pinned,

            'publish_at' => $announcement->publish_at?->format('Y-m-d H:i:s'),
            'publish_at_label' => $announcement->publish_at?->format('d M Y H:i'),

            'expired_at' => $announcement->expired_at?->format('Y-m-d H:i:s'),
            'expired_at_label' => $announcement->expired_at?->format('d M Y H:i'),

            'url' => '/announcements/' . $announcement->slug,
        ];
    }
}