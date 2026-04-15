<?php

namespace App\Http\Controllers\Equipment;

use App\Http\Controllers\Controller;
use App\Models\Equipment;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
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
        ]);

        return response()->json([
            'success' => true,
            'data' => [
                'id' => $equipment->id,
                'name' => $equipment->name,
                'code' => $equipment->code,
                'type' => $equipment->type ?? 'borrowable',
                'status' => $equipment->status,
                'condition' => $equipment->condition,
                'location' => $equipment->location,

                // 🔹 Assigned (current)
                'assigned_user' => $equipment->assignedUser ? [
                    'id' => $equipment->assignedUser->id,
                    'name' => $equipment->assignedUser->name,
                    'email' => $equipment->assignedUser->email,
                ] : null,

                // 🔹 Active Borrowing
                'active_borrowing' => $equipment->activeBorrowing ? [
                    'id' => $equipment->activeBorrowing->id,
                    'borrowed_at' => $equipment->activeBorrowing->borrowed_at,
                    'expected_return_at' => $equipment->activeBorrowing->expected_return_at,
                    'user' => $equipment->activeBorrowing->user ? [
                        'id' => $equipment->activeBorrowing->user->id,
                        'name' => $equipment->activeBorrowing->user->name,
                    ] : null,
                ] : null,

                // 🔥 Borrowing History (clean)
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
                        ] : null,
                    ];
                })->values(),
            ],
        ]);
    }

    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'slug' => ['nullable', 'string', 'max:255', 'unique:equipments,slug'],
            'serial_number' => ['nullable', 'string', 'max:255', 'unique:equipments,serial_number'],
            'brand' => ['nullable', 'string', 'max:255'],
            'model' => ['nullable', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'condition' => ['required', Rule::in(['good', 'minor_damage', 'damaged'])],
            'status' => ['required', Rule::in(['available', 'borrowed', 'maintenance'])],

            // new asset fields
            'type' => ['nullable', Rule::in(['assigned', 'borrowable'])],
            'assigned_user_id' => ['nullable', 'exists:users,id'],
            'location' => ['nullable', 'string', 'max:255'],
            'purchase_date' => ['nullable', 'date'],
            'purchase_price' => ['nullable', 'numeric', 'min:0'],
            'last_maintenance_at' => ['nullable', 'date'],

            'is_active' => ['nullable', 'boolean'],
        ]);

        $validated['slug'] = $this->generateUniqueSlug(
            $validated['slug'] ?? null,
            $validated['name']
        );

        $validated['code'] = $this->generateUniqueCode();
        $validated['is_active'] = (bool) ($validated['is_active'] ?? true);
        $validated['type'] = $validated['type'] ?? 'borrowable';

        $validated = $this->normalizeAssetPayload($validated);

        $equipment = Equipment::create($validated);

        return response()->json([
            'success' => true,
            'message' => 'Equipment berhasil ditambahkan.',
            'data' => $equipment->load(['assignedUser', 'activeBorrowing.user']),
        ]);
    }

    public function update(Request $request, Equipment $equipment): JsonResponse
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'slug' => [
                'nullable',
                'string',
                'max:255',
                Rule::unique('equipments', 'slug')->ignore($equipment->id),
            ],
            'serial_number' => [
                'nullable',
                'string',
                'max:255',
                Rule::unique('equipments', 'serial_number')->ignore($equipment->id),
            ],
            'brand' => ['nullable', 'string', 'max:255'],
            'model' => ['nullable', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'condition' => ['required', Rule::in(['good', 'minor_damage', 'damaged'])],
            'status' => ['required', Rule::in(['available', 'borrowed', 'maintenance'])],

            // new asset fields
            'type' => ['nullable', Rule::in(['assigned', 'borrowable'])],
            'assigned_user_id' => ['nullable', 'exists:users,id'],
            'location' => ['nullable', 'string', 'max:255'],
            'purchase_date' => ['nullable', 'date'],
            'purchase_price' => ['nullable', 'numeric', 'min:0'],
            'last_maintenance_at' => ['nullable', 'date'],

            'is_active' => ['nullable', 'boolean'],
        ]);

        $validated['slug'] = $this->generateUniqueSlug(
            $validated['slug'] ?? null,
            $validated['name'],
            $equipment->id
        );

        $validated['is_active'] = (bool) ($validated['is_active'] ?? true);
        $validated['type'] = $validated['type'] ?? ($equipment->type ?? 'borrowable');

        $validated = $this->normalizeAssetPayload($validated, $equipment);

        $equipment->update($validated);

        return response()->json([
            'success' => true,
            'message' => 'Equipment berhasil diperbarui.',
            'data' => $equipment->fresh()->load(['assignedUser', 'activeBorrowing.user']),
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

        $equipment->delete();

        return response()->json([
            'success' => true,
            'message' => 'Equipment berhasil dihapus.',
        ]);
    }

    private function normalizeAssetPayload(array $validated, ?Equipment $equipment = null): array
    {
        $type = $validated['type'] ?? 'borrowable';

        if ($type === 'assigned') {
            // assigned asset tidak boleh pakai status borrowed
            if (($validated['status'] ?? null) === 'borrowed') {
                $validated['status'] = 'available';
            }
        }

        if ($type === 'borrowable') {
            // borrowable asset tidak punya assigned user
            $validated['assigned_user_id'] = null;
        }

        // optional normalization
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