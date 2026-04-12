<?php

namespace App\Http\Controllers\Equipment;

use App\Http\Controllers\Controller;
use App\Models\Equipment;
use App\Models\EquipmentBorrowing;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class EquipmentBorrowingController extends Controller
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

        return view('borrowings.index', compact('equipments'));
    }

    public function show(EquipmentBorrowing $borrowing): JsonResponse
    {
        $borrowing->load([
            'equipment',
            'user',
        ]);

        return response()->json([
            'success' => true,
            'data' => $borrowing,
        ]);
    }

    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'equipment_id' => ['required', 'exists:equipments,id'],
            'borrowed_at' => ['nullable', 'date'],
            'expected_return_at' => ['nullable', 'date', 'after_or_equal:borrowed_at'],
            'notes' => ['nullable', 'string'],
        ]);

        $user = $request->user();

        $borrowing = DB::transaction(function () use ($validated, $user) {
            $equipment = Equipment::query()
                ->lockForUpdate()
                ->findOrFail($validated['equipment_id']);

            if (!$equipment->is_active) {
                return response()->json([
                    'success' => false,
                    'message' => 'Equipment is inactive and cannot be borrowed.',
                ], 422)->throwResponse();
            }

            if ($equipment->status !== 'available') {
                return response()->json([
                    'success' => false,
                    'message' => 'Equipment is not available for borrowing.',
                ], 422)->throwResponse();
            }

            $hasActiveBorrowing = EquipmentBorrowing::query()
                ->where('equipment_id', $equipment->id)
                ->where('status', 'borrowed')
                ->exists();

            if ($hasActiveBorrowing) {
                return response()->json([
                    'success' => false,
                    'message' => 'Equipment is currently borrowed by another user.',
                ], 422)->throwResponse();
            }

            $borrowing = EquipmentBorrowing::create([
                'equipment_id' => $equipment->id,
                'user_id' => $user->id,
                'status' => 'borrowed',
                'borrowed_at' => $validated['borrowed_at'] ?? now(),
                'expected_return_at' => $validated['expected_return_at'] ?? null,
                'notes' => $validated['notes'] ?? null,
            ]);

            $equipment->update([
                'status' => 'borrowed',
            ]);

            return $borrowing;
        });

        $borrowing->load(['equipment', 'user']);

        return response()->json([
            'success' => true,
            'message' => 'Equipment borrowed successfully.',
            'data' => $borrowing,
        ]);
    }

    public function returnEquipment(Request $request, EquipmentBorrowing $borrowing): JsonResponse
    {
        $validated = $request->validate([
            'returned_at' => ['nullable', 'date'],
            'return_notes' => ['nullable', 'string'],
        ]);

        $user = $request->user();

        if ((int) $borrowing->user_id !== (int) $user->id) {
            return response()->json([
                'success' => false,
                'message' => 'You are not allowed to return this equipment.',
            ], 403);
        }

        if ($borrowing->status === 'returned') {
            return response()->json([
                'success' => false,
                'message' => 'This equipment has already been returned.',
            ], 422);
        }

        DB::transaction(function () use ($borrowing, $validated) {
            $borrowing->update([
                'status' => 'returned',
                'returned_at' => $validated['returned_at'] ?? now(),
                'return_notes' => $validated['return_notes'] ?? null,
            ]);

            $borrowing->equipment()->update([
                'status' => 'available',
            ]);
        });

        $borrowing->load(['equipment', 'user']);

        return response()->json([
            'success' => true,
            'message' => 'Equipment returned successfully.',
            'data' => $borrowing,
        ]);
    }
}