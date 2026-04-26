<?php

namespace App\Http\Controllers\Api\Lms;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
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
                'student.activeEnrollments.program',
                'student.activeEnrollments.batch.program',
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

        $activeEnrollments = $user->student->activeEnrollments
            ->filter(fn ($enrollment) => $enrollment->is_accessible)
            ->values();

        if ($activeEnrollments->isEmpty()) {
            throw ValidationException::withMessages([
                'email' => ['Student belum memiliki enrollment aktif.'],
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
                'enrollments' => $this->formatEnrollments($activeEnrollments),
            ],
        ]);
    }

    public function me(Request $request): JsonResponse
    {
        $user = $request->user();

        $user->load([
            'student.activeEnrollments.program',
            'student.activeEnrollments.batch.program',
        ]);

        if (!$this->isStudentUser($user) || !$user->student) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthorized student account.',
            ], 403);
        }

        $activeEnrollments = $user->student->activeEnrollments
            ->filter(fn ($enrollment) => $enrollment->is_accessible)
            ->values();

        return response()->json([
            'success' => true,
            'data' => [
                'user' => $this->formatUser($user),
                'student' => $this->formatStudent($user),
                'enrollments' => $this->formatEnrollments($activeEnrollments),
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
            'email' => $user->student->email,
            'phone' => $user->student->phone,
            'city' => $user->student->city,
            'current_status' => $user->student->current_status,
            'goal' => $user->student->goal,
            'source' => $user->student->source,
            'status' => $user->student->status,
        ];
    }

    private function formatEnrollments($enrollments): array
    {
        return $enrollments
            ->map(function ($enrollment) {
                return [
                    'id' => $enrollment->id,
                    'status' => $enrollment->status,
                    'status_label' => $enrollment->status_label,
                    'access_status' => $enrollment->access_status,
                    'access_status_label' => $enrollment->access_status_label,
                    'enrollment_source' => $enrollment->enrollment_source,
                    'is_accessible' => $enrollment->is_accessible,

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