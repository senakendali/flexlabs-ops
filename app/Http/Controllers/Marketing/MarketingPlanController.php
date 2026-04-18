<?php

namespace App\Http\Controllers\Marketing;

use App\Http\Controllers\Controller;
use App\Models\MarketingPlan;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class MarketingPlanController extends Controller
{
    public function index(Request $request): View
    {
        $search = trim((string) $request->input('search'));
        $status = $request->input('status');
        $periodType = $request->input('period_type');
        $perPage = (int) $request->input('per_page', 10);

        $allowedPerPage = [10, 25, 50, 100];
        $perPage = in_array($perPage, $allowedPerPage, true) ? $perPage : 10;

        $plans = MarketingPlan::query()
            ->when($search !== '', function ($query) use ($search) {
                $query->where(function ($q) use ($search) {
                    $q->where('title', 'like', '%' . $search . '%')
                        ->orWhere('objective', 'like', '%' . $search . '%')
                        ->orWhere('strategy', 'like', '%' . $search . '%')
                        ->orWhere('notes', 'like', '%' . $search . '%');
                });
            })
            ->when($status, fn ($query) => $query->where('status', $status))
            ->when($periodType, fn ($query) => $query->where('period_type', $periodType))
            ->latest()
            ->paginate($perPage)
            ->withQueryString();

        return view('marketing.plans.index', compact(
            'plans',
            'search',
            'status',
            'periodType',
            'perPage'
        ));
    }

    public function store(Request $request): JsonResponse|RedirectResponse
    {
        $validated = $request->validate($this->rules());

        $payload = $this->preparePayload($request, $validated, true);

        MarketingPlan::create($payload);

        if ($request->expectsJson()) {
            return response()->json([
                'success' => true,
                'message' => 'Marketing plan berhasil ditambahkan.',
            ]);
        }

        return redirect()
            ->route('marketing.plans.index')
            ->with('success', 'Marketing plan berhasil ditambahkan.');
    }

    public function update(Request $request, MarketingPlan $marketingPlan): JsonResponse|RedirectResponse
    {
        $validated = $request->validate($this->rules($marketingPlan));

        $payload = $this->preparePayload($request, $validated, false, $marketingPlan);

        $marketingPlan->update($payload);

        if ($request->expectsJson()) {
            return response()->json([
                'success' => true,
                'message' => 'Marketing plan berhasil diperbarui.',
            ]);
        }

        return redirect()
            ->route('marketing.plans.index')
            ->with('success', 'Marketing plan berhasil diperbarui.');
    }

    public function destroy(Request $request, MarketingPlan $marketingPlan): JsonResponse|RedirectResponse
    {
        $marketingPlan->delete();

        if ($request->expectsJson()) {
            return response()->json([
                'success' => true,
                'message' => 'Marketing plan berhasil dihapus.',
            ]);
        }

        return redirect()
            ->route('marketing.plans.index')
            ->with('success', 'Marketing plan berhasil dihapus.');
    }

    /**
     * Validation rules for create/update.
     */
    protected function rules(?MarketingPlan $marketingPlan = null): array
    {
        return [
            'title' => ['required', 'string', 'max:255'],
            'period_type' => [
                'required',
                'string',
                Rule::in(['weekly', 'monthly', 'quarterly', 'yearly']),
            ],
            'start_date' => ['nullable', 'date'],
            'end_date' => ['nullable', 'date', 'after_or_equal:start_date'],
            'objective' => ['nullable', 'string'],
            'strategy' => ['nullable', 'string'],
            'notes' => ['nullable', 'string'],
            'budget' => ['nullable', 'numeric', 'min:0'],
            'status' => [
                'required',
                'string',
                Rule::in(['draft', 'active', 'completed', 'cancelled']),
            ],
            'is_active' => ['nullable', 'boolean'],
        ];
    }

    /**
     * Prepare payload before create/update.
     */
    protected function preparePayload(
        Request $request,
        array $validated,
        bool $isCreate = true,
        ?MarketingPlan $marketingPlan = null
    ): array {
        $validated['title'] = trim($validated['title']);
        $validated['objective'] = isset($validated['objective']) ? trim((string) $validated['objective']) : null;
        $validated['strategy'] = isset($validated['strategy']) ? trim((string) $validated['strategy']) : null;
        $validated['notes'] = isset($validated['notes']) ? trim((string) $validated['notes']) : null;

        $validated['budget'] = isset($validated['budget']) && $validated['budget'] !== ''
            ? (float) $validated['budget']
            : 0;

        if ($isCreate) {
            $validated['slug'] = $this->generateUniqueSlug($validated['title']);
            $validated['created_by'] = auth()->id();
            $validated['is_active'] = $request->boolean('is_active', true);
        } else {
            $titleChanged = $marketingPlan && $marketingPlan->title !== $validated['title'];

            if ($titleChanged) {
                $validated['slug'] = $this->generateUniqueSlug($validated['title'], $marketingPlan?->id);
            }

            $validated['is_active'] = $request->boolean('is_active', false);
        }

        $validated['updated_by'] = auth()->id();

        return $validated;
    }

    /**
     * Generate unique slug for marketing plan.
     */
    protected function generateUniqueSlug(string $title, ?int $ignoreId = null): string
    {
        $baseSlug = Str::slug($title);
        $slug = $baseSlug;
        $counter = 1;

        while (
            MarketingPlan::query()
                ->when($ignoreId, fn ($query) => $query->where('id', '!=', $ignoreId))
                ->where('slug', $slug)
                ->exists()
        ) {
            $slug = $baseSlug . '-' . $counter;
            $counter++;
        }

        return $slug;
    }
}