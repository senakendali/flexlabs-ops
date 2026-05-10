<?php

namespace App\Http\Controllers\Settings;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class UserManagementController extends Controller
{
    private array $roles = [
        'admin' => 'Admin',
        'academic' => 'Academic',
        'marketing' => 'Marketing',
        'sales' => 'Sales',
        'finance' => 'Finance',
        'hr' => 'HR',
        'instructor' => 'Instructor',
        'student' => 'Student',
    ];

    private array $userTypes = [
        'staff' => 'Staff',
        'instructor' => 'Instructor',
        'student' => 'Student',
    ];

    public function index(Request $request): View|JsonResponse
    {
        $this->authorizeAdmin();

        $search = trim((string) $request->query('search'));
        $role = $request->query('role');
        $userType = $request->query('user_type');
        $perPage = (int) $request->query('per_page', 15);

        $perPage = in_array($perPage, [10, 15, 25, 50, 100], true) ? $perPage : 15;

        $users = User::query()
            ->select([
                'id',
                'name',
                'email',
                'role',
                'user_type',
                'email_verified_at',
                'created_at',
                'updated_at',
            ])
            ->when($search !== '', function ($query) use ($search) {
                $query->where(function ($subQuery) use ($search) {
                    $subQuery
                        ->where('name', 'like', "%{$search}%")
                        ->orWhere('email', 'like', "%{$search}%")
                        ->orWhere('role', 'like', "%{$search}%")
                        ->orWhere('user_type', 'like', "%{$search}%");
                });
            })
            ->when($role, function ($query) use ($role) {
                $query->where('role', $role);
            })
            ->when($userType, function ($query) use ($userType) {
                $query->where('user_type', $userType);
            })
            ->latest()
            ->paginate($perPage)
            ->withQueryString();

        if ($this->expectsJson($request)) {
            return response()->json([
                'success' => true,
                'message' => 'Data user berhasil dimuat.',
                'data' => [
                    'users' => $users,
                    'roles' => $this->roles,
                    'user_types' => $this->userTypes,
                    'filters' => [
                        'search' => $search,
                        'role' => $role,
                        'user_type' => $userType,
                        'per_page' => $perPage,
                    ],
                ],
            ]);
        }

        return view('settings.users.index', [
            'users' => $users,
            'roles' => $this->roles,
            'userTypes' => $this->userTypes,
            'selectedRole' => $role,
            'selectedUserType' => $userType,
            'search' => $search,
            'perPage' => $perPage,
        ]);
    }

    public function create(Request $request): View|JsonResponse
    {
        $this->authorizeAdmin();

        if ($this->expectsJson($request)) {
            return response()->json([
                'success' => true,
                'message' => 'Form create user siap digunakan.',
                'data' => [
                    'roles' => $this->roles,
                    'user_types' => $this->userTypes,
                    'defaults' => [
                        'role' => 'academic',
                        'user_type' => 'staff',
                    ],
                ],
            ]);
        }

        return view('settings.users.create', [
            'user' => new User(),
            'roles' => $this->roles,
            'userTypes' => $this->userTypes,
        ]);
    }

    public function store(Request $request): JsonResponse|RedirectResponse
    {
        $this->authorizeAdmin();

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => [
                'required',
                'email',
                'max:255',
                Rule::unique('users', 'email'),
            ],
            'role' => [
                'required',
                'string',
                Rule::in(array_keys($this->roles)),
            ],
            'user_type' => [
                'nullable',
                'string',
                Rule::in(array_keys($this->userTypes)),
            ],
            'password' => ['required', 'string', 'min:8', 'confirmed'],
        ]);

        $user = User::create([
            'name' => $validated['name'],
            'email' => $validated['email'],
            'role' => $validated['role'],
            'user_type' => $this->resolveUserType($validated['role'], $validated['user_type'] ?? null),
            'password' => Hash::make($validated['password']),
        ]);

        if ($this->expectsJson($request)) {
            return response()->json([
                'success' => true,
                'message' => 'User berhasil ditambahkan.',
                'data' => [
                    'user' => $this->formatUser($user),
                ],
            ], 201);
        }

        return redirect()
            ->route('settings.users.index')
            ->with('success', 'User berhasil ditambahkan.');
    }

    public function show(Request $request, User $user): View|JsonResponse
    {
        $this->authorizeAdmin();

        if ($this->expectsJson($request)) {
            return response()->json([
                'success' => true,
                'message' => 'Detail user berhasil dimuat.',
                'data' => [
                    'user' => $this->formatUser($user),
                    'roles' => $this->roles,
                    'user_types' => $this->userTypes,
                ],
            ]);
        }

        return view('settings.users.show', [
            'user' => $user,
            'roles' => $this->roles,
            'userTypes' => $this->userTypes,
        ]);
    }

    public function edit(Request $request, User $user): View|JsonResponse
    {
        $this->authorizeAdmin();

        if ($this->expectsJson($request)) {
            return response()->json([
                'success' => true,
                'message' => 'Data user berhasil dimuat untuk edit.',
                'data' => [
                    'user' => $this->formatUser($user),
                    'roles' => $this->roles,
                    'user_types' => $this->userTypes,
                ],
            ]);
        }

        return view('settings.users.edit', [
            'user' => $user,
            'roles' => $this->roles,
            'userTypes' => $this->userTypes,
        ]);
    }

    public function update(Request $request, User $user): JsonResponse|RedirectResponse
    {
        $this->authorizeAdmin();

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => [
                'required',
                'email',
                'max:255',
                Rule::unique('users', 'email')->ignore($user->id),
            ],
            'role' => [
                'required',
                'string',
                Rule::in(array_keys($this->roles)),
            ],
            'user_type' => [
                'nullable',
                'string',
                Rule::in(array_keys($this->userTypes)),
            ],
        ]);

        $newRole = $validated['role'];

        if ($this->isEditingOwnAccount($user) && $newRole !== 'admin') {
            return $this->errorResponse(
                $request,
                'Role akun sendiri tidak boleh diubah dari admin.',
                422
            );
        }

        if ($this->isLastAdmin($user) && $newRole !== 'admin') {
            return $this->errorResponse(
                $request,
                'Minimal harus ada satu akun admin aktif di sistem.',
                422
            );
        }

        $user->update([
            'name' => $validated['name'],
            'email' => $validated['email'],
            'role' => $newRole,
            'user_type' => $this->resolveUserType($newRole, $validated['user_type'] ?? null),
        ]);

        if ($this->expectsJson($request)) {
            return response()->json([
                'success' => true,
                'message' => 'User berhasil diperbarui.',
                'data' => [
                    'user' => $this->formatUser($user->fresh()),
                ],
            ]);
        }

        return redirect()
            ->route('settings.users.index')
            ->with('success', 'User berhasil diperbarui.');
    }

    public function updatePassword(Request $request, User $user): JsonResponse|RedirectResponse
    {
        $this->authorizeAdmin();

        $validated = $request->validate([
            'password' => ['required', 'string', 'min:8', 'confirmed'],
        ]);

        $user->update([
            'password' => Hash::make($validated['password']),
        ]);

        if ($this->expectsJson($request)) {
            return response()->json([
                'success' => true,
                'message' => 'Password user berhasil diperbarui.',
                'data' => [
                    'user' => $this->formatUser($user->fresh()),
                ],
            ]);
        }

        return back()->with('success', 'Password user berhasil diperbarui.');
    }

    public function destroy(Request $request, User $user): JsonResponse|RedirectResponse
    {
        $this->authorizeAdmin();

        if ($this->isEditingOwnAccount($user)) {
            return $this->errorResponse(
                $request,
                'Akun sendiri tidak boleh dihapus.',
                422
            );
        }

        if ($this->isLastAdmin($user)) {
            return $this->errorResponse(
                $request,
                'Minimal harus ada satu akun admin aktif di sistem.',
                422
            );
        }

        $deletedUserId = $user->id;

        $user->delete();

        if ($this->expectsJson($request)) {
            return response()->json([
                'success' => true,
                'message' => 'User berhasil dihapus.',
                'data' => [
                    'deleted_user_id' => $deletedUserId,
                ],
            ]);
        }

        return redirect()
            ->route('settings.users.index')
            ->with('success', 'User berhasil dihapus.');
    }

    private function authorizeAdmin(): void
    {
        abort_unless(
            auth()->check() && (auth()->user()->role ?? null) === 'admin',
            403
        );
    }

    private function resolveUserType(string $role, ?string $requestedUserType = null): string
    {
        return match ($role) {
            'student' => 'student',
            'instructor' => 'instructor',
            default => 'staff',
        };
    }

    private function formatUser(User $user): array
    {
        return [
            'id' => $user->id,
            'name' => $user->name,
            'email' => $user->email,
            'role' => $user->role,
            'role_label' => $this->roles[$user->role] ?? ucfirst((string) $user->role),
            'user_type' => $user->user_type,
            'user_type_label' => $this->userTypes[$user->user_type] ?? ucfirst((string) $user->user_type),
            'email_verified_at' => optional($user->email_verified_at)->format('Y-m-d H:i:s'),
            'created_at' => optional($user->created_at)->format('Y-m-d H:i:s'),
            'updated_at' => optional($user->updated_at)->format('Y-m-d H:i:s'),
            'is_current_user' => auth()->id() === $user->id,
        ];
    }

    private function expectsJson(Request $request): bool
    {
        return $request->expectsJson()
            || $request->ajax()
            || $request->wantsJson()
            || $request->header('X-Requested-With') === 'XMLHttpRequest';
    }

    private function isEditingOwnAccount(User $user): bool
    {
        return auth()->id() === $user->id;
    }

    private function isLastAdmin(User $user): bool
    {
        if ($user->role !== 'admin') {
            return false;
        }

        return User::query()
            ->where('role', 'admin')
            ->count() <= 1;
    }

    private function errorResponse(
        Request $request,
        string $message,
        int $status = 422
    ): JsonResponse|RedirectResponse {
        if ($this->expectsJson($request)) {
            return response()->json([
                'success' => false,
                'message' => $message,
            ], $status);
        }

        return back()
            ->withInput()
            ->with('error', $message);
    }
}