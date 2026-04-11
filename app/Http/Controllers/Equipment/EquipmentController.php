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
            'borrowings' => fn ($query) => $query->latest()->with('user'),
            'activeBorrowing.user',
        ]);

        return response()->json([
            'success' => true,
            'data' => $equipment,
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
            'is_active' => ['nullable', 'boolean'],
        ]);

        $validated['slug'] = $this->generateUniqueSlug(
            $validated['slug'] ?? null,
            $validated['name']
        );

        $validated['code'] = $this->generateUniqueCode();
        $validated['is_active'] = (bool) ($validated['is_active'] ?? true);

        $equipment = Equipment::create($validated);

        return response()->json([
            'success' => true,
            'message' => 'Equipment berhasil ditambahkan.',
            'data' => $equipment,
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
            'is_active' => ['nullable', 'boolean'],
        ]);

        $validated['slug'] = $this->generateUniqueSlug(
            $validated['slug'] ?? null,
            $validated['name'],
            $equipment->id
        );

        $validated['is_active'] = (bool) ($validated['is_active'] ?? true);

        $equipment->update($validated);

        return response()->json([
            'success' => true,
            'message' => 'Equipment berhasil diperbarui.',
            'data' => $equipment->fresh(),
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