<?php

namespace App\Http\Controllers\Enrollment;

use App\Http\Controllers\Controller;
use App\Models\Batch;
use App\Models\Student;
use App\Models\StudentEnrollment;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Schema;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;
use Throwable;

class StudentController extends Controller
{
    public function index(Request $request): View
    {
        $perPage = (int) $request->get('per_page', 10);

        if (!in_array($perPage, [10, 25, 50, 100], true)) {
            $perPage = 10;
        }

        $students = Student::query()
            ->with([
                'user:id,name,email,user_type,role',
                'enrollments.batch.program',
            ])
            ->latest()
            ->paginate($perPage)
            ->withQueryString();

        $batches = Batch::query()
            ->with('program:id,name')
            ->orderByDesc('start_date')
            ->orderBy('name')
            ->get([
                'id',
                'program_id',
                'name',
                'start_date',
                'end_date',
                'status',
            ]);

        $stats = [
            'total' => Student::count(),

            'active' => Student::where('status', 'active')->count(),

            'inactive' => Student::where('status', 'inactive')->count(),

            'enrolled' => StudentEnrollment::query()
                ->where('status', 'active')
                ->where('access_status', 'active')
                ->count(),

            'login_ready' => Student::query()
                ->whereNotNull('user_id')
                ->count(),
        ];

        return view('enrollment.students.index', compact(
            'students',
            'batches',
            'stats'
        ));
    }

    public function show(Student $student): JsonResponse
    {
        return response()->json([
            'success' => true,
            'data' => [
                'id' => $student->id,
                'full_name' => $student->full_name,
                'email' => $student->email,
                'phone' => $student->phone,
                'city' => $student->city,
                'current_status' => $student->current_status,
                'goal' => $student->goal,
                'source' => $student->source,
                'status' => $student->status,
            ],
        ]);
    }

    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'full_name' => [
                'required',
                'string',
                'max:255',
            ],

            'email' => [
                'nullable',
                'email',
                'max:255',
                'unique:students,email',
            ],

            'phone' => [
                'nullable',
                'string',
                'max:30',
            ],

            'city' => [
                'nullable',
                'string',
                'max:255',
            ],

            'current_status' => [
                'nullable',
                'string',
                'max:255',
            ],

            'goal' => [
                'nullable',
                'string',
            ],

            'source' => [
                'nullable',
                'string',
                'max:255',
            ],

            'status' => [
                'required',
                Rule::in([
                    'active',
                    'inactive',
                ]),
            ],
        ]);

        $student = Student::create($validated);

        return response()->json([
            'success' => true,
            'message' => 'Student created successfully.',
            'data' => $student,
        ], 201);
    }

    public function update(Request $request, Student $student): JsonResponse
    {
        $validated = $request->validate([
            'full_name' => [
                'required',
                'string',
                'max:255',
            ],

            'email' => [
                'nullable',
                'email',
                'max:255',
                Rule::unique('students', 'email')->ignore($student->id),
            ],

            'phone' => [
                'nullable',
                'string',
                'max:30',
            ],

            'city' => [
                'nullable',
                'string',
                'max:255',
            ],

            'current_status' => [
                'nullable',
                'string',
                'max:255',
            ],

            'goal' => [
                'nullable',
                'string',
            ],

            'source' => [
                'nullable',
                'string',
                'max:255',
            ],

            'status' => [
                'required',
                Rule::in([
                    'active',
                    'inactive',
                ]),
            ],
        ]);

        $student->update($validated);

        if ($student->user) {
            $student->user->forceFill([
                'name' => $student->full_name,
            ])->save();
        }

        return response()->json([
            'success' => true,
            'message' => 'Student updated successfully.',
            'data' => $student,
        ]);
    }

    public function enroll(Request $request, Student $student): JsonResponse
    {
        $validated = $request->validate([
            'batch_id' => [
                'required',
                'exists:batches,id',
            ],

            'status' => [
                'required',
                Rule::in([
                    'active',
                    'completed',
                    'cancelled',
                    'on_hold',
                ]),
            ],

            'access_status' => [
                'required',
                Rule::in([
                    'active',
                    'suspended',
                    'expired',
                ]),
            ],

            'enrolled_at' => [
                'nullable',
                'date',
            ],

            'access_expires_at' => [
                'nullable',
                'date',
            ],

            'notes' => [
                'nullable',
                'string',
            ],

            'create_user_account' => [
                'required',
                'boolean',
            ],

            'password' => [
                'nullable',
                'string',
                'min:6',
            ],
        ]);

        try {
            DB::transaction(function () use ($student, $validated) {
                $batch = Batch::query()
                    ->select([
                        'id',
                        'program_id',
                        'name',
                    ])
                    ->findOrFail($validated['batch_id']);

                if ((bool) $validated['create_user_account']) {
                    $this->createOrLinkStudentUser($student, $validated);
                }

                $enrollment = StudentEnrollment::query()->firstOrNew([
                    'student_id' => $student->id,
                    'batch_id' => $batch->id,
                ]);

                $enrollment->fill([
                    'program_id' => $batch->program_id,
                    'status' => $validated['status'],
                    'access_status' => $validated['access_status'],
                    'enrollment_source' => 'manual',
                    'enrolled_at' => $validated['enrolled_at'] ?? now(),

                    'started_at' => $validated['status'] === 'active'
                        ? ($enrollment->started_at ?? now())
                        : $enrollment->started_at,

                    'completed_at' => $validated['status'] === 'completed'
                        ? ($enrollment->completed_at ?? now())
                        : null,

                    'access_expires_at' => $validated['access_expires_at'] ?? null,
                    'notes' => $validated['notes'] ?? null,
                    'updated_by' => auth()->id(),
                ]);

                if (!$enrollment->exists) {
                    $enrollment->created_by = auth()->id();
                }

                $enrollment->save();

                if ($validated['status'] === 'active' && $student->status !== 'active') {
                    $student->update([
                        'status' => 'active',
                    ]);
                }
            });

            return response()->json([
                'success' => true,
                'message' => 'Student enrolled successfully.',
            ]);
        } catch (ValidationException $e) {
            throw $e;
        } catch (Throwable $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to enroll student.',
                'error' => config('app.debug') ? $e->getMessage() : null,
            ], 500);
        }
    }

    public function destroy(Student $student): JsonResponse
    {
        $student->delete();

        return response()->json([
            'success' => true,
            'message' => 'Student deleted successfully.',
        ]);
    }

    private function createOrLinkStudentUser(Student $student, array $validated): void
    {
        if (empty($student->email)) {
            throw ValidationException::withMessages([
                'create_user_account' => [
                    'Student must have an email before LMS login account can be created.',
                ],
            ]);
        }

        $existingUser = User::query()
            ->where('email', $student->email)
            ->first();

        if ($existingUser && !$this->isAllowedStudentUser($existingUser)) {
            throw ValidationException::withMessages([
                'create_user_account' => [
                    'Email already belongs to non-student user account.',
                ],
            ]);
        }

        $password = $validated['password'] ?: 'password';

        if ($existingUser) {
            $user = $existingUser;

            $userPayload = [
                'name' => $student->full_name,
            ];

            if (Schema::hasColumn('users', 'user_type')) {
                $userPayload['user_type'] = 'student';
            }

            if (Schema::hasColumn('users', 'role')) {
                $userPayload['role'] = 'student';
            }

            $user->forceFill($userPayload)->save();
        } else {
            $userPayload = [
                'name' => $student->full_name,
                'email' => $student->email,
                'password' => Hash::make($password),
            ];

            if (Schema::hasColumn('users', 'user_type')) {
                $userPayload['user_type'] = 'student';
            }

            if (Schema::hasColumn('users', 'role')) {
                $userPayload['role'] = 'student';
            }

            $user = new User();
            $user->forceFill($userPayload)->save();
        }

        if ((int) $student->user_id !== (int) $user->id) {
            $student->update([
                'user_id' => $user->id,
            ]);
        }
    }

    private function isAllowedStudentUser(User $user): bool
    {
        $userType = $user->user_type ?? null;
        $role = $user->role ?? null;

        if ($userType === 'student') {
            return true;
        }

        if ($role === 'student') {
            return true;
        }

        return false;
    }
}