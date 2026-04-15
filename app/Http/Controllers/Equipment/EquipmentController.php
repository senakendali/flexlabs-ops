<?php

namespace App\Http\Controllers\Equipment;

use App\Http\Controllers\Controller;
use App\Models\Equipment;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class EquipmentController extends Controller
{
    public function index(Request $request): View
    {
        $perPage = (int) $request->get('per_page', 10);

        if (!in_array($perPage, [10, 25, 50, 100], true)) {
            $perPage = 10;
        }

        $equipments = Equipment::query()
            ->with([
                'activeBorrowing.user',
                'assignedUser',
                'activeAssignment.user',
            ])
            ->latest()
            ->paginate($perPage)
            ->withQueryString();

        $users = User::query()
            ->orderBy('name')
            ->get(['id', 'name', 'email']);

        return view('equipment.index', compact('equipments', 'users'));
    }

    public function show(Equipment $equipment): JsonResponse
    {
        $equipment->load([
            'borrowings' => fn ($query) => $query
                ->latest()
                ->with('user'),
            'activeBorrowing.user',
            'assignedUser',
            'assignments' => fn ($query) => $query
                ->latest('assigned_at')
                ->with('user'),
            'activeAssignment.user',
        ]);

        return response()->json([
            'success' => true,
            'data' => [
                'id' => $equipment->id,
                'name' => $equipment->name,
                'slug' => $equipment->slug,
                'code' => $equipment->code,
                'serial_number' => $equipment->serial_number,
                'brand' => $equipment->brand,
                'model' => $equipment->model,
                'description' => $equipment->description,
                'type' => $equipment->type ?? 'borrowable',
                'status' => $equipment->status,
                'condition' => $equipment->condition,
                'location' => $equipment->location,
                'purchase_date' => optional($equipment->purchase_date)?->format('Y-m-d'),
                'purchase_price' => $equipment->purchase_price,
                'last_maintenance_at' => optional($equipment->last_maintenance_at)?->toISOString(),
                'assigned_user_id' => $equipment->assigned_user_id,
                'is_active' => (bool) $equipment->is_active,

                'assigned_user' => $equipment->assignedUser ? [
                    'id' => $equipment->assignedUser->id,
                    'name' => $equipment->assignedUser->name,
                    'email' => $equipment->assignedUser->email,
                ] : null,

                'active_assignment' => $equipment->activeAssignment ? [
                    'id' => $equipment->activeAssignment->id,
                    'assigned_at' => $equipment->activeAssignment->assigned_at,
                    'unassigned_at' => $equipment->activeAssignment->unassigned_at,
                    'notes' => $equipment->activeAssignment->notes,
                    'user' => $equipment->activeAssignment->user ? [
                        'id' => $equipment->activeAssignment->user->id,
                        'name' => $equipment->activeAssignment->user->name,
                        'email' => $equipment->activeAssignment->user->email,
                    ] : null,
                ] : null,

                'assignments' => $equipment->assignments->map(function ($item) {
                    return [
                        'id' => $item->id,
                        'assigned_at' => $item->assigned_at,
                        'unassigned_at' => $item->unassigned_at,
                        'notes' => $item->notes,
                        'user' => $item->user ? [
                            'id' => $item->user->id,
                            'name' => $item->user->name,
                            'email' => $item->user->email,
                        ] : null,
                    ];
                })->values(),

                'active_borrowing' => $equipment->activeBorrowing ? [
                    'id' => $equipment->activeBorrowing->id,
                    'borrowed_at' => $equipment->activeBorrowing->borrowed_at,
                    'expected_return_at' => $equipment->activeBorrowing->expected_return_at,
                    'returned_at' => $equipment->activeBorrowing->returned_at,
                    'status' => $equipment->activeBorrowing->status,
                    'notes' => $equipment->activeBorrowing->notes,
                    'return_notes' => $equipment->activeBorrowing->return_notes,
                    'user' => $equipment->activeBorrowing->user ? [
                        'id' => $equipment->activeBorrowing->user->id,
                        'name' => $equipment->activeBorrowing->user->name,
                        'email' => $equipment->activeBorrowing->user->email,
                    ] : null,
                ] : null,

                'borrowings' => $equipment->borrowings->map(function ($item) {
                    return [
                        'id' => $item->id,
                        'borrowed_at' => $item->borrowed_at,
                        'expected_return_at' => $item->expected_return_at,
                        'returned_at' => $item->returned_at,
                        'status' => $item->status,
                        'notes' => $item->notes,
                        'return_notes' => $item->return_notes,
                        'user' => $item->user ? [
                            'id' => $item->user->id,
                            'name' => $item->user->name,
                            'email' => $item->user->email,
                        ] : null,
                    ];
                })->values(),
            ],
        ]);
    }

    public function store(Request $request): JsonResponse
    {
        $validated = $this->validateEquipment($request);

        $validated['slug'] = $this->generateUniqueSlug(
            $validated['slug'] ?? null,
            $validated['name']
        );

        $validated['code'] = $this->generateUniqueCode();
        $validated['is_active'] = (bool) ($validated['is_active'] ?? true);
        $validated['type'] = $validated['type'] ?? 'borrowable';

        $validated = $this->normalizeAssetPayload($validated);

        $equipment = DB::transaction(function () use ($validated) {
            $equipment = Equipment::create($validated);

            if (
                ($validated['type'] ?? 'borrowable') === 'assigned' &&
                !empty($validated['assigned_user_id'])
            ) {
                $equipment->assignments()->create([
                    'user_id' => $validated['assigned_user_id'],
                    'assigned_at' => now(),
                    'notes' => 'Initial assignment on equipment creation.',
                ]);
            }

            return $equipment;
        });

        return response()->json([
            'success' => true,
            'message' => 'Equipment berhasil ditambahkan.',
            'data' => $equipment->fresh()->load([
                'assignedUser',
                'activeAssignment.user',
                'activeBorrowing.user',
            ]),
        ]);
    }

    public function update(Request $request, Equipment $equipment): JsonResponse
    {
        $validated = $this->validateEquipment($request, $equipment);

        $validated['slug'] = $this->generateUniqueSlug(
            $validated['slug'] ?? null,
            $validated['name'],
            $equipment->id
        );

        $validated['is_active'] = (bool) ($validated['is_active'] ?? true);
        $validated['type'] = $validated['type'] ?? ($equipment->type ?? 'borrowable');

        $validated = $this->normalizeAssetPayload($validated, $equipment);

        DB::transaction(function () use ($equipment, $validated) {
            $oldType = $equipment->type ?? 'borrowable';
            $oldAssignedUserId = $equipment->assigned_user_id;

            $equipment->update($validated);

            $newType = $validated['type'] ?? 'borrowable';
            $newAssignedUserId = $validated['assigned_user_id'] ?? null;

            if ($newType === 'assigned') {
                if ((int) ($oldAssignedUserId ?? 0) !== (int) ($newAssignedUserId ?? 0)) {
                    $this->syncAssignmentHistory(
                        equipment: $equipment,
                        newUserId: $newAssignedUserId,
                        notes: 'Assignment updated from equipment form.'
                    );
                }
            }

            if ($oldType === 'assigned' && $newType === 'borrowable') {
                $equipment->assignments()
                    ->whereNull('unassigned_at')
                    ->update([
                        'unassigned_at' => now(),
                    ]);
            }
        });

        return response()->json([
            'success' => true,
            'message' => 'Equipment berhasil diperbarui.',
            'data' => $equipment->fresh()->load([
                'assignedUser',
                'activeAssignment.user',
                'activeBorrowing.user',
            ]),
        ]);
    }

    public function destroy(Equipment $equipment): JsonResponse
    {
        $hasActiveBorrowing = $equipment->borrowings()
            ->where('status', 'borrowed')
            ->exists();

        if ($hasActiveBorrowing) {
            return response()->json([
                'success' => false,
                'message' => 'Equipment sedang dipinjam dan tidak bisa dihapus.',
            ], 422);
        }

        $hasActiveAssignment = $equipment->assignments()
            ->whereNull('unassigned_at')
            ->exists();

        if ($hasActiveAssignment) {
            return response()->json([
                'success' => false,
                'message' => 'Equipment masih ter-assign dan tidak bisa dihapus.',
            ], 422);
        }

        $equipment->delete();

        return response()->json([
            'success' => true,
            'message' => 'Equipment berhasil dihapus.',
        ]);
    }

    private function validateEquipment(Request $request, ?Equipment $equipment = null): array
    {
        return $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'slug' => [
                'nullable',
                'string',
                'max:255',
                Rule::unique('equipments', 'slug')->ignore($equipment?->id),
            ],
            'serial_number' => [
                'nullable',
                'string',
                'max:255',
                Rule::unique('equipments', 'serial_number')->ignore($equipment?->id),
            ],
            'brand' => ['nullable', 'string', 'max:255'],
            'model' => ['nullable', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'condition' => ['required', Rule::in(['good', 'minor_damage', 'damaged'])],
            'status' => ['required', Rule::in(['available', 'borrowed', 'maintenance'])],
            'type' => ['nullable', Rule::in(['assigned', 'borrowable'])],
            'assigned_user_id' => ['nullable', 'exists:users,id'],
            'location' => ['nullable', 'string', 'max:255'],
            'purchase_date' => ['nullable', 'date'],
            'purchase_price' => ['nullable', 'numeric', 'min:0'],
            'last_maintenance_at' => ['nullable', 'date'],
            'is_active' => ['nullable', 'boolean'],
        ]);
    }

    private function syncAssignmentHistory(Equipment $equipment, ?int $newUserId, ?string $notes = null): void
    {
        $equipment->assignments()
            ->whereNull('unassigned_at')
            ->update([
                'unassigned_at' => now(),
            ]);

        if ($newUserId) {
            $equipment->assignments()->create([
                'user_id' => $newUserId,
                'assigned_at' => now(),
                'notes' => $notes,
            ]);
        }
    }

    private function normalizeAssetPayload(array $validated, ?Equipment $equipment = null): array
    {
        $type = $validated['type'] ?? 'borrowable';

        if ($type === 'assigned') {
            if (($validated['status'] ?? null) === 'borrowed') {
                $validated['status'] = 'available';
            }
        }

        if ($type === 'borrowable') {
            $validated['assigned_user_id'] = null;
        }

        $validated['location'] = filled($validated['location'] ?? null)
            ? trim((string) $validated['location'])
            : null;

        $validated['brand'] = filled($validated['brand'] ?? null)
            ? trim((string) $validated['brand'])
            : null;

        $validated['model'] = filled($validated['model'] ?? null)
            ? trim((string) $validated['model'])
            : null;

        $validated['serial_number'] = filled($validated['serial_number'] ?? null)
            ? trim((string) $validated['serial_number'])
            : null;

        $validated['description'] = filled($validated['description'] ?? null)
            ? trim((string) $validated['description'])
            : null;

        return $validated;
    }

    private function generateUniqueSlug(?string $slug, string $name, ?int $ignoreId = null): string
    {
        $baseSlug = Str::slug($slug ?: $name);
        $slug = $baseSlug;
        $counter = 1;

        while (
            Equipment::query()
                ->when($ignoreId, fn ($query) => $query->where('id', '!=', $ignoreId))
                ->where('slug', $slug)
                ->exists()
        ) {
            $slug = $baseSlug . '-' . $counter++;
        }

        return $slug;
    }

    private function generateUniqueCode(): string
    {
        $latest = Equipment::query()
            ->where('code', 'like', 'EQ-%')
            ->latest('id')
            ->value('code');

        $nextNumber = 1;

        if ($latest && preg_match('/EQ-(\d+)/', $latest, $matches)) {
            $nextNumber = ((int) $matches[1]) + 1;
        }

        do {
            $code = 'EQ-' . str_pad((string) $nextNumber, 4, '0', STR_PAD_LEFT);
            $exists = Equipment::query()->where('code', $code)->exists();
            $nextNumber++;
        } while ($exists);

        return $code;
    }
}