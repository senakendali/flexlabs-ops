<?php

namespace App\Http\Controllers\Instructor;

use App\Http\Controllers\Controller;
use App\Models\Batch;
use App\Models\Instructor;
use App\Models\Program;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class InstructorController extends Controller
{
    public function index(Request $request): View
    {
        $perPage = (int) $request->get('per_page', 10);

        if (!in_array($perPage, [10, 25, 50, 100], true)) {
            $perPage = 10;
        }

        $instructors = Instructor::query()
            ->latest()
            ->paginate($perPage)
            ->withQueryString();

        /**
         * Untuk modal Assign Teaching Scope.
         * Kalau nama model/table beda, tinggal adjust di sini.
         */
        $programs = Program::query()
            ->when(Schema::hasColumn('programs', 'is_active'), fn ($query) => $query->where('is_active', true))
            ->orderBy('name')
            ->get();

       $batches = Batch::query()
            ->when(method_exists(Batch::class, 'program'), fn ($query) => $query->with('program'))
            ->when(Schema::hasColumn('batches', 'is_active'), fn ($query) => $query->where('is_active', true))
            ->latest()
            ->get();

        $stats = [
            'total' => Instructor::count(),
            'active' => Instructor::where('is_active', true)->count(),
            'full_time' => Instructor::where('employment_type', 'full_time')->count(),
            'part_time' => Instructor::where('employment_type', 'part_time')->count(),
        ];

        return view('instructors.index', compact(
            'instructors',
            'programs',
            'batches',
            'stats'
        ));
    }

    public function show(Instructor $instructor): JsonResponse
    {
        return response()->json([
            'success' => true,
            'message' => 'Instructor berhasil diambil.',
            'data' => $this->formatInstructorResponse($instructor),
        ]);
    }

    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'slug' => ['nullable', 'string', 'max:255', 'unique:instructors,slug'],
            'email' => ['nullable', 'email', 'max:255', 'unique:instructors,email'],
            'phone' => ['nullable', 'string', 'max:30'],
            'specialization' => ['nullable', 'string', 'max:255'],
            'employment_type' => ['nullable', Rule::in(['full_time', 'part_time'])],
            'bio' => ['nullable', 'string'],
            'photo' => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'max:2048'],
            'is_active' => ['nullable', 'boolean'],
        ]);

        return DB::transaction(function () use ($request, $validated) {
            $validated['slug'] = $this->generateUniqueSlug(
                $validated['slug'] ?? null,
                $validated['name']
            );

            $validated['is_active'] = $request->boolean('is_active', true);

            $photoPath = $this->storePhoto($request);

            /**
             * Create / sync user hanya kalau email diisi.
             * Kalau email kosong, instructor tetap bisa dibuat sebagai master data.
             */
            $user = null;

            if (!blank($validated['email'] ?? null)) {
                $user = $this->syncInstructorUser(
                    name: $validated['name'],
                    email: $validated['email'],
                    shouldCreateWithDefaultPassword: true
                );
            }

            $instructor = Instructor::create([
                'name' => $validated['name'],
                'slug' => $validated['slug'],
                'email' => $validated['email'] ?? null,
                'phone' => $validated['phone'] ?? null,
                'specialization' => $validated['specialization'] ?? null,
                'employment_type' => $validated['employment_type'] ?? 'part_time',
                'bio' => $validated['bio'] ?? null,
                'photo' => $photoPath,
                'is_active' => $validated['is_active'],
            ]);

            /**
             * Optional kalau nanti lu tambahin user_id ke instructors.
             * Controller ini sudah siap tanpa bikin error.
             */
            if ($user && Schema::hasColumn('instructors', 'user_id')) {
                $instructor->forceFill([
                    'user_id' => $user->id,
                ])->save();
            }

            return response()->json([
                'success' => true,
                'message' => 'Instructor berhasil ditambahkan. User login instructor juga sudah disiapkan.',
                'data' => $this->formatInstructorResponse($instructor->fresh()),
            ], 201);
        });
    }

    public function update(Request $request, Instructor $instructor): JsonResponse
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'slug' => [
                'nullable',
                'string',
                'max:255',
                Rule::unique('instructors', 'slug')->ignore($instructor->id),
            ],
            'email' => [
                'nullable',
                'email',
                'max:255',
                Rule::unique('instructors', 'email')->ignore($instructor->id),
            ],
            'phone' => ['nullable', 'string', 'max:30'],
            'specialization' => ['nullable', 'string', 'max:255'],
            'employment_type' => ['nullable', Rule::in(['full_time', 'part_time'])],
            'bio' => ['nullable', 'string'],
            'photo' => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'max:2048'],
            'is_active' => ['nullable', 'boolean'],
        ]);

        return DB::transaction(function () use ($request, $validated, $instructor) {
            $validated['slug'] = $this->generateUniqueSlug(
                $validated['slug'] ?? null,
                $validated['name'],
                $instructor->id
            );

            $validated['is_active'] = $request->boolean('is_active', true);

            $photoPath = $instructor->photo;

            if ($request->hasFile('photo')) {
                $this->deletePhoto($instructor->photo);
                $photoPath = $this->storePhoto($request);
            }

            /**
             * Update flow:
             * - Cari user berdasarkan email baru.
             * - Kalau ada, update name + role/user_type instructor.
             * - Kalau tidak ada, create user baru dengan password default.
             */
            $user = null;

            if (!blank($validated['email'] ?? null)) {
                $user = $this->syncInstructorUser(
                    name: $validated['name'],
                    email: $validated['email'],
                    shouldCreateWithDefaultPassword: true
                );
            }

            $payload = [
                'name' => $validated['name'],
                'slug' => $validated['slug'],
                'email' => $validated['email'] ?? null,
                'phone' => $validated['phone'] ?? null,
                'specialization' => $validated['specialization'] ?? null,
                'employment_type' => $validated['employment_type'] ?? 'part_time',
                'bio' => $validated['bio'] ?? null,
                'photo' => $photoPath,
                'is_active' => $validated['is_active'],
            ];

            if ($user && Schema::hasColumn('instructors', 'user_id')) {
                $payload['user_id'] = $user->id;
            }

            $instructor->update($payload);

            return response()->json([
                'success' => true,
                'message' => 'Instructor berhasil diupdate. User login instructor juga sudah disinkronkan.',
                'data' => $this->formatInstructorResponse($instructor->fresh()),
            ]);
        });
    }

    public function destroy(Instructor $instructor): JsonResponse
    {
        return DB::transaction(function () use ($instructor) {
            /**
             * User sengaja tidak dihapus.
             * Karena akun user bisa dipakai di modul lain.
             */
            $this->deletePhoto($instructor->photo);

            $instructor->delete();

            return response()->json([
                'success' => true,
                'message' => 'Instructor berhasil dihapus.',
            ]);
        });
    }

    /**
     * Endpoint ini disiapkan untuk modal Assign Teaching Scope.
     *
     * Nanti perlu table:
     * instructor_teaching_assignments
     * - instructor_id
     * - program_id
     * - batch_id nullable
     * - assignment_role
     * - status
     * - notes
     */
    public function assignTeachingScope(Request $request, Instructor $instructor): JsonResponse
    {
        $validated = $request->validate([
            'program_id' => ['required', 'integer', Rule::exists('programs', 'id')],
            'batch_id' => ['nullable', 'integer', Rule::exists('batches', 'id')],
            'assignment_role' => [
                'required',
                Rule::in([
                    'primary_instructor',
                    'assistant_instructor',
                    'mentor',
                    'reviewer',
                ]),
            ],
            'status' => ['required', Rule::in(['active', 'inactive'])],
            'notes' => ['nullable', 'string'],
        ]);

        if (!Schema::hasTable('instructor_teaching_scopes')) {
            return response()->json([
                'success' => false,
                'message' => 'Table instructor_teaching_scopes belum dibuat. Jalankan migration terlebih dahulu.',
            ], 422);
        }

        if (!empty($validated['batch_id']) && Schema::hasColumn('batches', 'program_id')) {
            $batchMatchesProgram = Batch::query()
                ->where('id', $validated['batch_id'])
                ->where('program_id', $validated['program_id'])
                ->exists();

            if (!$batchMatchesProgram) {
                return response()->json([
                    'success' => false,
                    'message' => 'Batch tidak sesuai dengan program/course yang dipilih.',
                    'errors' => [
                        'batch_id' => ['Batch tidak sesuai dengan program/course yang dipilih.'],
                    ],
                ], 422);
            }
        }

        DB::table('instructor_teaching_scopes')->updateOrInsert(
            [
                'instructor_id' => $instructor->id,
                'program_id' => $validated['program_id'],
                'batch_id' => $validated['batch_id'] ?? null,
                'teaching_role' => $validated['assignment_role'],
            ],
            [
                'status' => $validated['status'],
                'notes' => $validated['notes'] ?? null,
                'updated_at' => now(),
                'created_at' => now(),
            ]
        );

        return response()->json([
            'success' => true,
            'message' => 'Teaching scope berhasil disimpan.',
        ]);
    }

    private function syncInstructorUser(string $name, string $email, bool $shouldCreateWithDefaultPassword = true): User
    {
        $user = User::query()
            ->where('email', $email)
            ->first();

        if (!$user) {
            $user = new User();
            $user->email = $email;

            if ($shouldCreateWithDefaultPassword) {
                $user->password = Hash::make($this->defaultInstructorPassword());
            }
        }

        $user->name = $name;

        /**
         * Biar aman untuk schema user lu yang beda-beda.
         * Kemarin user punya kolom role dan user_type.
         */
        if (Schema::hasColumn('users', 'user_type')) {
            $user->user_type = 'instructor';
        }

        if (Schema::hasColumn('users', 'role')) {
            $user->role = 'instructor';
        }

        if (Schema::hasColumn('users', 'email_verified_at') && !$user->email_verified_at) {
            $user->email_verified_at = now();
        }

        $user->save();

        return $user;
    }

    private function defaultInstructorPassword(): string
    {
        /**
         * Bisa set di .env:
         * DEFAULT_INSTRUCTOR_PASSWORD=password
         */
        return env('DEFAULT_INSTRUCTOR_PASSWORD', 'password');
    }

    private function storePhoto(Request $request): ?string
    {
        if (!$request->hasFile('photo')) {
            return null;
        }

        return $request->file('photo')->store('instructors', 'public');
    }

    private function deletePhoto(?string $photo): void
    {
        if (blank($photo)) {
            return;
        }

        if (Str::startsWith($photo, ['http://', 'https://'])) {
            return;
        }

        if (Storage::disk('public')->exists($photo)) {
            Storage::disk('public')->delete($photo);
        }
    }

    private function formatInstructorResponse(Instructor $instructor): array
    {
        $photoUrl = null;

        if (!blank($instructor->photo)) {
            $photoUrl = Str::startsWith($instructor->photo, ['http://', 'https://'])
                ? $instructor->photo
                : asset('storage/' . $instructor->photo);
        }

        return [
            'id' => $instructor->id,
            'name' => $instructor->name,
            'slug' => $instructor->slug,
            'email' => $instructor->email,
            'phone' => $instructor->phone,
            'specialization' => $instructor->specialization,
            'employment_type' => $instructor->employment_type,
            'bio' => $instructor->bio,
            'photo' => $instructor->photo,
            'photo_url' => $photoUrl,
            'is_active' => (bool) $instructor->is_active,
            'user_id' => Schema::hasColumn('instructors', 'user_id')
                ? $instructor->user_id
                : null,
        ];
    }

    private function generateUniqueSlug(?string $slug, string $name, ?int $ignoreId = null): string
    {
        $baseSlug = Str::slug($slug ?: $name);

        if (blank($baseSlug)) {
            $baseSlug = 'instructor';
        }

        $finalSlug = $baseSlug;
        $counter = 1;

        while ($this->slugExists($finalSlug, $ignoreId)) {
            $finalSlug = $baseSlug . '-' . $counter;
            $counter++;
        }

        return $finalSlug;
    }

    private function slugExists(string $slug, ?int $ignoreId = null): bool
    {
        return Instructor::query()
            ->when($ignoreId, fn ($query) => $query->where('id', '!=', $ignoreId))
            ->where('slug', $slug)
            ->exists();
    }
}