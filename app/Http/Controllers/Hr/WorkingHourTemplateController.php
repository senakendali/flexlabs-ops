<?php

namespace App\Http\Controllers\Hr;

use App\Http\Controllers\Controller;
use App\Models\WorkingHourTemplate;
use Carbon\CarbonImmutable;
use Illuminate\Contracts\View\View;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;

class WorkingHourTemplateController extends Controller
{
    /**
     * Menampilkan master working-hours template.
     */
    public function index(Request $request): View
    {
        $filters = [
            'search' => trim($request->string('search')->toString()),
            'is_active' => trim($request->string('is_active')->toString()),
            'source' => trim($request->string('source')->toString()),
        ];

        $query = WorkingHourTemplate::query()
            ->withCount([
                'employees',
                'importRows',
                'attendances',
            ])
            ->orderByDesc('is_active')
            ->orderBy('name');

        if ($filters['search'] !== '') {
            $search = $filters['search'];

            $query->where(function ($templateQuery) use ($search): void {
                $templateQuery
                    ->where('code', 'like', '%' . $search . '%')
                    ->orWhere('name', 'like', '%' . $search . '%')
                    ->orWhere('source', 'like', '%' . $search . '%');
            });
        }

        if (in_array($filters['is_active'], ['0', '1'], true)) {
            $query->where(
                'is_active',
                $filters['is_active'] === '1'
            );
        }

        if ($filters['source'] !== '') {
            $query->where(
                'source',
                $filters['source']
            );
        }

        $workingHourTemplates = $query
            ->paginate(20)
            ->withQueryString();

        $summary = [
            'total' => WorkingHourTemplate::query()->count(),
            'active' => WorkingHourTemplate::query()
                ->where('is_active', true)
                ->count(),
            'inactive' => WorkingHourTemplate::query()
                ->where('is_active', false)
                ->count(),
            'incomplete' => WorkingHourTemplate::query()
                ->where(function ($incompleteQuery): void {
                    $incompleteQuery
                        ->whereNull('start_time')
                        ->orWhereNull('end_time');
                })
                ->count(),
        ];

        $sourceOptions = WorkingHourTemplate::query()
            ->whereNotNull('source')
            ->where('source', '!=', '')
            ->distinct()
            ->orderBy('source')
            ->pluck('source');

        return view('hr.working-hour-templates.index', [
            'workingHourTemplates' => $workingHourTemplates,
            'filters' => $filters,
            'summary' => $summary,
            'sourceOptions' => $sourceOptions,
            'workingDayOptions' => $this->workingDayOptions(),
        ]);
    }

    /**
     * Menyimpan working-hours template secara asynchronous.
     */
    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate(
            $this->templateRules()
        );

        $template = DB::transaction(
            function () use ($validated): WorkingHourTemplate {
                return WorkingHourTemplate::query()->create(
                    $this->preparePayload(
                        data: $validated,
                        existing: null
                    )
                );
            }
        );

        $template->loadCount([
            'employees',
            'importRows',
            'attendances',
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Working-hours template berhasil ditambahkan.',
            'data' => [
                'working_hour_template' => $template,
            ],
        ], 201);
    }

    /**
     * Memperbarui working-hours template secara asynchronous.
     */
    public function update(
        Request $request,
        WorkingHourTemplate $workingHourTemplate
    ): JsonResponse {
        $validated = $request->validate(
            $this->templateRules($workingHourTemplate)
        );

        DB::transaction(function () use (
            $workingHourTemplate,
            $validated
        ): void {
            $workingHourTemplate->update(
                $this->preparePayload(
                    data: $validated,
                    existing: $workingHourTemplate
                )
            );
        });

        $workingHourTemplate
            ->refresh()
            ->loadCount([
                'employees',
                'importRows',
                'attendances',
            ]);

        return response()->json([
            'success' => true,
            'message' => 'Working-hours template berhasil diperbarui.',
            'data' => [
                'working_hour_template' => $workingHourTemplate,
            ],
        ]);
    }

    /**
     * Menghapus template yang belum digunakan secara asynchronous.
     */
    public function destroy(
        WorkingHourTemplate $workingHourTemplate
    ): JsonResponse {
        $usage = [
            'employees' => $workingHourTemplate
                ->employees()
                ->count(),

            'import_rows' => $workingHourTemplate
                ->importRows()
                ->count(),

            'attendances' => $workingHourTemplate
                ->attendances()
                ->count(),
        ];

        if (array_sum($usage) > 0) {
            return response()->json([
                'success' => false,
                'message' => 'Template sudah digunakan dan tidak dapat dihapus. Nonaktifkan template agar tidak dipakai untuk data baru.',
                'errors' => [
                    'working_hour_template' => [
                        'Template masih terhubung dengan employee atau data attendance.',
                    ],
                ],
                'data' => [
                    'usage' => $usage,
                ],
            ], 422);
        }

        DB::transaction(function () use (
            $workingHourTemplate
        ): void {
            $workingHourTemplate->delete();
        });

        return response()->json([
            'success' => true,
            'message' => 'Working-hours template berhasil dihapus.',
            'data' => [
                'id' => $workingHourTemplate->id,
            ],
        ]);
    }

    /**
     * Validation rules working-hours template.
     */
    protected function templateRules(
        ?WorkingHourTemplate $template = null
    ): array {
        return [
            'code' => [
                'nullable',
                'string',
                'max:100',
                Rule::unique(
                    'working_hour_templates',
                    'code'
                )->ignore($template?->id),
            ],

            'name' => [
                'required',
                'string',
                'max:255',
                Rule::unique(
                    'working_hour_templates',
                    'name'
                )->ignore($template?->id),
            ],

            'start_time' => [
                'required',
                'date_format:H:i',
            ],

            'end_time' => [
                'required',
                'date_format:H:i',
            ],

            'break_start_time' => [
                'nullable',
                'date_format:H:i',
                'required_with:break_end_time',
            ],

            'break_end_time' => [
                'nullable',
                'date_format:H:i',
                'required_with:break_start_time',
            ],

            'first_half_end_time' => [
                'nullable',
                'date_format:H:i',
            ],

            'second_half_start_time' => [
                'nullable',
                'date_format:H:i',
            ],

            'working_days' => [
                'required',
                'array',
                'min:1',
            ],

            'working_days.*' => [
                'integer',
                'distinct',
                'between:1,7',
            ],

            'late_tolerance_minutes' => [
                'nullable',
                'integer',
                'min:0',
                'max:240',
            ],

            'is_active' => [
                'nullable',
                'boolean',
            ],

            'source' => [
                'nullable',
                'string',
                'max:100',
            ],

            'metadata' => [
                'nullable',
                'array',
            ],
        ];
    }

    /**
     * Menyiapkan payload template serta menghitung scheduled work minutes.
     */
    protected function preparePayload(
        array $data,
        ?WorkingHourTemplate $existing
    ): array {
        $nullableFields = [
            'code',
            'break_start_time',
            'break_end_time',
            'first_half_end_time',
            'second_half_start_time',
            'source',
        ];

        foreach ($nullableFields as $field) {
            if (
                array_key_exists($field, $data)
                && $data[$field] === ''
            ) {
                $data[$field] = null;
            }
        }

        $data['working_days'] = collect(
            $data['working_days'] ?? []
        )
            ->map(fn ($day) => (int) $day)
            ->unique()
            ->sort()
            ->values()
            ->all();

        $data['late_tolerance_minutes'] = (int) (
            $data['late_tolerance_minutes']
            ?? $existing?->late_tolerance_minutes
            ?? 0
        );

        if (! array_key_exists('source', $data)) {
            $data['source'] = $existing?->source ?: 'manual';
        }

        if (! array_key_exists('is_active', $data)) {
            $data['is_active'] = $existing?->is_active ?? true;
        }

        if (! array_key_exists('metadata', $data)) {
            $data['metadata'] = $existing?->metadata;
        }

        $data['scheduled_work_minutes'] =
            $this->calculateScheduledWorkMinutes(
                startTime: $data['start_time'],
                endTime: $data['end_time'],
                breakStartTime: $data['break_start_time'] ?? null,
                breakEndTime: $data['break_end_time'] ?? null,
            );

        return $data;
    }

    /**
     * Menghitung total menit kerja, termasuk shift yang melewati tengah malam.
     */
    protected function calculateScheduledWorkMinutes(
        string $startTime,
        string $endTime,
        ?string $breakStartTime,
        ?string $breakEndTime
    ): int {
        $start = $this->timeOnBaseDate($startTime);
        $end = $this->timeOnBaseDate($endTime);

        if ($end->lessThanOrEqualTo($start)) {
            $end = $end->addDay();
        }

        $scheduledMinutes = $start->diffInMinutes($end);

        if (
            filled($breakStartTime)
            && filled($breakEndTime)
        ) {
            $breakStart = $this->timeOnBaseDate(
                $breakStartTime
            );

            $breakEnd = $this->timeOnBaseDate(
                $breakEndTime
            );

            $isOvernightShift = $end->toDateString()
                !== $start->toDateString();

            if (
                $isOvernightShift
                && $breakStart->lessThan($start)
            ) {
                $breakStart = $breakStart->addDay();
            }

            if (
                $isOvernightShift
                && $breakEnd->lessThan($start)
            ) {
                $breakEnd = $breakEnd->addDay();
            }

            if ($breakEnd->lessThanOrEqualTo($breakStart)) {
                $breakEnd = $breakEnd->addDay();
            }

            $breakMinutes = $breakStart->diffInMinutes(
                $breakEnd
            );

            $scheduledMinutes = max(
                $scheduledMinutes - $breakMinutes,
                0
            );
        }

        return (int) min(
            $scheduledMinutes,
            1440
        );
    }

    protected function timeOnBaseDate(
        string $time
    ): CarbonImmutable {
        return CarbonImmutable::createFromFormat(
            'Y-m-d H:i',
            '2000-01-01 ' . substr($time, 0, 5)
        );
    }

    protected function workingDayOptions(): array
    {
        return [
            1 => 'Monday',
            2 => 'Tuesday',
            3 => 'Wednesday',
            4 => 'Thursday',
            5 => 'Friday',
            6 => 'Saturday',
            7 => 'Sunday',
        ];
    }
}
