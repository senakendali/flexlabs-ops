<?php

namespace App\Http\Controllers\Api\Lms;

use App\Http\Controllers\Controller;
use App\Models\StudentEnrollment;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Schema;
use Illuminate\Validation\ValidationException;

class StudentAuthController extends Controller
{
    public function login(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'email' => ['required', 'email'],
            'password' => ['required', 'string'],
            'device_name' => ['nullable', 'string', 'max:255'],
        ]);

        $user = User::query()
            ->with([
                'student.enrollments.program',
                'student.enrollments.batch.program',
            ])
            ->where('email', $validated['email'])
            ->first();

        if (!$user || !Hash::check($validated['password'], $user->password)) {
            throw ValidationException::withMessages([
                'email' => ['Email atau password salah.'],
            ]);
        }

        if (!$this->isStudentUser($user)) {
            throw ValidationException::withMessages([
                'email' => ['Akun ini bukan akun student.'],
            ]);
        }

        if (!$user->student) {
            throw ValidationException::withMessages([
                'email' => ['Akun student belum terhubung dengan data student.'],
            ]);
        }

        $activeEnrollments = $this->getActiveEnrollments($user);

        if ($activeEnrollments->isEmpty()) {
            throw ValidationException::withMessages([
                'email' => ['Student belum memiliki enrollment aktif.'],
            ]);
        }

        $accessibleEnrollments = $this->getAccessibleEnrollments($activeEnrollments);

        if ($accessibleEnrollments->isEmpty()) {
            throw ValidationException::withMessages([
                'email' => ['Enrollment student aktif, tapi akses LMS belum tersedia atau sudah berakhir.'],
            ]);
        }

        $deviceName = $validated['device_name'] ?? 'lms-student-login';

        $token = $user->createToken($deviceName, ['student:lms'])->plainTextToken;

        return response()->json([
            'success' => true,
            'message' => 'Login berhasil.',
            'data' => [
                'token_type' => 'Bearer',
                'access_token' => $token,
                'user' => $this->formatUser($user),
                'student' => $this->formatStudent($user),
                'enrollments' => $this->formatEnrollments($accessibleEnrollments),
            ],
        ]);
    }

    public function me(Request $request): JsonResponse
    {
        $user = $request->user();

        $user->load([
            'student.enrollments.program',
            'student.enrollments.batch.program',
        ]);

        if (!$this->isStudentUser($user) || !$user->student) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthorized student account.',
            ], 403);
        }

        $activeEnrollments = $this->getActiveEnrollments($user);
        $accessibleEnrollments = $this->getAccessibleEnrollments($activeEnrollments);

        return response()->json([
            'success' => true,
            'data' => [
                'user' => $this->formatUser($user),
                'student' => $this->formatStudent($user),
                'enrollments' => $this->formatEnrollments($accessibleEnrollments),
            ],
        ]);
    }

    public function logout(Request $request): JsonResponse
    {
        $request->user()?->currentAccessToken()?->delete();

        return response()->json([
            'success' => true,
            'message' => 'Logout berhasil.',
        ]);
    }

    private function getActiveEnrollments(User $user): Collection
    {
        if (!$user->student) {
            return collect();
        }

        return $user->student->enrollments
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

    private function getAccessibleEnrollments(Collection $enrollments): Collection
    {
        return $enrollments
            ->filter(function ($enrollment) {
                /*
                 * Kalau accessor is_accessible ada, tetap kita hormati.
                 * Tapi kalau false karena kolom tanggal belum rapih saat development,
                 * logic fallback di bawah bikin pengecekan lebih aman.
                 */
                if (isset($enrollment->is_accessible) && $enrollment->is_accessible === true) {
                    return true;
                }

                $status = strtolower((string) ($enrollment->status ?? ''));
                $accessStatus = strtolower((string) ($enrollment->access_status ?? ''));

                if (!in_array($status, ['active', 'enrolled', 'ongoing', 'paid', 'confirmed'], true)) {
                    return false;
                }

                if (!in_array($accessStatus, ['', 'active', 'enabled', 'open'], true)) {
                    return false;
                }

                /*
                 * Kalau ada access_expires_at dan sudah lewat, akses ditolak.
                 * Kalau kolomnya null, berarti tidak ada expiry.
                 */
                if (!empty($enrollment->access_expires_at) && now()->greaterThan($enrollment->access_expires_at)) {
                    return false;
                }

                return true;
            })
            ->values();
    }

    private function isStudentUser(User $user): bool
    {
        $userType = Schema::hasColumn('users', 'user_type')
            ? ($user->user_type ?? null)
            : null;

        $role = Schema::hasColumn('users', 'role')
            ? ($user->role ?? null)
            : null;

        return $userType === 'student' || $role === 'student';
    }

    private function formatUser(User $user): array
    {
        return [
            'id' => $user->id,
            'name' => $user->name,
            'email' => $user->email,
            'user_type' => $user->user_type ?? null,
            'role' => $user->role ?? null,
        ];
    }

    private function formatStudent(User $user): ?array
    {
        if (!$user->student) {
            return null;
        }

        return [
            'id' => $user->student->id,
            'full_name' => $user->student->full_name,
            'name' => $user->student->full_name,
            'email' => $user->student->email,
            'phone' => $user->student->phone,
            'city' => $user->student->city,
            'current_status' => $user->student->current_status,
            'goal' => $user->student->goal,
            'source' => $user->student->source,
            'status' => $user->student->status,
            'role' => 'FlexLabs Student',
        ];
    }

    private function formatEnrollments(Collection $enrollments): array
    {
        return $enrollments
            ->map(function ($enrollment) {
                return [
                    'id' => $enrollment->id,
                    'status' => $enrollment->status,
                    'status_label' => $enrollment->status_label ?? ucfirst((string) $enrollment->status),

                    'access_status' => $enrollment->access_status,
                    'access_status_label' => $enrollment->access_status_label ?? ucfirst((string) $enrollment->access_status),

                    'enrollment_source' => $enrollment->enrollment_source,
                    'is_accessible' => $enrollment->is_accessible ?? true,

                    'enrolled_at' => $enrollment->enrolled_at?->toISOString(),
                    'started_at' => $enrollment->started_at?->toISOString(),
                    'completed_at' => $enrollment->completed_at?->toISOString(),
                    'access_expires_at' => $enrollment->access_expires_at?->toISOString(),

                    'program' => $enrollment->program ? [
                        'id' => $enrollment->program->id,
                        'name' => $enrollment->program->name,
                    ] : null,

                    'batch' => $enrollment->batch ? [
                        'id' => $enrollment->batch->id,
                        'program_id' => $enrollment->batch->program_id,
                        'program_name' => $enrollment->batch->program->name ?? null,
                        'name' => $enrollment->batch->name,
                        'start_date' => $enrollment->batch->start_date,
                        'end_date' => $enrollment->batch->end_date,
                        'status' => $enrollment->batch->status,
                    ] : null,
                ];
            })
            ->values()
            ->toArray();
    }
}