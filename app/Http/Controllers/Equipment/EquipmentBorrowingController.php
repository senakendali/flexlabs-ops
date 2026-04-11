<?php

namespace App\Http\Controllers\Equipment;

use App\Http\Controllers\Controller;
use App\Models\Equipment;
use App\Models\EquipmentBorrowing;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;

class EquipmentBorrowingController extends Controller
{
    public function borrow(Request $request, Equipment $equipment): JsonResponse
    {
        if ($equipment->status !== 'available') {
            return response()->json([
                'success' => false,
                'message' => 'Equipment ini tidak tersedia untuk dipinjam.',
            ], 422);
        }

        $validated = $request->validate([
            'user_id' => ['required', 'exists:users,id'],
            'borrowed_at' => ['required', 'date'],
            'due_at' => ['nullable', 'date', 'after_or_equal:borrowed_at'],
            'notes' => ['nullable', 'string'],
        ]);

        DB::transaction(function () use ($validated, $equipment) {
            EquipmentBorrowing::create([
                'equipment_id' => $equipment->id,
                'user_id' => $validated['user_id'],
                'borrowed_at' => $validated['borrowed_at'],
                'due_at' => $validated['due_at'] ?? null,
                'status' => 'borrowed',
                'notes' => $validated['notes'] ?? null,
            ]);

            $equipment->update([
                'status' => 'borrowed',
            ]);
        });

        return response()->json([
            'success' => true,
            'message' => 'Equipment berhasil dipinjamkan.',
        ]);
    }

    public function returnEquipment(Request $request, EquipmentBorrowing $borrowing): JsonResponse
    {
        if ($borrowing->status !== 'borrowed') {
            return response()->json([
                'success' => false,
                'message' => 'Data peminjaman ini sudah tidak aktif.',
            ], 422);
        }

        $validated = $request->validate([
            'returned_at' => ['required', 'date', 'after_or_equal:borrowed_at'],
            'condition' => ['nullable', Rule::in(['good', 'minor_damage', 'damaged'])],
            'notes' => ['nullable', 'string'],
        ]);

        DB::transaction(function () use ($validated, $borrowing) {
            $borrowing->update([
                'returned_at' => $validated['returned_at'],
                'status' => 'returned',
                'notes' => $validated['notes'] ?? $borrowing->notes,
            ]);

            $borrowing->equipment->update([
                'status' => 'available',
                'condition' => $validated['condition'] ?? $borrowing->equipment->condition,
            ]);
        });

        return response()->json([
            'success' => true,
            'message' => 'Equipment berhasil dikembalikan.',
        ]);
    }
}