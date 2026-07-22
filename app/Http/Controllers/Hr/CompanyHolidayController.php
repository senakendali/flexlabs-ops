<?php

namespace App\Http\Controllers\Hr;

use App\Http\Controllers\Controller;
use App\Models\CompanyHoliday;
use Illuminate\Contracts\View\View;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;

class CompanyHolidayController extends Controller
{
    /**
     * Menampilkan master company holiday.
     */
    public function index(Request $request): View
    {
        $filters = [
            'search' => trim($request->string('search')->toString()),
            'holiday_type' => trim(
                $request->string('holiday_type')->toString()
            ),
            'is_active' => trim(
                $request->string('is_active')->toString()
            ),
            'date_from' => trim(
                $request->string('date_from')->toString()
            ),
            'date_to' => trim(
                $request->string('date_to')->toString()
            ),
        ];

        $query = CompanyHoliday::query()
            ->orderByDesc('holiday_date')
            ->orderBy('name');

        if ($filters['search'] !== '') {
            $search = $filters['search'];

            $query->where(function ($holidayQuery) use ($search): void {
                $holidayQuery
                    ->where('name', 'like', '%' . $search . '%')
                    ->orWhere('holiday_type', 'like', '%' . $search . '%')
                    ->orWhere('notes', 'like', '%' . $search . '%');
            });
        }

        if ($filters['holiday_type'] !== '') {
            $query->where(
                'holiday_type',
                $filters['holiday_type']
            );
        }

        if (in_array($filters['is_active'], ['0', '1'], true)) {
            $query->where(
                'is_active',
                $filters['is_active'] === '1'
            );
        }

        if ($filters['date_from'] !== '') {
            $query->whereDate(
                'holiday_date',
                '>=',
                $filters['date_from']
            );
        }

        if ($filters['date_to'] !== '') {
            $query->whereDate(
                'holiday_date',
                '<=',
                $filters['date_to']
            );
        }

        $companyHolidays = $query
            ->paginate(20)
            ->withQueryString();

        $summary = [
            'total' => CompanyHoliday::query()->count(),
            'active' => CompanyHoliday::query()
                ->where('is_active', true)
                ->count(),
            'this_year' => CompanyHoliday::query()
                ->whereYear('holiday_date', now()->year)
                ->count(),
            'upcoming' => CompanyHoliday::query()
                ->where('is_active', true)
                ->whereDate('holiday_date', '>=', today())
                ->count(),
        ];

        $holidayTypeOptions = CompanyHoliday::query()
            ->whereNotNull('holiday_type')
            ->where('holiday_type', '!=', '')
            ->distinct()
            ->orderBy('holiday_type')
            ->pluck('holiday_type');

        return view('hr.company-holidays.index', [
            'companyHolidays' => $companyHolidays,
            'filters' => $filters,
            'summary' => $summary,
            'holidayTypeOptions' => $holidayTypeOptions,
        ]);
    }

    /**
     * Menyimpan company holiday secara asynchronous.
     */
    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate(
            $this->holidayRules($request)
        );

        $companyHoliday = DB::transaction(
            function () use ($validated): CompanyHoliday {
                return CompanyHoliday::query()->create(
                    $this->preparePayload(
                        data: $validated,
                        existing: null
                    )
                );
            }
        );

        return response()->json([
            'success' => true,
            'message' => 'Company holiday berhasil ditambahkan.',
            'data' => [
                'company_holiday' => $companyHoliday,
            ],
        ], 201);
    }

    /**
     * Memperbarui company holiday secara asynchronous.
     */
    public function update(
        Request $request,
        CompanyHoliday $companyHoliday
    ): JsonResponse {
        $validated = $request->validate(
            $this->holidayRules(
                request: $request,
                companyHoliday: $companyHoliday
            )
        );

        DB::transaction(function () use (
            $companyHoliday,
            $validated
        ): void {
            $companyHoliday->update(
                $this->preparePayload(
                    data: $validated,
                    existing: $companyHoliday
                )
            );
        });

        $companyHoliday->refresh();

        return response()->json([
            'success' => true,
            'message' => 'Company holiday berhasil diperbarui.',
            'data' => [
                'company_holiday' => $companyHoliday,
            ],
        ]);
    }

    /**
     * Menghapus company holiday secara asynchronous.
     */
    public function destroy(
        CompanyHoliday $companyHoliday
    ): JsonResponse {
        DB::transaction(function () use (
            $companyHoliday
        ): void {
            $companyHoliday->delete();
        });

        return response()->json([
            'success' => true,
            'message' => 'Company holiday berhasil dihapus.',
            'data' => [
                'id' => $companyHoliday->id,
            ],
        ]);
    }

    /**
     * Validation rules company holiday.
     */
    protected function holidayRules(
        Request $request,
        ?CompanyHoliday $companyHoliday = null
    ): array {
        return [
            'holiday_date' => [
                'required',
                'date',
                Rule::unique(
                    'company_holidays',
                    'holiday_date'
                )
                    ->where(
                        fn ($query) => $query->where(
                            'name',
                            trim((string) $request->input('name'))
                        )
                    )
                    ->ignore($companyHoliday?->id),
            ],

            'name' => [
                'required',
                'string',
                'max:255',
            ],

            'holiday_type' => [
                'nullable',
                'string',
                'max:100',
            ],

            'is_active' => [
                'nullable',
                'boolean',
            ],

            'notes' => [
                'nullable',
                'string',
                'max:2000',
            ],

            'metadata' => [
                'nullable',
                'array',
            ],
        ];
    }

    /**
     * Menyiapkan payload company holiday.
     */
    protected function preparePayload(
        array $data,
        ?CompanyHoliday $existing
    ): array {
        foreach ([
            'holiday_type',
            'notes',
        ] as $field) {
            if (
                array_key_exists($field, $data)
                && $data[$field] === ''
            ) {
                $data[$field] = null;
            }
        }

        if (! array_key_exists('is_active', $data)) {
            $data['is_active'] = $existing?->is_active ?? true;
        }

        if (! array_key_exists('metadata', $data)) {
            $data['metadata'] = $existing?->metadata;
        }

        return $data;
    }
}
