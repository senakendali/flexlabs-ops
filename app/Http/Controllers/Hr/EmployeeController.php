<?php

namespace App\Http\Controllers\Hr;

use App\Http\Controllers\Controller;
use App\Models\Employee;
use App\Models\WorkingHourTemplate;
use Illuminate\Contracts\View\View;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;

class EmployeeController extends Controller
{
    /**
     * Menampilkan master employee.
     */
    public function index(Request $request): View
    {
        $filters = [
            'search' => trim($request->string('search')->toString()),
            'is_active' => trim($request->string('is_active')->toString()),
            'employee_type' => trim(
                $request->string('employee_type')->toString()
            ),
            'work_team' => trim(
                $request->string('work_team')->toString()
            ),
            'default_working_hour_template_id' => $request->integer(
                'default_working_hour_template_id'
            ) ?: null,
        ];

        $query = Employee::query()
            ->with([
                'defaultWorkingHourTemplate:id,code,name,start_time,end_time',
            ])
            ->latest('id');

        if ($filters['search'] !== '') {
            $search = $filters['search'];

            $query->where(function ($employeeQuery) use ($search): void {
                $employeeQuery
                    ->where('employee_number', 'like', '%' . $search . '%')
                    ->orWhere('name', 'like', '%' . $search . '%')
                    ->orWhere('email', 'like', '%' . $search . '%')
                    ->orWhere('phone', 'like', '%' . $search . '%')
                    ->orWhere('employee_type', 'like', '%' . $search . '%')
                    ->orWhere('work_team', 'like', '%' . $search . '%')
                    ->orWhere('duty_type', 'like', '%' . $search . '%');
            });
        }

        if (in_array($filters['is_active'], ['0', '1'], true)) {
            $query->where(
                'is_active',
                $filters['is_active'] === '1'
            );
        }

        if ($filters['employee_type'] !== '') {
            $query->where(
                'employee_type',
                $filters['employee_type']
            );
        }

        if ($filters['work_team'] !== '') {
            $query->where(
                'work_team',
                $filters['work_team']
            );
        }

        if ($filters['default_working_hour_template_id']) {
            $query->where(
                'default_working_hour_template_id',
                $filters['default_working_hour_template_id']
            );
        }

        $employees = $query
            ->paginate(20)
            ->withQueryString();

        $summary = [
            'total' => Employee::query()->count(),
            'active' => Employee::query()
                ->where('is_active', true)
                ->count(),
            'inactive' => Employee::query()
                ->where('is_active', false)
                ->count(),
            'without_template' => Employee::query()
                ->whereNull('default_working_hour_template_id')
                ->count(),
        ];

        $workingHourTemplates = WorkingHourTemplate::query()
            ->orderByDesc('is_active')
            ->orderBy('name')
            ->get([
                'id',
                'code',
                'name',
                'start_time',
                'end_time',
                'is_active',
            ]);

        $employeeTypeOptions = Employee::query()
            ->whereNotNull('employee_type')
            ->where('employee_type', '!=', '')
            ->distinct()
            ->orderBy('employee_type')
            ->pluck('employee_type');

        $workTeamOptions = Employee::query()
            ->whereNotNull('work_team')
            ->where('work_team', '!=', '')
            ->distinct()
            ->orderBy('work_team')
            ->pluck('work_team');

        $dutyTypeOptions = Employee::query()
            ->whereNotNull('duty_type')
            ->where('duty_type', '!=', '')
            ->distinct()
            ->orderBy('duty_type')
            ->pluck('duty_type');

        return view('hr.employees.index', [
            'employees' => $employees,
            'filters' => $filters,
            'summary' => $summary,
            'workingHourTemplates' => $workingHourTemplates,
            'employeeTypeOptions' => $employeeTypeOptions,
            'workTeamOptions' => $workTeamOptions,
            'dutyTypeOptions' => $dutyTypeOptions,
            'workingDayOptions' => $this->workingDayOptions(),
        ]);
    }

    /**
     * Menyimpan employee baru secara asynchronous.
     */
    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate(
            $this->employeeRules()
        );

        $employee = DB::transaction(function () use ($validated): Employee {
            return Employee::query()->create(
                $this->preparePayload(
                    data: $validated,
                    existing: null
                )
            );
        });

        $employee->load([
            'defaultWorkingHourTemplate:id,code,name,start_time,end_time',
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Employee berhasil ditambahkan.',
            'data' => [
                'employee' => $employee,
            ],
        ], 201);
    }

    /**
     * Memperbarui employee secara asynchronous.
     */
    public function update(
        Request $request,
        Employee $employee
    ): JsonResponse {
        $validated = $request->validate(
            $this->employeeRules($employee)
        );

        DB::transaction(function () use (
            $employee,
            $validated
        ): void {
            $employee->update(
                $this->preparePayload(
                    data: $validated,
                    existing: $employee
                )
            );
        });

        $employee->refresh()->load([
            'defaultWorkingHourTemplate:id,code,name,start_time,end_time',
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Employee berhasil diperbarui.',
            'data' => [
                'employee' => $employee,
            ],
        ]);
    }

    /**
     * Menghapus employee secara soft delete dan asynchronous.
     */
    public function destroy(Employee $employee): JsonResponse
    {
        DB::transaction(function () use ($employee): void {
            $employee->delete();
        });

        return response()->json([
            'success' => true,
            'message' => 'Employee berhasil dihapus.',
            'data' => [
                'id' => $employee->id,
            ],
        ]);
    }

    /**
     * Validation rules employee.
     */
    protected function employeeRules(
        ?Employee $employee = null
    ): array {
        return [
            'employee_number' => [
                'required',
                'string',
                'max:100',
                Rule::unique(
                    'employees',
                    'employee_number'
                )->ignore($employee?->id),
            ],

            'name' => [
                'required',
                'string',
                'max:255',
            ],

            'email' => [
                'nullable',
                'email:rfc',
                'max:255',
                Rule::unique(
                    'employees',
                    'email'
                )->ignore($employee?->id),
            ],

            'phone' => [
                'nullable',
                'string',
                'max:100',
            ],

            'employee_type' => [
                'nullable',
                'string',
                'max:100',
            ],

            'work_team' => [
                'nullable',
                'string',
                'max:150',
            ],

            'duty_type' => [
                'nullable',
                'string',
                'max:150',
            ],

            'default_working_hour_template_id' => [
                'nullable',
                'integer',
                'exists:working_hour_templates,id',
            ],

            'default_start_time' => [
                'nullable',
                'date_format:H:i',
            ],

            'default_end_time' => [
                'nullable',
                'date_format:H:i',
            ],

            'working_days_override' => [
                'nullable',
                'array',
                'min:1',
            ],

            'working_days_override.*' => [
                'integer',
                'distinct',
                'between:1,7',
            ],

            'source' => [
                'nullable',
                'string',
                'max:100',
            ],

            'is_active' => [
                'nullable',
                'boolean',
            ],

            'metadata' => [
                'nullable',
                'array',
            ],
        ];
    }

    /**
     * Menyiapkan payload employee agar konsisten.
     */
    protected function preparePayload(
        array $data,
        ?Employee $existing
    ): array {
        $nullableFields = [
            'email',
            'phone',
            'employee_type',
            'work_team',
            'duty_type',
            'default_start_time',
            'default_end_time',
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

        if (array_key_exists('working_days_override', $data)) {
            $workingDays = collect(
                $data['working_days_override'] ?? []
            )
                ->map(fn ($day) => (int) $day)
                ->unique()
                ->sort()
                ->values()
                ->all();

            $data['working_days_override'] = $workingDays ?: null;
        }

        if (! array_key_exists('source', $data)) {
            $data['source'] = $existing?->source ?: 'manual';
        }

        if (! array_key_exists('is_active', $data)) {
            $data['is_active'] = $existing?->is_active ?? true;
        }

        if (! array_key_exists('metadata', $data)) {
            $data['metadata'] = $existing?->metadata;
        }

        return $data;
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
