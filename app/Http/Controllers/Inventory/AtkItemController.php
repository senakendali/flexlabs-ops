<?php

namespace App\Http\Controllers\Inventory;

use App\Http\Controllers\Controller;
use App\Models\AtkItem;
use App\Models\AtkStock;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\View\View;

class AtkItemController extends Controller
{
    public function index(Request $request): View
    {
        $search = $request->string('search')->toString();

        $items = AtkItem::query()
            ->with('stock')
            ->when($search, function ($query) use ($search) {
                $query->where(function ($sub) use ($search) {
                    $sub->where('name', 'like', "%{$search}%")
                        ->orWhere('code', 'like', "%{$search}%")
                        ->orWhere('unit', 'like', "%{$search}%");
                });
            })
            ->latest()
            ->paginate(10)
            ->withQueryString();

        $stats = [
            'total_items' => AtkItem::count(),
            'active_items' => AtkItem::where('is_active', true)->count(),
            'low_stock' => AtkItem::query()
                ->whereHas('stock', function ($q) {
                    $q->whereColumn('current_stock', '<=', 'atk_items.minimum_stock');
                })
                ->count(),
            'total_stock' => AtkStock::sum('current_stock'),
        ];

        return view('inventory.atk-items.index', compact('items', 'stats', 'search'));
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'code' => ['nullable', 'string', 'max:255', 'unique:atk_items,code'],
            'unit' => ['nullable', 'string', 'max:100'],
            'description' => ['nullable', 'string'],
            'minimum_stock' => ['required', 'integer', 'min:0'],
            'current_stock' => ['required', 'integer', 'min:0'],
            'location' => ['nullable', 'string', 'max:255'],
            'is_active' => ['nullable', 'boolean'],
        ]);

        DB::transaction(function () use ($validated) {
            $item = AtkItem::create([
                'name' => $validated['name'],
                'slug' => Str::slug($validated['name']),
                'code' => $validated['code'] ?? null,
                'unit' => $validated['unit'] ?? null,
                'description' => $validated['description'] ?? null,
                'minimum_stock' => $validated['minimum_stock'],
                'is_active' => (bool) ($validated['is_active'] ?? true),
            ]);

            AtkStock::create([
                'atk_item_id' => $item->id,
                'current_stock' => $validated['current_stock'],
                'reserved_stock' => 0,
                'location' => $validated['location'] ?? null,
                'notes' => 'Initial stock',
            ]);
        });

        return redirect()
            ->route('inventory.atk-items.index')
            ->with('success', 'Barang ATK berhasil ditambahkan.');
    }

    public function update(Request $request, AtkItem $atkItem): RedirectResponse
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'code' => ['nullable', 'string', 'max:255', 'unique:atk_items,code,' . $atkItem->id],
            'unit' => ['nullable', 'string', 'max:100'],
            'description' => ['nullable', 'string'],
            'minimum_stock' => ['required', 'integer', 'min:0'],
            'current_stock' => ['required', 'integer', 'min:0'],
            'location' => ['nullable', 'string', 'max:255'],
            'is_active' => ['nullable', 'boolean'],
        ]);

        DB::transaction(function () use ($validated, $atkItem) {
            $atkItem->update([
                'name' => $validated['name'],
                'slug' => Str::slug($validated['name']),
                'code' => $validated['code'] ?? null,
                'unit' => $validated['unit'] ?? null,
                'description' => $validated['description'] ?? null,
                'minimum_stock' => $validated['minimum_stock'],
                'is_active' => (bool) ($validated['is_active'] ?? false),
            ]);

            $atkItem->stock()->updateOrCreate(
                ['atk_item_id' => $atkItem->id],
                [
                    'current_stock' => $validated['current_stock'],
                    'reserved_stock' => $atkItem->stock?->reserved_stock ?? 0,
                    'location' => $validated['location'] ?? null,
                ]
            );
        });

        return redirect()
            ->route('inventory.atk-items.index')
            ->with('success', 'Barang ATK berhasil diperbarui.');
    }

    public function destroy(AtkItem $atkItem): RedirectResponse
    {
        if ($atkItem->requestItems()->exists()) {
            return redirect()
                ->route('inventory.atk-items.index')
                ->with('error', 'Barang tidak bisa dihapus karena sudah pernah digunakan dalam request.');
        }

        $atkItem->delete();

        return redirect()
            ->route('inventory.atk-items.index')
            ->with('success', 'Barang ATK berhasil dihapus.');
    }
}