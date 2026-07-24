<?php

namespace App\Http\Controllers\Hr;

use App\Http\Controllers\Controller;
use App\Models\AttendanceImport;
use App\Models\AttendanceImportRow;
use App\Models\Employee;
use App\Models\WorkingHourTemplate;
use App\Services\Hr\AttendanceImportService;
use Illuminate\Contracts\View\View;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Illuminate\Support\Str;
use Throwable;

class AttendanceImportController extends Controller
{
    /**
     * Menampilkan riwayat upload attendance.
     */
    public function index(Request $request): View
    {
        $filters = [
            'search' => trim($request->string('search')->toString()),
            'status' => trim($request->string('status')->toString()),
            'date_from' => trim($request->string('date_from')->toString()),
            'date_to' => trim($request->string('date_to')->toString()),
        ];

        $query = AttendanceImport::query()
            ->with([
                'uploader:id,name',
                'confirmer:id,name',
            ])
            ->latest('id');

        if ($filters['search'] !== '') {
            $query->where('original_file_name', 'like', '%' . $filters['search'] . '%');
        }

        if ($filters['status'] !== '') {
            $query->where('status', $filters['status']);
        }

        if ($filters['date_from'] !== '') {
            $query->whereDate('created_at', '>=', $filters['date_from']);
        }

        if ($filters['date_to'] !== '') {
            $query->whereDate('created_at', '<=', $filters['date_to']);
        }

        $imports = $query
            ->paginate(12)
            ->withQueryString();

        $summary = [
            'total' => AttendanceImport::query()->count(),
            'reviewing' => AttendanceImport::query()
                ->where('status', AttendanceImport::STATUS_REVIEWING)
                ->count(),
            'completed' => AttendanceImport::query()
                ->where('status', AttendanceImport::STATUS_COMPLETED)
                ->count(),
            'failed' => AttendanceImport::query()
                ->where('status', AttendanceImport::STATUS_FAILED)
                ->count(),
        ];

        return view('hr.attendance-imports.index', [
            'imports' => $imports,
            'filters' => $filters,
            'summary' => $summary,
            'statusOptions' => $this->importStatusOptions(),
        ]);
    }

    /**
     * Menampilkan form upload Excel Evertime.
     */
    public function create(): View
    {
        return view('hr.attendance-imports.create', [
            'workingHourTemplates' => WorkingHourTemplate::query()
                ->active()
                ->orderBy('name')
                ->get(),

            'defaultSettings' => [
                'sheet_name' => 'Attendance',
                'default_working_days' => [1, 2, 3, 4, 5],
                'default_start_time' => '08:00',
                'default_end_time' => '17:00',
                'late_tolerance_minutes' => 0,
                'duplicate_action' => 'update',
                'generate_missing_rows' => true,
                'include_future_dates' => false,
            ],

            'workingDayOptions' => $this->workingDayOptions(),
        ]);
    }

    /**
     * Upload dan parse file attendance ke staging rows.
     */
    public function store(
        Request $request,
        AttendanceImportService $attendanceImportService
    ): JsonResponse|RedirectResponse {
        $validated = $request->validate([
            'file' => [
                'required',
                'file',
                'mimes:xlsx,xls',
                'max:15360',
            ],

            'sheet_name' => [
                'nullable',
                'string',
                'max:100',
            ],

            'default_working_days' => [
                'nullable',
                'array',
                'min:1',
            ],

            'default_working_days.*' => [
                'integer',
                'distinct',
                'between:1,7',
            ],

            'default_start_time' => [
                'nullable',
                'date_format:H:i',
            ],

            'default_end_time' => [
                'nullable',
                'date_format:H:i',
            ],

            'late_tolerance_minutes' => [
                'nullable',
                'integer',
                'min:0',
                'max:240',
            ],

            'duplicate_action' => [
                'nullable',
                Rule::in(['update', 'skip', 'error']),
            ],

            'generate_missing_rows' => [
                'nullable',
                'boolean',
            ],

            'include_future_dates' => [
                'nullable',
                'boolean',
            ],
        ]);

        $settings = [
            'sheet_name' => $validated['sheet_name'] ?? 'Attendance',
            'default_working_days' => $validated['default_working_days']
                ?? [1, 2, 3, 4, 5],
            'default_start_time' => $validated['default_start_time'] ?? '08:00',
            'default_end_time' => $validated['default_end_time'] ?? '17:00',
            'late_tolerance_minutes' => (int) (
                $validated['late_tolerance_minutes'] ?? 0
            ),
            'duplicate_action' => $validated['duplicate_action'] ?? 'update',
            'generate_missing_rows' => array_key_exists('generate_missing_rows', $validated)
                ? (bool) $validated['generate_missing_rows']
                : true,
            'include_future_dates' => array_key_exists('include_future_dates', $validated)
                ? (bool) $validated['include_future_dates']
                : false,

            /*
            |--------------------------------------------------------------------------
            | Template Defaults
            |--------------------------------------------------------------------------
            | Bisa dipindahkan ke config/hr.php kalau konfigurasi shift sudah tetap.
            | Template baru yang belum dikenal tetap dibuat dan dapat dilengkapi HR.
            */
            'template_defaults' => config(
                'hr.attendance.template_defaults',
                []
            ),
        ];

        try {
            $attendanceImport = $attendanceImportService->upload(
                file: $request->file('file'),
                settings: $settings,
                userId: auth()->id(),
            );
        } catch (ValidationException $exception) {
            throw $exception;
        } catch (Throwable $exception) {
            Log::error('Attendance import upload failed.', [
                'user_id' => auth()->id(),
                'file_name' => $request->file('file')?->getClientOriginalName(),
                'message' => $exception->getMessage(),
            ]);

            if ($request->expectsJson()) {
                return response()->json([
                    'success' => false,
                    'message' => 'File attendance belum berhasil diproses.',
                    'error' => app()->hasDebugModeEnabled()
                        ? $exception->getMessage()
                        : null,
                ], 500);
            }

            return back()
                ->withInput()
                ->with(
                    'error',
                    'File attendance belum berhasil diproses. Silakan periksa format file atau coba kembali.'
                );
        }

        if ($request->expectsJson()) {
            return response()->json([
                'success' => true,
                'message' => 'File attendance berhasil diproses dan siap direview.',
                'data' => [
                    'id' => $attendanceImport->id,
                    'status' => $attendanceImport->status,
                    'review_url' => route(
                        'hr.attendance-imports.review',
                        $attendanceImport
                    ),
                ],
            ], 201);
        }

        return redirect()
            ->route('hr.attendance-imports.review', $attendanceImport)
            ->with(
                'success',
                'File attendance berhasil diproses. Silakan review tanggal kosong dan data yang membutuhkan penyesuaian.'
            );
    }

    /**
     * Menampilkan shell halaman review attendance.
     *
     * Data tetap disiapkan untuk progressive enhancement dan fallback tanpa
     * JavaScript. Blade berikutnya dapat memakai reviewDataUrl untuk memuat
     * atau mengganti daftar attendance secara asynchronous.
     */
    public function review(
        Request $request,
        AttendanceImport $attendanceImport
    ): View {
        $context = $this->buildReviewContext(
            request: $request,
            attendanceImport: $attendanceImport
        );

        return view(
            'hr.attendance-imports.review',
            array_merge($context, [
                'reviewDataUrl' => $this->reviewDataUrl(
                    $attendanceImport
                ),
                'asyncReviewEnabled' => Route::has(
                    'hr.attendance-imports.review-data'
                ),
            ])
        );
    }

    /**
     * Memuat daftar attendance review secara asynchronous.
     *
     * Endpoint ini mengembalikan HTML hasil render Blade partial, bukan markup
     * yang dibangun ulang di JavaScript. Dengan begitu gaya, badge, formatting,
     * permission, dan payload edit tetap memiliki satu sumber.
     */
    public function reviewData(
        Request $request,
        AttendanceImport $attendanceImport
    ): JsonResponse {
        $context = $this->buildReviewContext(
            request: $request,
            attendanceImport: $attendanceImport
        );

        $requestedGroupKeys = collect(
            $request->input('group_keys', [])
        )
            ->when(
                $request->filled('group_key'),
                fn (Collection $keys) => $keys->push(
                    trim(
                        $request
                            ->string('group_key')
                            ->toString()
                    )
                )
            )
            ->filter(
                fn ($key) => is_string($key)
                    && trim($key) !== ''
            )
            ->map(
                fn ($key) => trim((string) $key)
            )
            ->unique()
            ->values();

        $employeeGroups = $context['employeeGroups'];

        if ($requestedGroupKeys->isNotEmpty()) {
            $employeeGroups = $employeeGroups
                ->whereIn('key', $requestedGroupKeys->all())
                ->values();
        }

        $partialContext = array_merge(
            $context,
            [
                'employeeGroups' => $employeeGroups,
                'highlightRowId' => $request->integer(
                    'highlight_row_id'
                ) ?: null,
            ]
        );

        $html = view(
            'hr.attendance-imports.partials.employee-groups',
            $partialContext
        )->render();

        return response()->json([
            'success' => true,
            'html' => $html,
            'summary' => $this->reviewSummary(
                $context['attendanceImport']
            ),
            'meta' => [
                'employee_count' => $employeeGroups->count(),
                'row_count' => (int) $employeeGroups
                    ->sum('record_count'),
                'all_employee_count' => $context[
                    'employeeGroups'
                ]->count(),
                'all_row_count' => (int) $context[
                    'employeeGroups'
                ]->sum('record_count'),
                'requested_group_keys' => $requestedGroupKeys,
                'filtered' => collect(
                    $context['filters']
                )
                    ->filter(
                        fn ($value) => filled($value)
                    )
                    ->isNotEmpty(),
            ],
            'can_edit' => $context['canEdit'],
            'can_confirm' => $this->canConfirmImport(
                $context['attendanceImport'],
                $context['canEdit']
            ),
            'status' => $context[
                'attendanceImport'
            ]->status,
            'filters' => $context['filters'],
            'refreshed_at' => now()->toIso8601String(),
        ]);
    }

    /**
     * Update satu staging row dari inline review.
     *
     * Request JSON/AJAX mendapat context yang cukup untuk mengganti employee
     * group, summary counter, serta status Confirm Import tanpa reload halaman.
     * Request biasa tetap mendapat redirect sebagai fallback.
     */
    public function updateRow(
        Request $request,
        AttendanceImport $attendanceImport,
        AttendanceImportRow $attendanceImportRow,
        AttendanceImportService $attendanceImportService
    ): JsonResponse|RedirectResponse {
        $this->assertRowBelongsToImport(
            attendanceImport: $attendanceImport,
            row: $attendanceImportRow
        );

        $attendanceImportRow->loadMissing([
            'employee:id,employee_number,name',
        ]);

        $previousGroupKey = $this->reviewGroupKey(
            $attendanceImportRow
        );

        $validated = $request->validate(
            $this->reviewRowRules()
        );

        $row = $attendanceImportService->updateReviewRow(
            row: $attendanceImportRow,
            data: $validated,
            userId: auth()->id(),
        );

        $row->refresh()->load([
            'employee:id,employee_number,name',
            'workingHourTemplate:id,name,start_time,end_time',
            'resolver:id,name',
        ]);

        $attendanceImport->refresh()->load([
            'uploader:id,name',
            'confirmer:id,name',
        ]);

        $currentGroupKey = $this->reviewGroupKey(
            $row
        );

        if ($request->expectsJson()) {
            $rowData = $row->toArray();

            $rowData['group_key'] = $currentGroupKey;
            $rowData['previous_group_key'] = $previousGroupKey;
            $rowData['refresh_group_keys'] = collect([
                $previousGroupKey,
                $currentGroupKey,
            ])
                ->filter()
                ->unique()
                ->values()
                ->all();

            $rowData['summary'] = $this->reviewSummary(
                $attendanceImport
            );

            $rowData['can_edit'] = $this->canEditImport(
                $attendanceImport
            );

            $rowData['can_confirm'] = $this->canConfirmImport(
                $attendanceImport
            );

            $rowData['review_data_url'] =
                $this->reviewDataUrl(
                    $attendanceImport
                );

            return response()->json([
                'success' => true,
                'message' => 'Attendance row berhasil diperbarui.',
                'data' => $rowData,
            ]);
        }

        return back()->with(
            'success',
            'Attendance row berhasil diperbarui.'
        );
    }

    /**
     * Terapkan klasifikasi yang sama ke beberapa staging row.
     *
     * Response asynchronous meminta client me-refresh daftar attendance karena
     * satu bulk action dapat menyentuh beberapa employee group sekaligus.
     */
    public function bulkUpdate(
        Request $request,
        AttendanceImport $attendanceImport,
        AttendanceImportService $attendanceImportService
    ): JsonResponse|RedirectResponse {
        $validated = $request->validate(array_merge(
            [
                'row_ids' => [
                    'required',
                    'array',
                    'min:1',
                ],
                'row_ids.*' => [
                    'integer',
                    'distinct',
                    'exists:attendance_import_rows,id',
                ],
            ],
            $this->reviewRowRules(
                prefix: 'resolution.',
                requiredClassification: true
            )
        ));

        $rowIds = collect($validated['row_ids'])
            ->map(fn ($id) => (int) $id)
            ->values();

        $matchingRows = $attendanceImport->rows()
            ->whereIn('id', $rowIds)
            ->count();

        if ($matchingRows !== $rowIds->count()) {
            throw ValidationException::withMessages([
                'row_ids' => 'Sebagian row tidak termasuk dalam attendance import ini.',
            ]);
        }

        $updated = $attendanceImportService
            ->bulkUpdateReviewRows(
                attendanceImport: $attendanceImport,
                rowIds: $rowIds->all(),
                data: $validated['resolution'],
                userId: auth()->id(),
            );

        $attendanceImport->refresh()->load([
            'uploader:id,name',
            'confirmer:id,name',
        ]);

        if ($request->expectsJson()) {
            return response()->json([
                'success' => true,
                'message' => "{$updated} attendance row berhasil diperbarui.",
                'data' => [
                    'updated_rows' => $updated,
                    'updated_row_ids' => $rowIds->all(),
                    'refresh_all' => true,
                    'summary' => $this->reviewSummary(
                        $attendanceImport
                    ),
                    'can_edit' => $this->canEditImport(
                        $attendanceImport
                    ),
                    'can_confirm' => $this->canConfirmImport(
                        $attendanceImport
                    ),
                    'review_data_url' =>
                        $this->reviewDataUrl(
                            $attendanceImport
                        ),
                ],
            ]);
        }

        return back()->with(
            'success',
            "{$updated} attendance row berhasil diperbarui."
        );
    }

    /**
     * Finalisasi staging rows ke employee_attendances.
     */
    public function confirm(
        Request $request,
        AttendanceImport $attendanceImport,
        AttendanceImportService $attendanceImportService
    ): JsonResponse|RedirectResponse {
        $attendanceImport = $attendanceImportService->confirm(
            attendanceImport: $attendanceImport,
            userId: auth()->id(),
        );

        if ($request->expectsJson()) {
            return response()->json([
                'success' => true,
                'message' => 'Attendance import berhasil dikonfirmasi.',
                'data' => [
                    'id' => $attendanceImport->id,
                    'status' => $attendanceImport->status,
                    'attendance_url' => route('hr.attendances.index'),
                ],
            ]);
        }

        return redirect()
            ->route('hr.attendances.index')
            ->with(
                'success',
                'Attendance import berhasil dikonfirmasi dan data final sudah diperbarui.'
            );
    }

    /**
     * Batalkan import yang belum completed.
     */
    public function cancel(
        Request $request,
        AttendanceImport $attendanceImport,
        AttendanceImportService $attendanceImportService
    ): JsonResponse|RedirectResponse {
        $attendanceImport = $attendanceImportService->cancel(
            attendanceImport: $attendanceImport,
            userId: auth()->id(),
        );

        if ($request->expectsJson()) {
            return response()->json([
                'success' => true,
                'message' => 'Attendance import berhasil dibatalkan.',
                'data' => [
                    'id' => $attendanceImport->id,
                    'status' => $attendanceImport->status,
                ],
            ]);
        }

        return redirect()
            ->route('hr.attendance-imports.index')
            ->with(
                'success',
                'Attendance import berhasil dibatalkan.'
            );
    }

    /**
     * Hapus draft/failed/cancelled import beserta file sumber.
     */
    public function destroy(
        Request $request,
        AttendanceImport $attendanceImport
    ): JsonResponse|RedirectResponse {
        if (in_array($attendanceImport->status, [
            AttendanceImport::STATUS_PROCESSING,
            AttendanceImport::STATUS_COMPLETED,
        ], true)) {
            throw ValidationException::withMessages([
                'attendance_import' => 'Import processing atau completed tidak dapat dihapus.',
            ]);
        }

        $storedFilePath = $attendanceImport->stored_file_path;

        $attendanceImport->delete();

        if (filled($storedFilePath)) {
            Storage::disk('local')->delete($storedFilePath);
        }

        if ($request->expectsJson()) {
            return response()->json([
                'success' => true,
                'message' => 'Attendance import berhasil dihapus.',
            ]);
        }

        return redirect()
            ->route('hr.attendance-imports.index')
            ->with(
                'success',
                'Attendance import berhasil dihapus.'
            );
    }

    /**
     * Menyiapkan seluruh context yang dipakai halaman review dan endpoint data.
     */
    protected function buildReviewContext(
        Request $request,
        AttendanceImport $attendanceImport
    ): array {
        $attendanceImport->load([
            'uploader:id,name',
            'confirmer:id,name',
        ]);

        $filters = $this->reviewFilters($request);

        $filteredRows = $this
            ->reviewRowsQuery(
                attendanceImport: $attendanceImport,
                filters: $filters
            )
            ->orderByRaw(
                'CASE WHEN employee_name IS NULL OR employee_name = ? THEN 1 ELSE 0 END',
                ['']
            )
            ->orderBy('employee_name')
            ->orderBy('attendance_date')
            ->orderBy('id')
            ->get();

        $employeeGroups = $this->groupReviewRows(
            $filteredRows
        );

        $employeeIds = $attendanceImport
            ->rows()
            ->whereNotNull('employee_id')
            ->distinct()
            ->pluck('employee_id');

        $employees = Employee::query()
            ->whereIn('id', $employeeIds)
            ->orderBy('name')
            ->get([
                'id',
                'employee_number',
                'name',
            ]);

        $workingHourTemplates = WorkingHourTemplate::query()
            ->active()
            ->orderBy('name')
            ->get();

        $canEdit = $this->canEditImport(
            $attendanceImport
        );

        return [
            'attendanceImport' => $attendanceImport,
            'employeeGroups' => $employeeGroups,
            'filters' => $filters,
            'employees' => $employees,
            'workingHourTemplates' => $workingHourTemplates,
            'attendanceTypeOptions' =>
                $this->attendanceTypeOptions(),
            'punctualityOptions' =>
                $this->punctualityOptions(),
            'arrivalStatusOptions' =>
                $this->arrivalStatusOptions(),
            'departureStatusOptions' =>
                $this->departureStatusOptions(),
            'leaveTypeOptions' =>
                $this->leaveTypeOptions(),
            'leaveDurationOptions' =>
                $this->leaveDurationOptions(),
            'leaveSessionOptions' =>
                $this->leaveSessionOptions(),
            'reviewStatusOptions' =>
                $this->reviewStatusOptions(),
            'sourceOptions' =>
                $this->sourceOptions(),
            'canEdit' => $canEdit,
            'canConfirm' => $this->canConfirmImport(
                $attendanceImport,
                $canEdit
            ),
        ];
    }

    /**
     * Mengambil filter review dari request dengan format yang konsisten.
     */
    protected function reviewFilters(
        Request $request
    ): array {
        return [
            'search' => trim(
                $request->string('search')->toString()
            ),
            'attendance_type' => trim(
                $request
                    ->string('attendance_type')
                    ->toString()
            ),
            'review_status' => trim(
                $request
                    ->string('review_status')
                    ->toString()
            ),
            'source' => trim(
                $request->string('source')->toString()
            ),
            'employee_id' => $request->integer(
                'employee_id'
            ) ?: null,
            'date_from' => trim(
                $request->string('date_from')->toString()
            ),
            'date_to' => trim(
                $request->string('date_to')->toString()
            ),
        ];
    }

    /**
     * Menyusun query staging rows sesuai filter review.
     */
    protected function reviewRowsQuery(
        AttendanceImport $attendanceImport,
        array $filters
    ) {
        $query = $attendanceImport
            ->rows()
            ->with([
                'employee:id,employee_number,name',
                'workingHourTemplate:id,name,start_time,end_time',
                'resolver:id,name',
            ]);

        if ($filters['search'] !== '') {
            $search = $filters['search'];

            $query->where(function ($rowQuery) use (
                $search
            ): void {
                $rowQuery
                    ->where(
                        'employee_name',
                        'like',
                        '%' . $search . '%'
                    )
                    ->orWhere(
                        'employee_number',
                        'like',
                        '%' . $search . '%'
                    )
                    ->orWhere(
                        'remarks',
                        'like',
                        '%' . $search . '%'
                    )
                    ->orWhere(
                        'validation_message',
                        'like',
                        '%' . $search . '%'
                    )
                    ->orWhereHas(
                        'employee',
                        function ($employeeQuery) use (
                            $search
                        ): void {
                            $employeeQuery
                                ->where(
                                    'name',
                                    'like',
                                    '%' . $search . '%'
                                )
                                ->orWhere(
                                    'employee_number',
                                    'like',
                                    '%' . $search . '%'
                                );
                        }
                    );
            });
        }

        if ($filters['attendance_type'] !== '') {
            $query->where(
                'attendance_type',
                $filters['attendance_type']
            );
        }

        if ($filters['review_status'] !== '') {
            $query->where(
                'review_status',
                $filters['review_status']
            );
        }

        if ($filters['source'] !== '') {
            $query->where(
                'source',
                $filters['source']
            );
        }

        if ($filters['employee_id']) {
            $query->where(
                'employee_id',
                $filters['employee_id']
            );
        }

        if ($filters['date_from'] !== '') {
            $query->whereDate(
                'attendance_date',
                '>=',
                $filters['date_from']
            );
        }

        if ($filters['date_to'] !== '') {
            $query->whereDate(
                'attendance_date',
                '<=',
                $filters['date_to']
            );
        }

        return $query;
    }

    /**
     * Mengelompokkan staging rows berdasarkan employee yang sudah matched atau
     * normalized raw name untuk employee yang belum matched.
     */
    protected function groupReviewRows(
        Collection $filteredRows
    ): Collection {
        return $filteredRows
            ->groupBy(
                fn (AttendanceImportRow $row): string =>
                    $this->reviewGroupKey($row)
            )
            ->map(function (
                Collection $group,
                string $groupKey
            ): array {
                /** @var AttendanceImportRow|null $firstRow */
                $firstRow = $group->first();

                $employee = $firstRow?->employee;

                $employeeName = $employee?->name
                    ?? $firstRow?->employee_name
                    ?? $firstRow?->employee_name_raw
                    ?? 'Unknown Employee';

                $employeeNumber =
                    $employee?->employee_number
                    ?? $firstRow?->employee_number;

                $sortedRows = $group
                    ->sort(function (
                        AttendanceImportRow $left,
                        AttendanceImportRow $right
                    ): int {
                        $leftDate =
                            $left->attendance_date?->format(
                                'Y-m-d'
                            )
                            ?? '9999-12-31';

                        $rightDate =
                            $right->attendance_date?->format(
                                'Y-m-d'
                            )
                            ?? '9999-12-31';

                        $dateComparison =
                            $leftDate <=> $rightDate;

                        if ($dateComparison !== 0) {
                            return $dateComparison;
                        }

                        return $left->id <=> $right->id;
                    })
                    ->values();

                $dates = $sortedRows
                    ->pluck('attendance_date')
                    ->filter()
                    ->sortBy(
                        fn ($date) =>
                            $date->format('Y-m-d')
                    )
                    ->values();

                return [
                    'key' => $groupKey,
                    'employee' => $employee,
                    'employee_id' => $employee?->id,
                    'employee_name' => $employeeName,
                    'employee_number' => $employeeNumber,
                    'is_unmatched' => ! $employee,
                    'record_count' => $sortedRows->count(),
                    'valid_count' => $sortedRows
                        ->where(
                            'review_status',
                            AttendanceImportRow::REVIEW_VALID
                        )
                        ->count(),
                    'needs_review_count' => $sortedRows
                        ->where(
                            'review_status',
                            AttendanceImportRow::REVIEW_NEEDS_REVIEW
                        )
                        ->count(),
                    'resolved_count' => $sortedRows
                        ->where(
                            'review_status',
                            AttendanceImportRow::REVIEW_RESOLVED
                        )
                        ->count(),
                    'ignored_count' => $sortedRows
                        ->where(
                            'review_status',
                            AttendanceImportRow::REVIEW_IGNORED
                        )
                        ->count(),
                    'error_count' => $sortedRows
                        ->where(
                            'review_status',
                            AttendanceImportRow::REVIEW_ERROR
                        )
                        ->count(),
                    'duplicate_count' => $sortedRows
                        ->where(
                            'review_status',
                            AttendanceImportRow::REVIEW_DUPLICATE
                        )
                        ->count(),
                    'date_from' => $dates->first(),
                    'date_to' => $dates->last(),
                    'rows' => $sortedRows,
                ];
            })
            ->sortBy(
                fn (array $group) => Str::lower(
                    $group['employee_name']
                )
            )
            ->values();
    }

    /**
     * Identifier DOM/API stabil untuk employee group.
     */
    protected function reviewGroupKey(
        AttendanceImportRow $row
    ): string {
        if ($row->employee_id) {
            return 'employee-' . $row->employee_id;
        }

        $employeeName = $row->employee?->name
            ?? $row->employee_name
            ?? $row->employee_name_raw
            ?? 'unknown-employee';

        $normalizedName = Str::of($employeeName)
            ->lower()
            ->squish()
            ->toString();

        return 'unmatched-' . md5(
            $normalizedName
        );
    }

    /**
     * Ringkasan global import untuk stat cards dan tombol Confirm Import.
     */
    protected function reviewSummary(
        AttendanceImport $attendanceImport
    ): array {
        return [
            'total_rows' => (int)
                $attendanceImport->total_rows,
            'imported_rows' => (int)
                $attendanceImport->imported_rows,
            'generated_rows' => (int)
                $attendanceImport->generated_rows,
            'review_rows' => (int)
                $attendanceImport->review_rows,
            'error_rows' => (int)
                $attendanceImport->error_rows,
            'duplicate_rows' => (int)
                $attendanceImport->duplicate_rows,
            'status' => $attendanceImport->status,
        ];
    }

    protected function canEditImport(
        AttendanceImport $attendanceImport
    ): bool {
        return in_array(
            $attendanceImport->status,
            [
                AttendanceImport::STATUS_UPLOADED,
                AttendanceImport::STATUS_REVIEWING,
                AttendanceImport::STATUS_FAILED,
            ],
            true
        );
    }

    protected function canConfirmImport(
        AttendanceImport $attendanceImport,
        ?bool $canEdit = null
    ): bool {
        $canEdit ??= $this->canEditImport(
            $attendanceImport
        );

        return $canEdit
            && (int) $attendanceImport->review_rows === 0
            && (int) $attendanceImport->error_rows === 0
            && (int) $attendanceImport->duplicate_rows === 0;
    }

    protected function reviewDataUrl(
        AttendanceImport $attendanceImport
    ): ?string {
        if (! Route::has(
            'hr.attendance-imports.review-data'
        )) {
            return null;
        }

        return route(
            'hr.attendance-imports.review-data',
            $attendanceImport
        );
    }

    /**
     * Pastikan row yang diedit memang bagian dari import pada URL.
     */
    protected function assertRowBelongsToImport(
        AttendanceImport $attendanceImport,
        AttendanceImportRow $row
    ): void {
        abort_unless(
            (int) $row->attendance_import_id === (int) $attendanceImport->id,
            404
        );
    }

    /**
     * Validation rules untuk single dan bulk review.
     */
    protected function reviewRowRules(
        string $prefix = '',
        bool $requiredClassification = false
    ): array {
        $sometimes = $requiredClassification
            ? ['required']
            : ['sometimes'];

        return [
            $prefix . 'employee_id' => [
                'sometimes',
                'nullable',
                'integer',
                'exists:employees,id',
            ],

            $prefix . 'working_hour_template_id' => [
                'sometimes',
                'nullable',
                'integer',
                'exists:working_hour_templates,id',
            ],

            $prefix . 'attendance_date' => [
                'sometimes',
                'nullable',
                'date',
            ],

            $prefix . 'clock_in' => [
                'sometimes',
                'nullable',
                'regex:/^\d{2}:\d{2}(:\d{2})?$/',
            ],

            $prefix . 'clock_out' => [
                'sometimes',
                'nullable',
                'regex:/^\d{2}:\d{2}(:\d{2})?$/',
            ],

            $prefix . 'scheduled_start_time' => [
                'sometimes',
                'nullable',
                'regex:/^\d{2}:\d{2}(:\d{2})?$/',
            ],

            $prefix . 'scheduled_end_time' => [
                'sometimes',
                'nullable',
                'regex:/^\d{2}:\d{2}(:\d{2})?$/',
            ],

            $prefix . 'attendance_type' => array_merge(
                $sometimes,
                [
                    Rule::in(array_keys(
                        $this->attendanceTypeOptions()
                    )),
                ]
            ),

            $prefix . 'punctuality_status' => [
                'sometimes',
                'nullable',
                Rule::in(array_keys(
                    $this->punctualityOptions()
                )),
            ],

            $prefix . 'arrival_status' => [
                'sometimes',
                'nullable',
                Rule::in(array_keys(
                    $this->arrivalStatusOptions()
                )),
            ],

            $prefix . 'departure_status' => [
                'sometimes',
                'nullable',
                Rule::in(array_keys(
                    $this->departureStatusOptions()
                )),
            ],

            $prefix . 'late_minutes' => [
                'sometimes',
                'nullable',
                'integer',
                'min:0',
                'max:1440',
            ],

            $prefix . 'early_leave_minutes' => [
                'sometimes',
                'nullable',
                'integer',
                'min:0',
                'max:1440',
            ],

            $prefix . 'leave_type' => [
                'sometimes',
                'nullable',
                Rule::in(array_keys(
                    $this->leaveTypeOptions()
                )),
            ],

            $prefix . 'leave_duration' => [
                'sometimes',
                'nullable',
                Rule::in(array_keys(
                    $this->leaveDurationOptions()
                )),
            ],

            $prefix . 'leave_session' => [
                'sometimes',
                'nullable',
                Rule::in(array_keys(
                    $this->leaveSessionOptions()
                )),
            ],

            $prefix . 'leave_start_time' => [
                'sometimes',
                'nullable',
                'regex:/^\d{2}:\d{2}(:\d{2})?$/',
            ],

            $prefix . 'leave_end_time' => [
                'sometimes',
                'nullable',
                'regex:/^\d{2}:\d{2}(:\d{2})?$/',
            ],

            $prefix . 'leave_minutes' => [
                'sometimes',
                'nullable',
                'integer',
                'min:0',
                'max:1440',
            ],

            $prefix . 'is_excused' => [
                'sometimes',
                'boolean',
            ],

            $prefix . 'leave_reason' => [
                'sometimes',
                'nullable',
                'string',
                'max:2000',
            ],

            $prefix . 'remarks' => [
                'sometimes',
                'nullable',
                'string',
                'max:5000',
            ],

            $prefix . 'review_status' => [
                'sometimes',
                'nullable',
                Rule::in(array_keys(
                    $this->reviewStatusOptions()
                )),
            ],
        ];
    }

    protected function importStatusOptions(): array
    {
        return [
            AttendanceImport::STATUS_UPLOADED => 'Uploaded',
            AttendanceImport::STATUS_REVIEWING => 'Reviewing',
            AttendanceImport::STATUS_PROCESSING => 'Processing',
            AttendanceImport::STATUS_COMPLETED => 'Completed',
            AttendanceImport::STATUS_FAILED => 'Failed',
            AttendanceImport::STATUS_CANCELLED => 'Cancelled',
        ];
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

    protected function attendanceTypeOptions(): array
    {
        return [
            'present' => 'Present',
            'absent' => 'Absent',
            'missing' => 'Missing Attendance',
            'off_day' => 'Off Day',
            'holiday' => 'Holiday',
            'unknown' => 'Unknown',
        ];
    }

    protected function punctualityOptions(): array
    {
        return [
            'on_time' => 'On Time',
            'late' => 'Late',
            'excused_late' => 'Excused Late',
            'early_departure' => 'Early Departure',
            'excused_early_departure' => 'Excused Early Departure',
            'unknown' => 'Unknown',
            'not_applicable' => 'Not Applicable',
        ];
    }

    protected function arrivalStatusOptions(): array
    {
        return [
            'on_time' => 'On Time',
            'late' => 'Late',
            'excused_late' => 'Excused Late',
            'unknown' => 'Unknown',
            'not_applicable' => 'Not Applicable',
        ];
    }

    protected function departureStatusOptions(): array
    {
        return [
            'on_time' => 'On Time',
            'early_departure' => 'Early Departure',
            'excused_early_departure' => 'Excused Early Departure',
            'unknown' => 'Unknown',
            'not_applicable' => 'Not Applicable',
        ];
    }

    protected function leaveTypeOptions(): array
    {
        return [
            'sick_leave' => 'Sick Leave',
            'annual_leave' => 'Annual Leave',
            'permission' => 'Permission',
            'unpaid_leave' => 'Unpaid Leave',
            'other' => 'Other',
        ];
    }

    protected function leaveDurationOptions(): array
    {
        return [
            'full_day' => 'Full Day',
            'half_day' => 'Half Day',
            'hourly' => 'Hourly',
        ];
    }

    protected function leaveSessionOptions(): array
    {
        return [
            'first_half' => 'First Half',
            'second_half' => 'Second Half',
            'late_arrival' => 'Late Arrival',
            'early_departure' => 'Early Departure',
        ];
    }

    protected function reviewStatusOptions(): array
    {
        return [
            AttendanceImportRow::REVIEW_VALID => 'Valid',
            AttendanceImportRow::REVIEW_NEEDS_REVIEW => 'Needs Review',
            AttendanceImportRow::REVIEW_RESOLVED => 'Resolved',
            AttendanceImportRow::REVIEW_IGNORED => 'Ignored',
            AttendanceImportRow::REVIEW_ERROR => 'Error',
            AttendanceImportRow::REVIEW_DUPLICATE => 'Duplicate',
        ];
    }

    protected function sourceOptions(): array
    {
        return [
            AttendanceImportRow::SOURCE_EXCEL => 'Imported from Excel',
            AttendanceImportRow::SOURCE_GENERATED_GAP => 'Generated Missing Date',
            AttendanceImportRow::SOURCE_MANUAL => 'Manual',
        ];
    }
}
