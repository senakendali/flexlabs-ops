<?php

namespace App\Http\Controllers\Inventory;

use App\Http\Controllers\Controller;
use App\Models\AtkItem;
use App\Models\AtkRequest;
use App\Models\AtkRequestItem;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class AtkRequestController extends Controller
{
    public function index(Request $request): View
    {
        $status = $request->input('status');

        $requests = AtkRequest::query()
            ->with(['requester', 'approver', 'items.item'])
            ->when($status, fn ($q) => $q->where('status', $status))
            ->latest()
            ->paginate(10)
            ->withQueryString();

        $items = AtkItem::query()
            ->with('stock')
            ->where('is_active', true)
            ->orderBy('name')
            ->get();

        $stats = [
            'total_requests' => AtkRequest::count(),
            'pending_requests' => AtkRequest::where('status', AtkRequest::STATUS_PENDING)->count(),
            'approved_requests' => AtkRequest::where('status', AtkRequest::STATUS_APPROVED)->count(),
            'rejected_requests' => AtkRequest::where('status', AtkRequest::STATUS_REJECTED)->count(),
        ];

        return view('inventory.atk-requests.index', compact('requests', 'items', 'stats', 'status'));
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'notes' => ['nullable', 'string'],
            'items' => ['required', 'array', 'min:1'],
            'items.*.atk_item_id' => ['required', 'exists:atk_items,id'],
            'items.*.qty' => ['required', 'integer', 'min:1'],
            'items.*.notes' => ['nullable', 'string'],
        ]);

        DB::transaction(function () use ($validated) {
            $atkRequest = AtkRequest::create([
                'user_id' => Auth::id(),
                'notes' => $validated['notes'] ?? null,
                'status' => AtkRequest::STATUS_PENDING,
            ]);

            foreach ($validated['items'] as $itemData) {
                $item = AtkItem::with('stock')->findOrFail($itemData['atk_item_id']);

                AtkRequestItem::create([
                    'atk_request_id' => $atkRequest->id,
                    'atk_item_id' => $item->id,
                    'qty' => $itemData['qty'],
                    'unit' => $item->unit,
                    'notes' => $itemData['notes'] ?? null,
                ]);
            }
        });

        return redirect()
            ->route('inventory.atk-requests.index')
            ->with('success', 'Request ATK berhasil dibuat.');
    }

    public function approve(AtkRequest $atkRequest): RedirectResponse
    {
        if ($atkRequest->status !== AtkRequest::STATUS_PENDING) {
            return redirect()
                ->route('inventory.atk-requests.index')
                ->with('error', 'Hanya request dengan status pending yang bisa disetujui.');
        }

        DB::transaction(function () use ($atkRequest) {
            $atkRequest->load(['items.item.stock']);

            foreach ($atkRequest->items as $requestItem) {
                $stock = $requestItem->item?->stock;

                if (! $stock) {
                    abort(422, 'Stok untuk barang ' . ($requestItem->item->name ?? '-') . ' belum tersedia.');
                }

                if ($stock->current_stock < $requestItem->qty) {
                    abort(422, 'Stok barang "' . $requestItem->item->name . '" tidak mencukupi.');
                }
            }

            foreach ($atkRequest->items as $requestItem) {
                $stock = $requestItem->item->stock;
                $stock->decrement('current_stock', $requestItem->qty);
            }

            $atkRequest->update([
                'status' => AtkRequest::STATUS_APPROVED,
                'approved_by' => Auth::id(),
                'approved_at' => now(),
            ]);
        });

        return redirect()
            ->route('inventory.atk-requests.index')
            ->with('success', 'Request ATK berhasil disetujui dan stok telah dikurangi.');
    }

    public function reject(Request $request, AtkRequest $atkRequest): RedirectResponse
    {
        if ($atkRequest->status !== AtkRequest::STATUS_PENDING) {
            return redirect()
                ->route('inventory.atk-requests.index')
                ->with('error', 'Hanya request dengan status pending yang bisa ditolak.');
        }

        $validated = $request->validate([
            'rejection_reason' => ['required', 'string'],
        ]);

        $atkRequest->update([
            'status' => AtkRequest::STATUS_REJECTED,
            'approved_by' => Auth::id(),
            'approved_at' => now(),
            'rejection_reason' => $validated['rejection_reason'],
        ]);

        return redirect()
            ->route('inventory.atk-requests.index')
            ->with('success', 'Request ATK berhasil ditolak.');
    }

    public function cancel(AtkRequest $atkRequest): RedirectResponse
    {
        if ((int) $atkRequest->user_id !== (int) Auth::id() && Auth::user()?->role !== 'admin') {
            abort(403);
        }

        if (! in_array($atkRequest->status, [AtkRequest::STATUS_PENDING, AtkRequest::STATUS_DRAFT], true)) {
            return redirect()
                ->route('inventory.atk-requests.index')
                ->with('error', 'Request ini tidak bisa dibatalkan.');
        }

        $atkRequest->update([
            'status' => AtkRequest::STATUS_CANCELLED,
        ]);

        return redirect()
            ->route('inventory.atk-requests.index')
            ->with('success', 'Request ATK berhasil dibatalkan.');
    }
}