<?php

namespace App\Services\Hr;

use App\Models\AttendanceImport;
use App\Models\AttendanceImportRow;
use App\Models\CompanyHoliday;
use App\Models\Employee;
use App\Models\EmployeeAttendance;
use App\Models\WorkingHourTemplate;
use Carbon\Carbon;
use Carbon\CarbonPeriod;
use DateTimeInterface;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Arr;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use PhpOffice\PhpSpreadsheet\Cell\Coordinate;
use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Shared\Date as ExcelDate;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;
use Throwable;

class AttendanceImportService
{
    private const DEFAULT_SHEET_NAME = 'Attendance';

    private const DEFAULT_WORKING_DAYS = [1, 2, 3, 4, 5];

    private const DEFAULT_START_TIME = '08:00:00';

    private const DEFAULT_END_TIME = '17:00:00';

    /**
     * Core business fields used to decide whether two rows for the same
     * employee/date are exact duplicates or conflicting duplicates.
     *
     * Remarks, source row number, and raw payload are deliberately excluded.
     * They are audit details, not attendance outcomes.
     */
    private const DUPLICATE_COMPARISON_FIELDS = [
        'working_hour_template_id',
        'scheduled_start_time',
        'scheduled_end_time',
        'attendance_type',
        'clock_in',
        'clock_out',
        'leave_type',
        'leave_duration',
        'leave_session',
        'leave_start_time',
        'leave_end_time',
        'leave_minutes',
        'is_excused',
    ];

    private const DUPLICATE_CONFLICT_MESSAGE =
        'Conflicting duplicate ditemukan untuk employee dan attendance date yang sama. HR perlu memilih record yang digunakan atau mengabaikan record yang tidak valid.';

    /**
     * Upload, parse, normalize, and stage an Evertime attendance workbook.
     */
    public function upload(
        UploadedFile $file,
        array $settings = [],
        ?int $userId = null
    ): AttendanceImport {
        $settings = $this->normalizeSettings($settings);
        $this->assertSupportedFile($file);

        $storedPath = $this->storeUploadedFile($file);

        $attendanceImport = AttendanceImport::create([
            'original_file_name' => $file->getClientOriginalName(),
            'stored_file_path' => $storedPath,
            'sheet_name' => $settings['sheet_name'],
            'status' => AttendanceImport::STATUS_UPLOADED,
            'uploaded_by' => $userId,
            'settings' => $settings,
        ]);

        try {
            $absolutePath = Storage::disk('local')->path($storedPath);
            $parsed = $this->readAttendanceSheet(
                filePath: $absolutePath,
                requestedSheetName: $settings['sheet_name']
            );

            DB::transaction(function () use (
                $attendanceImport,
                $parsed,
                $settings,
                $userId
            ): void {
                $attendanceImport->update([
                    'sheet_name' => $parsed['sheet_name'],
                    'status' => AttendanceImport::STATUS_PROCESSING,
                ]);

                $normalizedRows = collect($parsed['rows'])
                    ->map(fn (array $row) => $this->normalizeSourceRow($row, $settings))
                    ->filter(fn (array $row) => $this->isMeaningfulSourceRow($row))
                    ->values();

                if ($normalizedRows->isEmpty()) {
                    throw ValidationException::withMessages([
                        'file' => 'Sheet Attendance tidak memiliki data attendance yang dapat dibaca.',
                    ]);
                }

                $dateRange = $this->resolveImportDateRange($normalizedRows);

                if (! $dateRange['date_from'] || ! $dateRange['date_to']) {
                    throw ValidationException::withMessages([
                        'file' => 'Periode attendance tidak dapat dideteksi dari file.',
                    ]);
                }

                $attendanceImport->update([
                    'date_from' => $dateRange['date_from'],
                    'date_to' => $dateRange['date_to'],
                ]);

                /*
                |------------------------------------------------------------------
                | Pass 1: bootstrap employees and working-hour templates.
                |------------------------------------------------------------------
                | Employee dengan nomor valid dibuat lebih dulu. Dengan begitu row
                | seperti Employee No. = Sick Leave tetap dapat dicocokkan lewat nama.
                */
                $context = $this->bootstrapMasterData(
                    rows: $normalizedRows,
                    settings: $settings
                );

                /*
                |------------------------------------------------------------------
                | Company holidays within the detected import period.
                |------------------------------------------------------------------
                |
                | Holiday data is loaded once and reused while staging Excel rows
                | and generating missing calendar rows. This avoids one holiday
                | query for every employee/date combination.
                |
                */
                $companyHolidays = $this->getCompanyHolidaysByDate(
                    dateFrom: Carbon::parse($dateRange['date_from'])->startOfDay(),
                    dateTo: Carbon::parse($dateRange['date_to'])->startOfDay()
                );

                $context['company_holidays'] = $companyHolidays;

                /*
                |------------------------------------------------------------------
                | Pass 2: create staging rows.
                |------------------------------------------------------------------
                */
                foreach ($normalizedRows as $normalizedRow) {
                    $this->stageNormalizedRow(
                        attendanceImport: $attendanceImport,
                        row: $normalizedRow,
                        context: $context,
                        settings: $settings
                    );
                }

                $this->assignEmployeeDefaultTemplates(
                    employees: $context['employees'],
                    templateUsage: $context['template_usage']
                );

                if (
                    (bool) $settings['generate_missing_rows']
                    || (bool) $settings['generate_holiday_rows']
                ) {
                    $this->generateMissingAttendanceRows(
                        attendanceImport: $attendanceImport,
                        employees: $context['employees']->values(),
                        settings: $settings,
                        companyHolidays: $companyHolidays
                    );
                }

                $this->refreshImportStatistics($attendanceImport);

                $attendanceImport->update([
                    'status' => AttendanceImport::STATUS_REVIEWING,
                    'imported_at' => now(),
                    'failure_message' => null,
                    'summary' => $this->buildImportSummary($attendanceImport),
                ]);
            });
        } catch (Throwable $exception) {
            $attendanceImport->update([
                'status' => AttendanceImport::STATUS_FAILED,
                'failure_message' => $exception->getMessage(),
            ]);

            throw $exception;
        }

        return $attendanceImport->fresh([
            'rows.employee',
            'rows.workingHourTemplate',
            'uploader',
        ]);
    }

    /**
     * Update one staging row after HR review.
     */
    public function updateReviewRow(
        AttendanceImportRow $row,
        array $data,
        ?int $userId = null
    ): AttendanceImportRow {
        $attendanceImport = $row->attendanceImport;

        $this->assertImportIsEditable($attendanceImport);

        $originalEmployeeId = $row->employee_id;
        $originalAttendanceDate = $this->normalizeDuplicateDate(
            $row->attendance_date
        );

        $payload = Arr::only($data, [
            'employee_id',
            'working_hour_template_id',
            'attendance_date',
            'clock_in',
            'clock_out',
            'scheduled_start_time',
            'scheduled_end_time',
            'attendance_type',
            'punctuality_status',
            'arrival_status',
            'departure_status',
            'late_minutes',
            'early_leave_minutes',
            'leave_type',
            'leave_duration',
            'leave_session',
            'leave_start_time',
            'leave_end_time',
            'leave_minutes',
            'is_excused',
            'leave_reason',
            'remarks',
            'review_status',
        ]);

        $payload = $this->normalizeManualReviewPayload($row, $payload);

        $row->fill($payload);
        $row->resolved_by = $userId;
        $row->resolved_at = now();
        $row->resolution_metadata = array_merge(
            is_array($row->resolution_metadata) ? $row->resolution_metadata : [],
            [
                'updated_by' => $userId,
                'updated_at' => now()->toIso8601String(),
                'changes' => $payload,
            ]
        );
        $row->save();
        $row->refresh();

        $currentEmployeeId = $row->employee_id;
        $currentAttendanceDate = $this->normalizeDuplicateDate(
            $row->attendance_date
        );

        /*
        |--------------------------------------------------------------------------
        | Reconcile both duplicate groups when employee/date changes.
        |--------------------------------------------------------------------------
        |
        | The old group may stop being conflicting, while the new group may
        | become exact or conflicting. Running reconciliation on both keeps
        | duplicate counters and Confirm Import state accurate.
        |
        */
        $this->reconcileStagingDuplicateGroupByIdentity(
            attendanceImport: $attendanceImport,
            employeeId: $originalEmployeeId,
            attendanceDate: $originalAttendanceDate
        );

        if (
            $currentEmployeeId !== $originalEmployeeId
            || $currentAttendanceDate !== $originalAttendanceDate
        ) {
            $this->reconcileStagingDuplicateGroupByIdentity(
                attendanceImport: $attendanceImport,
                employeeId: $currentEmployeeId,
                attendanceDate: $currentAttendanceDate
            );
        }

        $this->refreshImportStatistics($attendanceImport);

        return $row->fresh([
            'employee',
            'workingHourTemplate',
            'resolver',
        ]);
    }

    /**
     * Apply the same HR resolution to several staging rows.
     */
    public function bulkUpdateReviewRows(
        AttendanceImport $attendanceImport,
        array $rowIds,
        array $data,
        ?int $userId = null
    ): int {
        $this->assertImportIsEditable($attendanceImport);

        $rowIds = collect($rowIds)
            ->map(fn ($id) => (int) $id)
            ->filter(fn (int $id) => $id > 0)
            ->unique()
            ->values();

        if ($rowIds->isEmpty()) {
            return 0;
        }

        $updated = 0;

        DB::transaction(function () use (
            $attendanceImport,
            $rowIds,
            $data,
            $userId,
            &$updated
        ): void {
            $attendanceImport->rows()
                ->whereIn('id', $rowIds)
                ->orderBy('id')
                ->chunkById(100, function ($rows) use ($data, $userId, &$updated): void {
                    foreach ($rows as $row) {
                        $this->updateReviewRow(
                            row: $row,
                            data: $data,
                            userId: $userId
                        );

                        $updated++;
                    }
                });
        });

        $this->refreshImportStatistics($attendanceImport);

        return $updated;
    }

    /**
     * Confirm reviewed staging rows into employee_attendances.
     */
    public function confirm(
        AttendanceImport $attendanceImport,
        ?int $userId = null
    ): AttendanceImport {
        if ($attendanceImport->status === AttendanceImport::STATUS_COMPLETED) {
            return $attendanceImport->fresh();
        }

        $this->assertImportIsEditable($attendanceImport);
        $this->assertImportCanBeConfirmed($attendanceImport);

        $settings = $this->normalizeSettings(
            is_array($attendanceImport->settings)
                ? $attendanceImport->settings
                : []
        );

        DB::transaction(function () use (
            $attendanceImport,
            $settings,
            $userId
        ): void {
            $attendanceImport->update([
                'status' => AttendanceImport::STATUS_PROCESSING,
            ]);

            $attendanceImport->rows()
                ->whereNotIn('review_status', [
                    AttendanceImportRow::REVIEW_IGNORED,
                    AttendanceImportRow::REVIEW_ERROR,
                    AttendanceImportRow::REVIEW_DUPLICATE,
                ])
                ->orderBy('id')
                ->chunkById(100, function ($rows) use ($attendanceImport, $settings, $userId): void {
                    foreach ($rows as $row) {
                        $this->persistFinalAttendance(
                            attendanceImport: $attendanceImport,
                            row: $row,
                            duplicateAction: $settings['duplicate_action'],
                            userId: $userId
                        );
                    }
                });

            $attendanceImport->update([
                'status' => AttendanceImport::STATUS_COMPLETED,
                'confirmed_by' => $userId,
                'confirmed_at' => now(),
                'failure_message' => null,
            ]);
        });

        return $attendanceImport->fresh([
            'confirmer',
            'attendances.employee',
        ]);
    }

    /**
     * Cancel an import that has not been completed.
     */
    public function cancel(
        AttendanceImport $attendanceImport,
        ?int $userId = null
    ): AttendanceImport {
        if ($attendanceImport->status === AttendanceImport::STATUS_COMPLETED) {
            throw ValidationException::withMessages([
                'attendance_import' => 'Import yang sudah completed tidak dapat dibatalkan.',
            ]);
        }

        $attendanceImport->update([
            'status' => AttendanceImport::STATUS_CANCELLED,
            'confirmed_by' => $userId,
            'confirmed_at' => now(),
        ]);

        return $attendanceImport->fresh();
    }

    /**
     * Parse the Attendance sheet without trusting duplicated heading labels.
     */
    protected function readAttendanceSheet(
        string $filePath,
        string $requestedSheetName
    ): array {
        $reader = IOFactory::createReaderForFile($filePath);
        $reader->setReadDataOnly(true);
        $reader->setReadEmptyCells(false);

        $spreadsheet = $reader->load($filePath);

        $worksheet = $this->resolveWorksheet(
            worksheetCollection: $spreadsheet->getAllSheets(),
            requestedSheetName: $requestedSheetName
        );

        if (! $worksheet) {
            throw ValidationException::withMessages([
                'file' => "Sheet {$requestedSheetName} tidak ditemukan.",
            ]);
        }

        $headerRow = $this->detectHeaderRow($worksheet);

        if (! $headerRow) {
            throw ValidationException::withMessages([
                'file' => 'Header sheet Attendance tidak dapat dikenali.',
            ]);
        }

        $highestColumnIndex = Coordinate::columnIndexFromString(
            $worksheet->getHighestDataColumn()
        );

        $headers = $this->readHeaders(
            worksheet: $worksheet,
            headerRow: $headerRow,
            highestColumnIndex: $highestColumnIndex
        );

        $rows = [];
        $highestDataRow = $worksheet->getHighestDataRow();

        for ($rowNumber = $headerRow + 1; $rowNumber <= $highestDataRow; $rowNumber++) {
            $row = [
                '_row_number' => $rowNumber,
            ];

            $hasValue = false;

            for ($columnIndex = 1; $columnIndex <= $highestColumnIndex; $columnIndex++) {
                $coordinate = Coordinate::stringFromColumnIndex($columnIndex) . $rowNumber;
                $value = $worksheet->getCell($coordinate)->getValue();

                if ($value !== null && $value !== '') {
                    $hasValue = true;
                }

                $key = $headers[$columnIndex] ?? ('column_' . $columnIndex);
                $row[$key] = $value;
            }

            if (! $hasValue) {
                continue;
            }

            $rows[] = $row;
        }

        $spreadsheet->disconnectWorksheets();
        unset($spreadsheet);

        return [
            'sheet_name' => $worksheet->getTitle(),
            'header_row' => $headerRow,
            'headers' => $headers,
            'rows' => $rows,
        ];
    }

    /**
     * @param array<int, Worksheet> $worksheetCollection
     */
    protected function resolveWorksheet(
        array $worksheetCollection,
        string $requestedSheetName
    ): ?Worksheet {
        foreach ($worksheetCollection as $worksheet) {
            if (strcasecmp($worksheet->getTitle(), $requestedSheetName) === 0) {
                return $worksheet;
            }
        }

        foreach ($worksheetCollection as $worksheet) {
            if (str_contains(
                Str::lower($worksheet->getTitle()),
                Str::lower($requestedSheetName)
            )) {
                return $worksheet;
            }
        }

        return null;
    }

    protected function detectHeaderRow(Worksheet $worksheet): ?int
    {
        $maxRowsToScan = min(20, $worksheet->getHighestDataRow());
        $maxColumnsToScan = min(
            40,
            Coordinate::columnIndexFromString($worksheet->getHighestDataColumn())
        );

        for ($rowNumber = 1; $rowNumber <= $maxRowsToScan; $rowNumber++) {
            $values = [];

            for ($columnIndex = 1; $columnIndex <= $maxColumnsToScan; $columnIndex++) {
                $coordinate = Coordinate::stringFromColumnIndex($columnIndex) . $rowNumber;
                $values[] = $this->normalizeHeader(
                    $worksheet->getCell($coordinate)->getValue()
                );
            }

            if (
                in_array('attendance_date', $values, true)
                && in_array('employee', $values, true)
                && in_array('employee_no', $values, true)
            ) {
                return $rowNumber;
            }
        }

        return null;
    }

    /**
     * @return array<int, string>
     */
    protected function readHeaders(
        Worksheet $worksheet,
        int $headerRow,
        int $highestColumnIndex
    ): array {
        $headers = [];
        $occurrences = [];

        for ($columnIndex = 1; $columnIndex <= $highestColumnIndex; $columnIndex++) {
            $coordinate = Coordinate::stringFromColumnIndex($columnIndex) . $headerRow;
            $baseKey = $this->normalizeHeader(
                $worksheet->getCell($coordinate)->getValue()
            );

            if ($baseKey === '') {
                $baseKey = 'column_' . $columnIndex;
            }

            $occurrences[$baseKey] = ($occurrences[$baseKey] ?? 0) + 1;

            $headers[$columnIndex] = $occurrences[$baseKey] > 1
                ? $baseKey . '_' . $occurrences[$baseKey]
                : $baseKey;
        }

        return $headers;
    }

    protected function normalizeHeader(mixed $value): string
    {
        return Str::of((string) $value)
            ->trim()
            ->lower()
            ->replace('&', ' and ')
            ->replaceMatches('/[^a-z0-9]+/i', '_')
            ->trim('_')
            ->toString();
    }

    /**
     * Convert raw spreadsheet cells into a predictable normalized structure.
     */
    protected function normalizeSourceRow(array $rawRow, array $settings): array
    {
        $attendanceDate = $this->normalizeDate($rawRow['attendance_date'] ?? null);
        $employeeNameRaw = $this->normalizeText($rawRow['employee'] ?? null);
        $employeeNumberRaw = $rawRow['employee_no'] ?? null;
        $employeeNumber = $this->normalizeEmployeeNumber($employeeNumberRaw);

        $clockIn = $this->normalizeTime($rawRow['begin_time'] ?? null);
        $clockOut = $this->normalizeTime($rawRow['end_time'] ?? null);

        $workingTemplateRaw = $this->normalizeText(
            $rawRow['working_hours_template'] ?? null
        );

        $leaveDetection = $this->detectLeaveInformation(
            rawRow: $rawRow,
            employeeNumberRaw: $employeeNumberRaw,
            clockIn: $clockIn,
            clockOut: $clockOut
        );

        $statusRaw = $this->firstUsefulValue([
            $rawRow['status2'] ?? null,
            $rawRow['status'] ?? null,
        ]);

        return [
            'row_number' => (int) ($rawRow['_row_number'] ?? 0),
            'attendance_date' => $attendanceDate,

            'employee_name_raw' => $employeeNameRaw,
            'employee_number_raw' => $this->normalizeText($employeeNumberRaw),
            'employee_name' => $employeeNameRaw,
            'employee_number' => $employeeNumber,
            'normalized_employee_name' => Employee::normalizeName($employeeNameRaw),

            'employee_type' => $this->normalizeText($rawRow['employee_type'] ?? null),
            'employee_subtype' => $this->normalizeText($rawRow['employee_type_2'] ?? null),
            'date_of_joining' => $this->normalizeDate($rawRow['date_of_joining'] ?? null),
            'department' => $this->normalizeText($rawRow['dept'] ?? null),
            'country' => $this->normalizeText($rawRow['country'] ?? null),
            'timezone' => $this->normalizeText($rawRow['time_zone'] ?? null),
            'work_team' => $this->normalizeText($rawRow['work_team'] ?? null),
            'working_hours_template_raw' => $workingTemplateRaw,
            'duty_type' => $this->normalizeText($rawRow['duty_type'] ?? null),

            'clock_in' => $clockIn,
            'clock_out' => $clockOut,
            'status_raw' => $this->normalizeText($statusRaw),

            'attendance_type' => $leaveDetection['attendance_type'],
            'leave_type' => $leaveDetection['leave_type'],
            'leave_duration' => $leaveDetection['leave_duration'],
            'leave_session' => $leaveDetection['leave_session'],
            'is_excused' => $leaveDetection['is_excused'],
            'leave_reason' => $leaveDetection['leave_reason'],
            'leave_was_detected' => $leaveDetection['leave_was_detected'],
            'leave_requires_review' => $leaveDetection['leave_requires_review'],

            'remarks' => $this->normalizeText($rawRow['remarks'] ?? null),
            'raw_payload' => $this->normalizeRawPayload($rawRow),
            'source_row_key' => $this->buildSourceRowKey(
                attendanceDate: $attendanceDate,
                employeeName: $employeeNameRaw,
                employeeNumber: $employeeNumber,
                rowNumber: (int) ($rawRow['_row_number'] ?? 0)
            ),
        ];
    }

    protected function isMeaningfulSourceRow(array $row): bool
    {
        return filled($row['attendance_date'] ?? null)
            || filled($row['employee_name'] ?? null)
            || filled($row['employee_number_raw'] ?? null);
    }

    protected function resolveImportDateRange(Collection $rows): array
    {
        $dates = $rows
            ->pluck('attendance_date')
            ->filter()
            ->sort()
            ->values();

        return [
            'date_from' => $dates->first(),
            'date_to' => $dates->last(),
        ];
    }

    /**
     * Bootstrap master employees/templates before staging leave-only rows.
     */
    protected function bootstrapMasterData(
        Collection $rows,
        array $settings
    ): array {
        $employees = collect();
        $templates = collect();
        $templateUsage = [];

        foreach ($rows as $row) {
            $template = null;

            if (filled($row['working_hours_template_raw'] ?? null)) {
                $template = $this->resolveWorkingHourTemplate(
                    templateName: $row['working_hours_template_raw'],
                    settings: $settings
                );

                $templates->put(
                    Str::lower($template->name),
                    $template
                );
            }

            if (! $this->isValidEmployeeNumber($row['employee_number'] ?? null)) {
                continue;
            }

            $employee = $this->createOrUpdateEmployee(
                row: $row,
                template: $template
            );

            $employees->put((string) $employee->id, $employee);

            if ($template) {
                $templateUsage[$employee->id][$template->id] =
                    ($templateUsage[$employee->id][$template->id] ?? 0) + 1;
            }
        }

        return [
            'employees' => $employees,
            'templates' => $templates,
            'template_usage' => $templateUsage,
        ];
    }

    protected function createOrUpdateEmployee(
        array $row,
        ?WorkingHourTemplate $template
    ): Employee {
        $employeeNumber = (string) $row['employee_number'];
        $employeeName = trim((string) $row['employee_name']);
        $normalizedName = Employee::normalizeName($employeeName);

        $employee = Employee::withTrashed()
            ->where('employee_number', $employeeNumber)
            ->first();

        if (! $employee) {
            $employee = Employee::withTrashed()
                ->where('normalized_name', $normalizedName)
                ->whereNull('employee_number')
                ->first();
        }

        $metadata = is_array($employee?->metadata)
            ? $employee->metadata
            : [];

        $metadata = array_merge($metadata, array_filter([
            'date_of_joining' => $row['date_of_joining'] ?? null,
            'department' => $row['department'] ?? null,
            'country' => $row['country'] ?? null,
            'timezone' => $row['timezone'] ?? null,
            'employee_subtype' => $row['employee_subtype'] ?? null,
        ], fn ($value) => $value !== null && $value !== ''));

        $payload = [
            'employee_number' => $employeeNumber,
            'name' => $employeeName,
            'normalized_name' => $normalizedName,
            'employee_type' => $row['employee_type'] ?? null,
            'work_team' => $row['work_team'] ?? null,
            'duty_type' => $row['duty_type'] ?? null,
            'source' => $employee?->source ?: 'attendance_import',
            'is_active' => true,
            'first_seen_at' => $employee?->first_seen_at ?: now(),
            'last_seen_at' => now(),
            'metadata' => $metadata,
        ];

        if (! $employee) {
            $employee = new Employee();
        }

        if (
            $template
            && ! $employee->default_working_hour_template_id
        ) {
            $payload['default_working_hour_template_id'] = $template->id;
        }

        if ($employee->trashed()) {
            $employee->restore();
        }

        $employee->fill($payload);
        $employee->save();

        return $employee;
    }

    protected function resolveWorkingHourTemplate(
        string $templateName,
        array $settings
    ): WorkingHourTemplate {
        $templateName = trim($templateName);

        $existing = WorkingHourTemplate::query()
            ->whereRaw('LOWER(name) = ?', [Str::lower($templateName)])
            ->first();

        if ($existing) {
            return $existing;
        }

        $templateDefaults = $this->findTemplateDefaults(
            templateName: $templateName,
            settings: $settings
        );

        $code = $this->buildUniqueTemplateCode($templateName);

        return WorkingHourTemplate::create([
            'code' => $code,
            'name' => $templateName,
            'start_time' => $templateDefaults['start_time'] ?? null,
            'end_time' => $templateDefaults['end_time'] ?? null,
            'break_start_time' => $templateDefaults['break_start_time'] ?? null,
            'break_end_time' => $templateDefaults['break_end_time'] ?? null,
            'first_half_end_time' => $templateDefaults['first_half_end_time'] ?? null,
            'second_half_start_time' => $templateDefaults['second_half_start_time'] ?? null,
            'working_days' => $templateDefaults['working_days']
                ?? $settings['default_working_days'],
            'late_tolerance_minutes' => (int) (
                $templateDefaults['late_tolerance_minutes']
                ?? $settings['late_tolerance_minutes']
            ),
            'scheduled_work_minutes' => $this->calculateMinutesBetween(
                $templateDefaults['start_time'] ?? null,
                $templateDefaults['end_time'] ?? null
            ),
            'is_active' => true,
            'source' => 'attendance_import',
            'metadata' => [
                'auto_created' => true,
                'requires_configuration' => empty($templateDefaults['start_time'])
                    || empty($templateDefaults['end_time']),
            ],
        ]);
    }

    protected function findTemplateDefaults(
        string $templateName,
        array $settings
    ): array {
        foreach ($settings['template_defaults'] as $name => $defaults) {
            if (strcasecmp(trim((string) $name), $templateName) === 0) {
                return is_array($defaults) ? $defaults : [];
            }
        }

        if (strcasecmp($templateName, 'Regular working hours') === 0) {
            return [
                'start_time' => $settings['default_start_time'],
                'end_time' => $settings['default_end_time'],
                'working_days' => $settings['default_working_days'],
                'late_tolerance_minutes' => $settings['late_tolerance_minutes'],
            ];
        }

        return [
            'working_days' => $settings['default_working_days'],
            'late_tolerance_minutes' => $settings['late_tolerance_minutes'],
        ];
    }

    protected function buildUniqueTemplateCode(string $templateName): string
    {
        $base = Str::slug($templateName, '_');
        $base = $base !== '' ? $base : 'working_template';
        $code = $base;
        $counter = 2;

        while (WorkingHourTemplate::query()->where('code', $code)->exists()) {
            $code = $base . '_' . $counter;
            $counter++;
        }

        return $code;
    }

    protected function stageNormalizedRow(
        AttendanceImport $attendanceImport,
        array $row,
        array &$context,
        array $settings
    ): AttendanceImportRow {
        $employee = $this->resolveEmployeeForRow(
            row: $row,
            employees: $context['employees']
        );

        if ($employee) {
            $employee->loadMissing('defaultWorkingHourTemplate');

            $context['employees']->put(
                (string) $employee->id,
                $employee
            );
        }

        $template = null;
        $scheduleSource = 'unknown';
        $scheduleIsInferred = false;

        /*
        |--------------------------------------------------------------------------
        | Prefer the template supplied by Excel.
        |--------------------------------------------------------------------------
        */
        if (filled($row['working_hours_template_raw'] ?? null)) {
            $template = $context['templates']->get(
                Str::lower($row['working_hours_template_raw'])
            );

            if (! $template) {
                $template = $this->resolveWorkingHourTemplate(
                    templateName: $row['working_hours_template_raw'],
                    settings: $settings
                );

                $context['templates']->put(
                    Str::lower($template->name),
                    $template
                );
            }

            $scheduleSource = 'excel';
        }

        /*
        |--------------------------------------------------------------------------
        | Fall back to the employee default template.
        |--------------------------------------------------------------------------
        |
        | Some Evertime rows do not contain a template name. When the employee
        | master already has a default template, use it as a schedule snapshot
        | instead of treating the schedule as unknown.
        |
        */
        if (! $template && $employee?->defaultWorkingHourTemplate) {
            $template = $employee->defaultWorkingHourTemplate;
            $scheduleSource = 'employee_default';
            $scheduleIsInferred = true;
        }

        /*
        |--------------------------------------------------------------------------
        | Track only templates explicitly observed from Excel.
        |--------------------------------------------------------------------------
        |
        | A fallback employee template should not be counted as fresh evidence
        | when selecting the employee's most frequently used template.
        |
        */
        if (
            $employee
            && $template
            && $scheduleSource === 'excel'
        ) {
            $context['template_usage'][$employee->id][$template->id] =
                ($context['template_usage'][$employee->id][$template->id] ?? 0) + 1;
        }

        $schedule = $this->resolveScheduleSnapshot(
            template: $template,
            settings: $settings
        );

        /*
        |--------------------------------------------------------------------------
        | Automatically infer a missing clock-out when it is safe to do so.
        |--------------------------------------------------------------------------
        |
        | The inferred time is never hidden. A system marker is stored inside
        | raw_payload._system and the row receives a visible remark.
        |
        */
        $row = $this->applyAutomaticClockOut(
            row: $row,
            employee: $employee,
            template: $template,
            schedule: $schedule,
            companyHolidays: $context['company_holidays'] ?? collect(),
            settings: $settings
        );

        $punctuality = $this->resolvePunctuality(
            clockIn: $row['clock_in'],
            clockOut: $row['clock_out'],
            scheduledStart: $schedule['scheduled_start_time'],
            scheduledEnd: $schedule['scheduled_end_time'],
            toleranceMinutes: (int) (
                $template?->late_tolerance_minutes
                ?? $settings['late_tolerance_minutes']
            ),
            rawStatus: $row['status_raw'] ?? null,
            attendanceType: $row['attendance_type']
        );

        $review = $this->resolveInitialReviewState(
            row: $row,
            employee: $employee,
            template: $template,
            punctuality: $punctuality
        );

        $stagedRow = AttendanceImportRow::create([
            'attendance_import_id' => $attendanceImport->id,
            'employee_id' => $employee?->id,
            'working_hour_template_id' => $template?->id,
            'working_hours_template_raw' => $row['working_hours_template_raw']
                ?: $template?->name,

            'row_number' => $row['row_number'],
            'source_row_key' => $row['source_row_key'],
            'attendance_date' => $row['attendance_date'],

            'employee_number_raw' => $row['employee_number_raw'],
            'employee_name_raw' => $row['employee_name_raw'],
            'employee_number' => $employee?->employee_number
                ?? $row['employee_number'],
            'employee_name' => $employee?->name
                ?? $row['employee_name'],

            'clock_in' => $row['clock_in'],
            'clock_out' => $row['clock_out'],
            'scheduled_start_time' => $schedule['scheduled_start_time'],
            'scheduled_end_time' => $schedule['scheduled_end_time'],
            'scheduled_work_minutes' => $schedule['scheduled_work_minutes'],
            'worked_minutes' => $this->calculateMinutesBetween(
                $row['clock_in'],
                $row['clock_out']
            ),
            'schedule_source' => $scheduleSource,
            'schedule_is_inferred' => $scheduleIsInferred,

            'attendance_type' => $row['attendance_type'],
            'punctuality_status' => $punctuality['punctuality_status'],
            'arrival_status' => $punctuality['arrival_status'],
            'departure_status' => $punctuality['departure_status'],
            'late_minutes' => $punctuality['late_minutes'],
            'early_leave_minutes' => $punctuality['early_leave_minutes'],

            'leave_type' => $row['leave_type'],
            'leave_duration' => $row['leave_duration'],
            'leave_session' => $row['leave_session'],
            'is_excused' => $row['is_excused'],
            'leave_reason' => $row['leave_reason'],

            'source' => AttendanceImportRow::SOURCE_EXCEL,
            'review_status' => $review['review_status'],
            'validation_message' => $review['validation_message'],
            'remarks' => $row['remarks'],
            'raw_payload' => $row['raw_payload'],
        ]);

        /*
        |--------------------------------------------------------------------------
        | Duplicate reconciliation runs after the row exists.
        |--------------------------------------------------------------------------
        |
        | This allows the service to write bidirectional metadata to every row
        | in the same employee/date group and to auto-ignore exact duplicates.
        |
        */
        $this->reconcileStagingDuplicateGroup($stagedRow);

        return $stagedRow->fresh([
            'employee',
            'workingHourTemplate',
        ]);
    }

    /**
     * Fill a missing clock-out using the scheduled end time when the inference
     * is safe and transparent.
     */
    protected function applyAutomaticClockOut(
        array $row,
        ?Employee $employee,
        ?WorkingHourTemplate $template,
        array $schedule,
        Collection $companyHolidays,
        array $settings
    ): array {
        $autoClockOutEnabled = (bool) (
            $settings['auto_clock_out_missing']
            ?? true
        );

        if (! $autoClockOutEnabled) {
            return $row;
        }

        /*
        |--------------------------------------------------------------------------
        | Only normal present rows may receive auto clock-out.
        |--------------------------------------------------------------------------
        |
        | Half-day leave and other leave rows need their own expected boundary
        | and must remain reviewable instead of using the full scheduled end.
        |
        */
        if (
            ($row['attendance_type'] ?? null) !== 'present'
            || ! filled($row['clock_in'] ?? null)
            || filled($row['clock_out'] ?? null)
            || filled($row['leave_type'] ?? null)
            || (bool) ($row['leave_was_detected'] ?? false)
            || ! filled($row['attendance_date'] ?? null)
            || ! filled($schedule['scheduled_end_time'] ?? null)
        ) {
            return $row;
        }

        $dateString = Carbon::parse(
            $row['attendance_date']
        )->toDateString();

        /*
        |--------------------------------------------------------------------------
        | Never auto clock-out on a company holiday.
        |--------------------------------------------------------------------------
        */
        if ($companyHolidays->has($dateString)) {
            return $row;
        }

        /*
        |--------------------------------------------------------------------------
        | The date must be an expected workday for the employee/template.
        |--------------------------------------------------------------------------
        */
        if (! $this->isExpectedWorkdayForAutomaticClockOut(
            attendanceDate: $dateString,
            employee: $employee,
            template: $template,
            settings: $settings
        )) {
            return $row;
        }

        if (! $this->isClockInCompatibleWithScheduledEnd(
            clockIn: $row['clock_in'],
            scheduledStart: $schedule['scheduled_start_time'] ?? null,
            scheduledEnd: $schedule['scheduled_end_time']
        )) {
            return $row;
        }

        $scheduledEnd = $this->normalizeTime(
            $schedule['scheduled_end_time']
        );

        if (! $scheduledEnd) {
            return $row;
        }

        $row['clock_out'] = $scheduledEnd;

        $templateLabel = $template?->name
            ?: 'default schedule';

        $autoRemark = "Auto clock-out {$scheduledEnd} berdasarkan {$templateLabel}.";

        $row['remarks'] = $this->appendRemark(
            existing: $row['remarks'] ?? null,
            additional: $autoRemark
        );

        $rawPayload = is_array($row['raw_payload'] ?? null)
            ? $row['raw_payload']
            : [];

        $systemPayload = is_array($rawPayload['_system'] ?? null)
            ? $rawPayload['_system']
            : [];

        $rawPayload['_system'] = array_merge(
            $systemPayload,
            [
                'auto_clock_out' => true,
                'original_clock_out' => null,
                'inferred_clock_out' => $scheduledEnd,
                'inferred_from' => $template
                    ? 'working_hour_template'
                    : 'default_setting',
                'working_hour_template_id' => $template?->id,
                'working_hour_template_name' => $template?->name,
                'generated_at' => now()->toIso8601String(),
            ]
        );

        $row['raw_payload'] = $rawPayload;

        return $row;
    }

    /**
     * Confirm that the date belongs to the employee's expected work pattern.
     */
    protected function isExpectedWorkdayForAutomaticClockOut(
        string $attendanceDate,
        ?Employee $employee,
        ?WorkingHourTemplate $template,
        array $settings
    ): bool {
        try {
            $isoDayNumber = Carbon::parse(
                $attendanceDate
            )->dayOfWeekIso;
        } catch (Throwable) {
            return false;
        }

        $workingDays = [];

        if (
            $employee
            && is_array($employee->working_days_override)
            && ! empty($employee->working_days_override)
        ) {
            $workingDays = $employee->working_days_override;
        } elseif (
            $template
            && is_array($template->working_days)
            && ! empty($template->working_days)
        ) {
            $workingDays = $template->working_days;
        } else {
            $workingDays = $settings['default_working_days']
                ?? self::DEFAULT_WORKING_DAYS;
        }

        return in_array(
            $isoDayNumber,
            $this->normalizeWorkingDays($workingDays),
            true
        );
    }

    /**
     * Prevent obviously impossible inferences, such as a day-shift employee
     * clocking in after the scheduled end.
     */
    protected function isClockInCompatibleWithScheduledEnd(
        string $clockIn,
        ?string $scheduledStart,
        string $scheduledEnd
    ): bool {
        $clockIn = $this->normalizeTime($clockIn);
        $scheduledStart = $this->normalizeTime($scheduledStart);
        $scheduledEnd = $this->normalizeTime($scheduledEnd);

        if (! $clockIn || ! $scheduledEnd) {
            return false;
        }

        /*
        |--------------------------------------------------------------------------
        | Without a start time, a same-day clock-in must not be after end time.
        |--------------------------------------------------------------------------
        */
        if (! $scheduledStart) {
            return $clockIn <= $scheduledEnd;
        }

        /*
        |--------------------------------------------------------------------------
        | Normal same-day schedule.
        |--------------------------------------------------------------------------
        */
        if ($scheduledStart < $scheduledEnd) {
            return $clockIn <= $scheduledEnd;
        }

        /*
        |--------------------------------------------------------------------------
        | Overnight schedule, for example 22:00–07:00.
        |--------------------------------------------------------------------------
        |
        | A valid clock-in is either on/after the evening start or on/before the
        | next-day end.
        |
        */
        return $clockIn >= $scheduledStart
            || $clockIn <= $scheduledEnd;
    }

    protected function appendRemark(
        ?string $existing,
        string $additional
    ): string {
        $existing = trim((string) $existing);
        $additional = trim($additional);

        if ($existing === '') {
            return $additional;
        }

        if (
            Str::contains(
                Str::lower($existing),
                Str::lower($additional)
            )
        ) {
            return $existing;
        }

        return rtrim($existing, " \t\n\r\0\x0B.")
            . '. '
            . $additional;
    }

    protected function resolveEmployeeForRow(
        array $row,
        Collection $employees
    ): ?Employee {
        if ($this->isValidEmployeeNumber($row['employee_number'] ?? null)) {
            $employee = Employee::query()
                ->where('employee_number', $row['employee_number'])
                ->first();

            if ($employee) {
                return $employee;
            }
        }

        $normalizedName = $row['normalized_employee_name'] ?? '';

        if ($normalizedName === '') {
            return null;
        }

        $fromContext = $employees->first(
            fn (Employee $employee) => $employee->normalized_name === $normalizedName
        );

        if ($fromContext) {
            return $fromContext;
        }

        return Employee::query()
            ->where('normalized_name', $normalizedName)
            ->first();
    }

    protected function assignEmployeeDefaultTemplates(
        Collection $employees,
        array $templateUsage
    ): void {
        foreach ($employees as $employee) {
            $usage = $templateUsage[$employee->id] ?? [];

            if (empty($usage)) {
                continue;
            }

            arsort($usage);
            $mostUsedTemplateId = (int) array_key_first($usage);

            if (
                ! $employee->default_working_hour_template_id
                || $employee->source === 'attendance_import'
            ) {
                $employee->update([
                    'default_working_hour_template_id' => $mostUsedTemplateId,
                ]);
            }
        }
    }

    /**
     * Create one needs-review row for each expected workday missing from Excel.
     */
    /**
     * Generate calendar rows that are absent from the Excel export.
     *
     * Company holidays remain visible as valid system-generated rows, while
     * missing expected workdays remain Needs Review.
     */
    protected function generateMissingAttendanceRows(
        AttendanceImport $attendanceImport,
        Collection $employees,
        array $settings,
        ?Collection $companyHolidays = null
    ): void {
        $dateFrom = $attendanceImport->date_from
            ? Carbon::parse($attendanceImport->date_from)->startOfDay()
            : null;

        $dateTo = $attendanceImport->date_to
            ? Carbon::parse($attendanceImport->date_to)->startOfDay()
            : null;

        if (! $dateFrom || ! $dateTo) {
            return;
        }

        if (! (bool) $settings['include_future_dates'] && $dateTo->isFuture()) {
            $dateTo = now()->startOfDay();
        }

        $companyHolidays ??= $this->getCompanyHolidaysByDate(
            dateFrom: $dateFrom,
            dateTo: $dateTo
        );

        $generateHolidayRows = (bool) (
            $settings['generate_holiday_rows']
            ?? true
        );

        $generateMissingRows = (bool) (
            $settings['generate_missing_rows']
            ?? true
        );

        foreach ($employees as $employee) {
            $employee->loadMissing('defaultWorkingHourTemplate');

            $employeeStartDate = $this->resolveEmployeeExpectedStartDate(
                employee: $employee,
                importStart: $dateFrom
            );

            $actualDates = $attendanceImport->rows()
                ->where('employee_id', $employee->id)
                ->whereNotNull('attendance_date')
                ->pluck('attendance_date')
                ->map(fn ($date) => Carbon::parse($date)->toDateString())
                ->flip();

            $workingDays = $this->resolveEmployeeWorkingDays(
                employee: $employee,
                settings: $settings
            );

            $template = $employee->defaultWorkingHourTemplate;
            $schedule = $this->resolveScheduleSnapshot(
                template: $template,
                settings: $settings
            );

            foreach (CarbonPeriod::create($employeeStartDate, $dateTo) as $date) {
                $dateString = $date->toDateString();

                /*
                |--------------------------------------------------------------------------
                | Excel data takes precedence.
                |--------------------------------------------------------------------------
                |
                | If an actual row exists on a holiday or off day, do not create a
                | second generated row for the same employee/date.
                |
                */
                if ($actualDates->has($dateString)) {
                    continue;
                }

                /*
                |--------------------------------------------------------------------------
                | Keep company holidays visible in the attendance timeline.
                |--------------------------------------------------------------------------
                */
                $holiday = $companyHolidays->get($dateString);

                if ($generateHolidayRows && is_array($holiday)) {
                    $this->createGeneratedHolidayRow(
                        attendanceImport: $attendanceImport,
                        employee: $employee,
                        template: $template,
                        schedule: $schedule,
                        attendanceDate: $dateString,
                        holiday: $holiday
                    );

                    continue;
                }

                /*
                |--------------------------------------------------------------------------
                | Non-working days are ignored unless they are company holidays.
                |--------------------------------------------------------------------------
                */
                if (! in_array($date->dayOfWeekIso, $workingDays, true)) {
                    continue;
                }

                if (! $generateMissingRows) {
                    continue;
                }

                AttendanceImportRow::create([
                    'attendance_import_id' => $attendanceImport->id,
                    'employee_id' => $employee->id,
                    'working_hour_template_id' => $template?->id,
                    'working_hours_template_raw' => $template?->name,

                    'source_row_key' => hash('sha256', implode('|', [
                        'generated_missing',
                        $attendanceImport->id,
                        $employee->id,
                        $dateString,
                    ])),

                    'attendance_date' => $dateString,
                    'employee_number' => $employee->employee_number,
                    'employee_name' => $employee->name,
                    'employee_name_raw' => $employee->name,
                    'employee_number_raw' => $employee->employee_number,

                    'scheduled_start_time' => $schedule['scheduled_start_time'],
                    'scheduled_end_time' => $schedule['scheduled_end_time'],
                    'scheduled_work_minutes' => $schedule['scheduled_work_minutes'],
                    'schedule_source' => $template
                        ? 'employee_default'
                        : 'default_setting',
                    'schedule_is_inferred' => true,

                    'attendance_type' => 'missing',
                    'punctuality_status' => 'not_applicable',
                    'arrival_status' => 'not_applicable',
                    'departure_status' => 'not_applicable',

                    'source' => AttendanceImportRow::SOURCE_GENERATED_GAP,
                    'review_status' => AttendanceImportRow::REVIEW_NEEDS_REVIEW,
                    'validation_message' => 'Tidak ditemukan data attendance pada hari kerja. HR perlu memilih sakit, cuti, izin, absent, off day, holiday, atau data issue.',
                    'raw_payload' => [
                        '_system' => [
                            'generated' => true,
                            'generated_type' => 'missing_workday',
                            'generated_at' => now()->toIso8601String(),
                        ],
                    ],
                ]);
            }
        }
    }

    /**
     * Create a valid system-generated holiday row for one employee/date.
     */
    protected function createGeneratedHolidayRow(
        AttendanceImport $attendanceImport,
        Employee $employee,
        ?WorkingHourTemplate $template,
        array $schedule,
        string $attendanceDate,
        array $holiday
    ): AttendanceImportRow {
        $holidayName = trim((string) (
            $holiday['name']
            ?? 'Company Holiday'
        ));

        $holidayType = trim((string) (
            $holiday['holiday_type']
            ?? ''
        ));

        $remarks = $holidayName;

        if ($holidayType !== '') {
            $remarks .= ' · '
                . Str::headline($holidayType);
        }

        if (filled($holiday['notes'] ?? null)) {
            $remarks .= '. '
                . trim((string) $holiday['notes']);
        }

        return AttendanceImportRow::create([
            'attendance_import_id' => $attendanceImport->id,
            'employee_id' => $employee->id,
            'working_hour_template_id' => $template?->id,
            'working_hours_template_raw' => $template?->name,

            'source_row_key' => hash('sha256', implode('|', [
                'generated_holiday',
                $attendanceImport->id,
                $employee->id,
                $attendanceDate,
                implode(',', $holiday['ids'] ?? []),
            ])),

            'attendance_date' => $attendanceDate,
            'employee_number' => $employee->employee_number,
            'employee_name' => $employee->name,
            'employee_name_raw' => $employee->name,
            'employee_number_raw' => $employee->employee_number,

            'scheduled_start_time' => $schedule['scheduled_start_time'],
            'scheduled_end_time' => $schedule['scheduled_end_time'],
            'scheduled_work_minutes' => $schedule['scheduled_work_minutes'],
            'worked_minutes' => null,
            'schedule_source' => $template
                ? 'employee_default'
                : 'default_setting',
            'schedule_is_inferred' => true,

            'attendance_type' => 'holiday',
            'punctuality_status' => 'not_applicable',
            'arrival_status' => 'not_applicable',
            'departure_status' => 'not_applicable',
            'late_minutes' => null,
            'early_leave_minutes' => null,

            'is_excused' => true,
            'remarks' => $remarks,

            'source' => AttendanceImportRow::SOURCE_GENERATED_GAP,
            'review_status' => AttendanceImportRow::REVIEW_VALID,
            'validation_message' => null,

            'raw_payload' => [
                '_system' => [
                    'generated' => true,
                    'generated_type' => 'company_holiday',
                    'holiday_ids' => $holiday['ids'] ?? [],
                    'holiday_name' => $holidayName,
                    'holiday_type' => $holidayType !== ''
                        ? $holidayType
                        : null,
                    'holiday_notes' => $holiday['notes'] ?? null,
                    'generated_at' => now()->toIso8601String(),
                ],
            ],
        ]);
    }

    protected function resolveEmployeeExpectedStartDate(
        Employee $employee,
        Carbon $importStart
    ): Carbon {
        $joiningDate = data_get($employee->metadata, 'date_of_joining');

        if (! $joiningDate) {
            return $importStart->copy();
        }

        try {
            $joining = Carbon::parse($joiningDate)->startOfDay();

            return $joining->greaterThan($importStart)
                ? $joining
                : $importStart->copy();
        } catch (Throwable) {
            return $importStart->copy();
        }
    }

    protected function resolveEmployeeWorkingDays(
        Employee $employee,
        array $settings
    ): array {
        $override = is_array($employee->working_days_override)
            ? $employee->working_days_override
            : [];

        if (! empty($override)) {
            return $this->normalizeWorkingDays($override);
        }

        $templateDays = is_array($employee->defaultWorkingHourTemplate?->working_days)
            ? $employee->defaultWorkingHourTemplate->working_days
            : [];

        if (! empty($templateDays)) {
            return $this->normalizeWorkingDays($templateDays);
        }

        return $settings['default_working_days'];
    }

    /**
     * Return active company holidays keyed by date.
     *
     * Multiple holidays on the same date are merged so one generated attendance
     * row can still preserve all names, types, notes, and source IDs.
     */
    protected function getCompanyHolidaysByDate(
        Carbon $dateFrom,
        Carbon $dateTo
    ): Collection {
        if (! Schema::hasTable('company_holidays')) {
            return collect();
        }

        return CompanyHoliday::query()
            ->active()
            ->whereDate('holiday_date', '>=', $dateFrom->toDateString())
            ->whereDate('holiday_date', '<=', $dateTo->toDateString())
            ->orderBy('holiday_date')
            ->orderBy('name')
            ->get([
                'id',
                'holiday_date',
                'name',
                'holiday_type',
                'notes',
            ])
            ->groupBy(
                fn (CompanyHoliday $holiday) => $holiday
                    ->holiday_date
                    ->toDateString()
            )
            ->map(function (Collection $holidays): array {
                $names = $holidays
                    ->pluck('name')
                    ->filter()
                    ->map(fn ($name) => trim((string) $name))
                    ->unique()
                    ->values();

                $types = $holidays
                    ->pluck('holiday_type')
                    ->filter()
                    ->map(fn ($type) => trim((string) $type))
                    ->unique()
                    ->values();

                $notes = $holidays
                    ->pluck('notes')
                    ->filter()
                    ->map(fn ($note) => trim((string) $note))
                    ->unique()
                    ->values();

                return [
                    'ids' => $holidays
                        ->pluck('id')
                        ->map(fn ($id) => (int) $id)
                        ->values()
                        ->all(),

                    'name' => $names->isNotEmpty()
                        ? $names->implode(' / ')
                        : 'Company Holiday',

                    'holiday_type' => $types->isNotEmpty()
                        ? $types->implode(', ')
                        : null,

                    'notes' => $notes->isNotEmpty()
                        ? $notes->implode(' ')
                        : null,
                ];
            });
    }

    protected function resolveScheduleSnapshot(
        ?WorkingHourTemplate $template,
        array $settings
    ): array {
        $start = $this->normalizeTime(
            $template?->start_time
                ?? null
        );

        $end = $this->normalizeTime(
            $template?->end_time
                ?? null
        );

        return [
            'scheduled_start_time' => $start,
            'scheduled_end_time' => $end,
            'scheduled_work_minutes' => $template?->scheduled_work_minutes
                ?: $this->calculateMinutesBetween($start, $end),
        ];
    }

    protected function resolvePunctuality(
        ?string $clockIn,
        ?string $clockOut,
        ?string $scheduledStart,
        ?string $scheduledEnd,
        int $toleranceMinutes,
        ?string $rawStatus,
        string $attendanceType
    ): array {
        if ($attendanceType !== 'present') {
            return [
                'punctuality_status' => 'not_applicable',
                'arrival_status' => 'not_applicable',
                'departure_status' => 'not_applicable',
                'late_minutes' => null,
                'early_leave_minutes' => null,
            ];
        }

        $lateMinutes = null;
        $earlyLeaveMinutes = null;
        $arrivalStatus = 'unknown';
        $departureStatus = 'unknown';

        if ($clockIn && $scheduledStart) {
            $lateMinutes = max(
                $this->timeDifferenceMinutes($scheduledStart, $clockIn)
                    - max($toleranceMinutes, 0),
                0
            );

            $arrivalStatus = $lateMinutes > 0
                ? 'late'
                : 'on_time';
        } else {
            $rawPunctuality = $this->normalizeRawPunctuality($rawStatus);

            if ($rawPunctuality !== 'unknown') {
                $arrivalStatus = $rawPunctuality;
            }
        }

        if ($clockOut && $scheduledEnd) {
            $earlyLeaveMinutes = max(
                $this->timeDifferenceMinutes($clockOut, $scheduledEnd),
                0
            );

            $departureStatus = $earlyLeaveMinutes > 0
                ? 'early_departure'
                : 'on_time';
        }

        return [
            'punctuality_status' => $arrivalStatus,
            'arrival_status' => $arrivalStatus,
            'departure_status' => $departureStatus,
            'late_minutes' => $lateMinutes,
            'early_leave_minutes' => $earlyLeaveMinutes,
        ];
    }

    protected function resolveInitialReviewState(
        array $row,
        ?Employee $employee,
        ?WorkingHourTemplate $template,
        array $punctuality
    ): array {
        $messages = [];

        if (! $row['attendance_date']) {
            $messages[] = 'Attendance date tidak terbaca.';
        }

        if (! $employee) {
            $messages[] = 'Employee belum berhasil dicocokkan ke master employee.';
        }

        if (
            $row['attendance_type'] === 'present'
            && ! $row['clock_in']
        ) {
            $messages[] = 'Clock in tidak tersedia untuk attendance present.';
        }

        if (
            $row['attendance_type'] === 'present'
            && ! $row['clock_out']
        ) {
            $messages[] = 'Clock out belum tersedia.';
        }

        if ($row['leave_requires_review']) {
            $messages[] = 'Jenis atau durasi leave perlu dikonfirmasi HR.';
        }

        if (
            $template
            && (
                ! $this->normalizeTime($template->start_time)
                || ! $this->normalizeTime($template->end_time)
            )
        ) {
            $messages[] = "Working Hours Template {$template->name} belum memiliki konfigurasi jam lengkap.";
        }

        if (
            $row['attendance_type'] === 'present'
            && $punctuality['punctuality_status'] === 'unknown'
        ) {
            $messages[] = 'Punctuality belum dapat ditentukan.';
        }

        if (empty($messages)) {
            return [
                'review_status' => AttendanceImportRow::REVIEW_VALID,
                'validation_message' => null,
            ];
        }

        return [
            'review_status' => AttendanceImportRow::REVIEW_NEEDS_REVIEW,
            'validation_message' => implode(' ', $messages),
        ];
    }

    /**
     * Reconcile all staging rows that share one employee and attendance date.
     *
     * Rules:
     * - Same core attendance values: keep the highest-quality row and
     *   auto-ignore the additional rows.
     * - Different core attendance values: keep one representative per distinct
     *   value set and mark those representatives as conflicting duplicates.
     * - Rows manually ignored by HR remain ignored and no longer block the
     *   duplicate group.
     */
    protected function reconcileStagingDuplicateGroup(
        AttendanceImportRow $row
    ): void {
        $this->reconcileStagingDuplicateGroupByIdentity(
            attendanceImport: $row->attendanceImport,
            employeeId: $row->employee_id,
            attendanceDate: $this->normalizeDuplicateDate(
                $row->attendance_date
            )
        );
    }

    protected function reconcileStagingDuplicateGroupByIdentity(
        AttendanceImport $attendanceImport,
        ?int $employeeId,
        ?string $attendanceDate
    ): void {
        if (! $employeeId || ! $attendanceDate) {
            return;
        }

        $rows = $attendanceImport->rows()
            ->where('employee_id', $employeeId)
            ->whereDate('attendance_date', $attendanceDate)
            ->orderBy('id')
            ->get();

        if ($rows->isEmpty()) {
            return;
        }

        /*
        |--------------------------------------------------------------------------
        | Do not label an ordinary single row as a duplicate.
        |--------------------------------------------------------------------------
        |
        | A single row can still carry REVIEW_DUPLICATE after another row was
        | moved to a different employee/date. In that case restore its original
        | review state and preserve a resolved-conflict audit marker.
        |
        */
        if ($rows->count() === 1) {
            /** @var AttendanceImportRow $onlyRow */
            $onlyRow = $rows->first();

            if (
                $onlyRow->review_status
                    === AttendanceImportRow::REVIEW_DUPLICATE
            ) {
                $groupKey = $this->duplicateGroupKey(
                    attendanceImportId: $attendanceImport->id,
                    employeeId: $employeeId,
                    attendanceDate: $attendanceDate
                );

                $this->markDuplicateCanonical(
                    canonical: $onlyRow,
                    groupKey: $groupKey,
                    allRows: $rows,
                    resolutionType: 'resolved_conflict'
                );
            }

            return;
        }

        $groupKey = $this->duplicateGroupKey(
            attendanceImportId: $attendanceImport->id,
            employeeId: $employeeId,
            attendanceDate: $attendanceDate
        );

        /*
        |--------------------------------------------------------------------------
        | A row explicitly ignored by HR is removed from active comparison.
        |--------------------------------------------------------------------------
        |
        | Auto-ignored exact duplicates are also excluded. They remain available
        | for audit through metadata but do not create a new blocking conflict.
        |
        */
        $activeRows = $rows
            ->reject(
                fn (AttendanceImportRow $candidate): bool =>
                    $candidate->review_status
                        === AttendanceImportRow::REVIEW_IGNORED
            )
            ->values();

        if ($activeRows->isEmpty()) {
            return;
        }

        $fingerprintGroups = $activeRows
            ->groupBy(
                fn (AttendanceImportRow $candidate): string =>
                    $this->duplicateFingerprint($candidate)
            );

        $representatives = collect();

        foreach ($fingerprintGroups as $fingerprintRows) {
            /** @var AttendanceImportRow $preferred */
            $preferred = $fingerprintRows
                ->sort(function (
                    AttendanceImportRow $left,
                    AttendanceImportRow $right
                ): int {
                    $scoreComparison =
                        $this->duplicateQualityScore($right)
                        <=> $this->duplicateQualityScore($left);

                    if ($scoreComparison !== 0) {
                        return $scoreComparison;
                    }

                    return $left->id <=> $right->id;
                })
                ->first();

            $representatives->push($preferred);

            $fingerprintRows
                ->reject(
                    fn (AttendanceImportRow $candidate): bool =>
                        $candidate->is($preferred)
                )
                ->each(function (
                    AttendanceImportRow $duplicate
                ) use (
                    $preferred,
                    $groupKey,
                    $rows
                ): void {
                    $this->markExactDuplicateIgnored(
                        duplicate: $duplicate,
                        canonical: $preferred,
                        groupKey: $groupKey,
                        allRows: $rows
                    );
                });
        }

        $representatives = $representatives
            ->values();

        if ($representatives->count() === 1) {
            /** @var AttendanceImportRow $canonical */
            $canonical = $representatives->first();

            $allFingerprintCount = $rows
                ->groupBy(
                    fn (AttendanceImportRow $candidate): string =>
                        $this->duplicateFingerprint($candidate)
                )
                ->count();

            $this->markDuplicateCanonical(
                canonical: $canonical,
                groupKey: $groupKey,
                allRows: $rows,
                resolutionType: $allFingerprintCount > 1
                    ? 'resolved_conflict'
                    : 'exact'
            );

            return;
        }

        $conflictingFields =
            $this->duplicateDifferenceFields(
                $representatives
            );

        $comparisonMatrix =
            $this->duplicateComparisonMatrix(
                $representatives
            );

        $representativeIds = $representatives
            ->pluck('id')
            ->map(fn ($id): int => (int) $id)
            ->values()
            ->all();

        foreach ($representatives as $representative) {
            $this->markConflictingDuplicate(
                row: $representative,
                groupKey: $groupKey,
                allRows: $rows,
                representativeIds: $representativeIds,
                conflictingFields: $conflictingFields,
                comparisonMatrix: $comparisonMatrix
            );
        }
    }

    /**
     * Core normalized values used for exact/conflicting comparison.
     */
    protected function duplicateComparisonPayload(
        AttendanceImportRow $row
    ): array {
        $payload = [
            'working_hour_template_id' =>
                $row->working_hour_template_id
                    ? (int) $row->working_hour_template_id
                    : null,

            'scheduled_start_time' => $this->normalizeTime(
                $row->scheduled_start_time
            ),

            'scheduled_end_time' => $this->normalizeTime(
                $row->scheduled_end_time
            ),

            'attendance_type' => $this->normalizeDuplicateScalar(
                $row->attendance_type
            ),

            'clock_in' => $this->normalizeTime(
                $row->clock_in
            ),

            'clock_out' => $this->normalizeTime(
                $row->clock_out
            ),

            'leave_type' => $this->normalizeDuplicateScalar(
                $row->leave_type
            ),

            'leave_duration' => $this->normalizeDuplicateScalar(
                $row->leave_duration
            ),

            'leave_session' => $this->normalizeDuplicateScalar(
                $row->leave_session
            ),

            'leave_start_time' => $this->normalizeTime(
                $row->leave_start_time
            ),

            'leave_end_time' => $this->normalizeTime(
                $row->leave_end_time
            ),

            'leave_minutes' => $row->leave_minutes !== null
                ? (int) $row->leave_minutes
                : null,

            'is_excused' => (bool) $row->is_excused,
        ];

        return Arr::only(
            $payload,
            self::DUPLICATE_COMPARISON_FIELDS
        );
    }

    protected function duplicateFingerprint(
        AttendanceImportRow $row
    ): string {
        return hash(
            'sha256',
            json_encode(
                $this->duplicateComparisonPayload($row),
                JSON_UNESCAPED_UNICODE
                    | JSON_UNESCAPED_SLASHES
                    | JSON_PRESERVE_ZERO_FRACTION
            ) ?: ''
        );
    }

    /**
     * Prefer matched, complete, explicit attendance evidence when exact
     * duplicates contain different levels of data quality.
     */
    protected function duplicateQualityScore(
        AttendanceImportRow $row
    ): int {
        $score = 0;

        $score += $row->employee_id ? 100 : 0;
        $score += $row->clock_in ? 20 : 0;
        $score += $row->clock_out ? 20 : 0;
        $score += $row->working_hour_template_id ? 10 : 0;
        $score += ! $row->schedule_is_inferred ? 6 : 0;

        $score += (bool) data_get(
            $row->raw_payload,
            '_system.auto_clock_out',
            false
        )
            ? 0
            : 8;

        $score += in_array(
            $row->review_status,
            [
                AttendanceImportRow::REVIEW_VALID,
                AttendanceImportRow::REVIEW_RESOLVED,
            ],
            true
        )
            ? 5
            : 0;

        $score += filled($row->remarks) ? 2 : 0;
        $score += filled($row->leave_reason) ? 2 : 0;

        return $score;
    }

    protected function duplicateDifferenceFields(
        Collection $rows
    ): array {
        $payloads = $rows
            ->map(
                fn (AttendanceImportRow $row): array =>
                    $this->duplicateComparisonPayload($row)
            );

        return collect(
            self::DUPLICATE_COMPARISON_FIELDS
        )
            ->filter(function (
                string $field
            ) use ($payloads): bool {
                return $payloads
                    ->pluck($field)
                    ->map(
                        fn ($value): string =>
                            json_encode(
                                $value,
                                JSON_UNESCAPED_UNICODE
                                    | JSON_UNESCAPED_SLASHES
                                    | JSON_PRESERVE_ZERO_FRACTION
                            ) ?: 'null'
                    )
                    ->unique()
                    ->count() > 1;
            })
            ->values()
            ->all();
    }

    protected function duplicateComparisonMatrix(
        Collection $rows
    ): array {
        return $rows
            ->mapWithKeys(
                fn (AttendanceImportRow $row): array => [
                    (string) $row->id =>
                        $this->duplicateComparisonPayload(
                            $row
                        ),
                ]
            )
            ->all();
    }

    protected function duplicateGroupKey(
        int $attendanceImportId,
        int $employeeId,
        string $attendanceDate
    ): string {
        return hash(
            'sha256',
            implode('|', [
                'attendance_duplicate',
                $attendanceImportId,
                $employeeId,
                $attendanceDate,
            ])
        );
    }

    protected function normalizeDuplicateDate(
        mixed $value
    ): ?string {
        if (! $value) {
            return null;
        }

        try {
            return Carbon::parse($value)->toDateString();
        } catch (Throwable) {
            return null;
        }
    }

    protected function normalizeDuplicateScalar(
        mixed $value
    ): mixed {
        if ($value === null) {
            return null;
        }

        if (is_bool($value)) {
            return $value;
        }

        if (is_int($value) || is_float($value)) {
            return $value;
        }

        $text = Str::of((string) $value)
            ->lower()
            ->squish()
            ->toString();

        return $text !== ''
            ? $text
            : null;
    }

    protected function markExactDuplicateIgnored(
        AttendanceImportRow $duplicate,
        AttendanceImportRow $canonical,
        string $groupKey,
        Collection $allRows
    ): void {
        $before = $this->duplicateReviewStateBeforeChange(
            $duplicate
        );

        $relatedIds = $allRows
            ->pluck('id')
            ->map(fn ($id): int => (int) $id)
            ->values()
            ->all();

        $metadata = [
            'detected' => true,
            'type' => 'exact',
            'role' => 'ignored_exact',
            'group_key' => $groupKey,
            'canonical_row_id' => (int) $canonical->id,
            'related_row_ids' => $relatedIds,
            'comparison_fields' =>
                self::DUPLICATE_COMPARISON_FIELDS,
            'comparison' => [
                (string) $canonical->id =>
                    $this->duplicateComparisonPayload(
                        $canonical
                    ),
                (string) $duplicate->id =>
                    $this->duplicateComparisonPayload(
                        $duplicate
                    ),
            ],
            'review_status_before_duplicate' =>
                $before['review_status'],
            'validation_message_before_duplicate' =>
                $before['validation_message'],
            'auto_resolved' => true,
            'resolved_action' => 'auto_ignore_exact',
            'updated_at' => now()->toIso8601String(),
        ];

        $duplicate->forceFill([
            'review_status' =>
                AttendanceImportRow::REVIEW_IGNORED,
            'validation_message' => null,
            'resolved_by' => null,
            'resolved_at' => now(),
            'raw_payload' =>
                $this->mergeDuplicateRawMetadata(
                    $duplicate,
                    $metadata
                ),
            'resolution_metadata' =>
                $this->mergeDuplicateResolutionMetadata(
                    $duplicate,
                    [
                        'type' => 'exact',
                        'action' => 'auto_ignore_exact',
                        'canonical_row_id' =>
                            (int) $canonical->id,
                        'group_key' => $groupKey,
                        'resolved_at' =>
                            now()->toIso8601String(),
                    ]
                ),
        ])->save();
    }

    protected function markDuplicateCanonical(
        AttendanceImportRow $canonical,
        string $groupKey,
        Collection $allRows,
        string $resolutionType
    ): void {
        /*
        |--------------------------------------------------------------------------
        | Keep every exact duplicate pointed at the latest preferred row.
        |--------------------------------------------------------------------------
        |
        | The preferred row can change when a later record contains stronger
        | evidence, for example an actual clock-out instead of an inferred one.
        |
        */
        $canonicalFingerprint =
            $this->duplicateFingerprint($canonical);

        $allRows
            ->filter(
                fn (AttendanceImportRow $candidate): bool =>
                    ! $candidate->is($canonical)
                    && $this->duplicateFingerprint(
                        $candidate
                    ) === $canonicalFingerprint
            )
            ->each(function (
                AttendanceImportRow $duplicate
            ) use (
                $canonical,
                $groupKey,
                $allRows
            ): void {
                $this->markExactDuplicateIgnored(
                    duplicate: $duplicate,
                    canonical: $canonical,
                    groupKey: $groupKey,
                    allRows: $allRows
                );
            });

        $relatedIds = $allRows
            ->pluck('id')
            ->map(fn ($id): int => (int) $id)
            ->values()
            ->all();

        $wasDuplicate =
            $canonical->review_status
                === AttendanceImportRow::REVIEW_DUPLICATE;

        if ($wasDuplicate) {
            $this->restoreReviewStateBeforeDuplicate(
                $canonical
            );

            $canonical->refresh();
        }

        $metadata = [
            'detected' => $allRows->count() > 1,
            'type' => $resolutionType,
            'role' => $resolutionType === 'resolved_conflict'
                ? 'selected_record'
                : 'canonical',
            'group_key' => $groupKey,
            'canonical_row_id' => (int) $canonical->id,
            'related_row_ids' => $relatedIds,
            'comparison_fields' =>
                self::DUPLICATE_COMPARISON_FIELDS,
            'comparison' =>
                $this->duplicateComparisonMatrix(
                    $allRows
                ),
            'auto_resolved' => $resolutionType === 'exact',
            'resolved_action' =>
                $resolutionType === 'resolved_conflict'
                    ? 'conflict_resolved_by_ignored_rows'
                    : 'keep_canonical',
            'updated_at' => now()->toIso8601String(),
        ];

        $canonical->forceFill([
            'raw_payload' =>
                $this->mergeDuplicateRawMetadata(
                    $canonical,
                    $metadata
                ),
            'resolution_metadata' =>
                $this->mergeDuplicateResolutionMetadata(
                    $canonical,
                    [
                        'type' => $resolutionType,
                        'action' =>
                            $metadata['resolved_action'],
                        'canonical_row_id' =>
                            (int) $canonical->id,
                        'group_key' => $groupKey,
                        'resolved_at' =>
                            now()->toIso8601String(),
                    ]
                ),
        ])->save();
    }

    protected function markConflictingDuplicate(
        AttendanceImportRow $row,
        string $groupKey,
        Collection $allRows,
        array $representativeIds,
        array $conflictingFields,
        array $comparisonMatrix
    ): void {
        $before = $this->duplicateReviewStateBeforeChange(
            $row
        );

        $metadata = [
            'detected' => true,
            'type' => 'conflict',
            'role' => 'conflicting_record',
            'group_key' => $groupKey,
            'canonical_row_id' => null,
            'related_row_ids' => $allRows
                ->pluck('id')
                ->map(fn ($id): int => (int) $id)
                ->values()
                ->all(),
            'representative_row_ids' =>
                $representativeIds,
            'conflicting_fields' =>
                $conflictingFields,
            'comparison_fields' =>
                self::DUPLICATE_COMPARISON_FIELDS,
            'comparison' => $comparisonMatrix,
            'review_status_before_duplicate' =>
                $before['review_status'],
            'validation_message_before_duplicate' =>
                $before['validation_message'],
            'auto_resolved' => false,
            'resolved_action' => null,
            'updated_at' => now()->toIso8601String(),
        ];

        $row->forceFill([
            'review_status' =>
                AttendanceImportRow::REVIEW_DUPLICATE,
            'validation_message' =>
                self::DUPLICATE_CONFLICT_MESSAGE,
            'resolved_by' => null,
            'resolved_at' => null,
            'raw_payload' =>
                $this->mergeDuplicateRawMetadata(
                    $row,
                    $metadata
                ),
            'resolution_metadata' =>
                $this->mergeDuplicateResolutionMetadata(
                    $row,
                    [
                        'type' => 'conflict',
                        'action' => 'pending_hr_review',
                        'group_key' => $groupKey,
                        'representative_row_ids' =>
                            $representativeIds,
                        'conflicting_fields' =>
                            $conflictingFields,
                        'detected_at' =>
                            now()->toIso8601String(),
                    ]
                ),
        ])->save();
    }

    protected function duplicateReviewStateBeforeChange(
        AttendanceImportRow $row
    ): array {
        $existingDuplicateMetadata = data_get(
            $row->raw_payload,
            '_system.duplicate',
            []
        );

        $reviewStatus = data_get(
            $existingDuplicateMetadata,
            'review_status_before_duplicate'
        );

        $validationMessage = data_get(
            $existingDuplicateMetadata,
            'validation_message_before_duplicate'
        );

        if (! $reviewStatus) {
            $reviewStatus = in_array(
                $row->review_status,
                [
                    AttendanceImportRow::REVIEW_DUPLICATE,
                    AttendanceImportRow::REVIEW_IGNORED,
                ],
                true
            )
                ? AttendanceImportRow::REVIEW_VALID
                : $row->review_status;
        }

        if (
            ! array_key_exists(
                'validation_message_before_duplicate',
                is_array($existingDuplicateMetadata)
                    ? $existingDuplicateMetadata
                    : []
            )
        ) {
            $validationMessage =
                $row->validation_message;
        }

        return [
            'review_status' => $reviewStatus,
            'validation_message' => $validationMessage,
        ];
    }

    protected function restoreReviewStateBeforeDuplicate(
        AttendanceImportRow $row
    ): void {
        $before = $this->duplicateReviewStateBeforeChange(
            $row
        );

        $restorableStatuses = [
            AttendanceImportRow::REVIEW_VALID,
            AttendanceImportRow::REVIEW_NEEDS_REVIEW,
            AttendanceImportRow::REVIEW_RESOLVED,
            AttendanceImportRow::REVIEW_ERROR,
        ];

        $restoredStatus = in_array(
            $before['review_status'],
            $restorableStatuses,
            true
        )
            ? $before['review_status']
            : AttendanceImportRow::REVIEW_VALID;

        $row->forceFill([
            'review_status' => $restoredStatus,
            'validation_message' =>
                $before['validation_message'],
        ])->save();
    }

    protected function mergeDuplicateRawMetadata(
        AttendanceImportRow $row,
        array $duplicateMetadata
    ): array {
        $rawPayload = is_array($row->raw_payload)
            ? $row->raw_payload
            : [];

        $systemPayload = is_array(
            $rawPayload['_system'] ?? null
        )
            ? $rawPayload['_system']
            : [];

        $existingDuplicateMetadata = is_array(
            $systemPayload['duplicate'] ?? null
        )
            ? $systemPayload['duplicate']
            : [];

        $systemPayload['duplicate'] = array_merge(
            $existingDuplicateMetadata,
            $duplicateMetadata
        );

        $rawPayload['_system'] = $systemPayload;

        return $rawPayload;
    }

    protected function mergeDuplicateResolutionMetadata(
        AttendanceImportRow $row,
        array $duplicateResolution
    ): array {
        $resolutionMetadata =
            is_array($row->resolution_metadata)
                ? $row->resolution_metadata
                : [];

        $existing = is_array(
            $resolutionMetadata[
                'duplicate_resolution'
            ] ?? null
        )
            ? $resolutionMetadata[
                'duplicate_resolution'
            ]
            : [];

        $resolutionMetadata[
            'duplicate_resolution'
        ] = array_merge(
            $existing,
            $duplicateResolution
        );

        return $resolutionMetadata;
    }

    protected function normalizeManualReviewPayload(
        AttendanceImportRow $row,
        array $payload
    ): array {
        $attendanceType = $payload['attendance_type']
            ?? $row->attendance_type;

        $leaveType = $payload['leave_type']
            ?? $row->leave_type;

        $leaveDuration = $payload['leave_duration']
            ?? $row->leave_duration;

        $leaveSession = $payload['leave_session']
            ?? $row->leave_session;

        $isExcused = array_key_exists('is_excused', $payload)
            ? (bool) $payload['is_excused']
            : (bool) $row->is_excused;

        if (in_array($attendanceType, ['off_day', 'holiday'], true)) {
            $payload['punctuality_status'] = 'not_applicable';
            $payload['arrival_status'] = 'not_applicable';
            $payload['departure_status'] = 'not_applicable';
            $payload['late_minutes'] = null;
            $payload['early_leave_minutes'] = null;
        }

        if (
            in_array($leaveType, [
                'sick_leave',
                'annual_leave',
                'unpaid_leave',
                'permission',
                'other',
            ], true)
            && $leaveDuration === 'full_day'
        ) {
            $payload['attendance_type'] = 'absent';
            $payload['punctuality_status'] = 'not_applicable';
            $payload['arrival_status'] = 'not_applicable';
            $payload['departure_status'] = 'not_applicable';
            $payload['is_excused'] = true;
        }

        if (
            $attendanceType === 'present'
            && $leaveDuration === 'half_day'
            && $isExcused
        ) {
            if (in_array($leaveSession, ['first_half', 'late_arrival'], true)) {
                $payload['arrival_status'] = 'excused_late';
                $payload['punctuality_status'] = 'excused_late';
            }

            if (in_array($leaveSession, ['second_half', 'early_departure'], true)) {
                $payload['departure_status'] = 'excused_early_departure';
            }
        }

        if (($payload['attendance_type'] ?? $attendanceType) === 'missing') {
            $payload['review_status'] = AttendanceImportRow::REVIEW_NEEDS_REVIEW;
        } elseif (($payload['review_status'] ?? null) !== AttendanceImportRow::REVIEW_IGNORED) {
            $payload['review_status'] = AttendanceImportRow::REVIEW_RESOLVED;
        }

        return $payload;
    }

    protected function assertImportCanBeConfirmed(
        AttendanceImport $attendanceImport
    ): void {
        $blockingRows = $attendanceImport->rows()
            ->where(function ($query): void {
                $query
                    ->whereIn('review_status', [
                        AttendanceImportRow::REVIEW_NEEDS_REVIEW,
                        AttendanceImportRow::REVIEW_ERROR,
                        AttendanceImportRow::REVIEW_DUPLICATE,
                    ])
                    ->orWhereNull('employee_id')
                    ->orWhereNull('attendance_date');
            })
            ->count();

        if ($blockingRows > 0) {
            throw ValidationException::withMessages([
                'attendance_import' => "Masih ada {$blockingRows} row yang perlu diselesaikan sebelum import dikonfirmasi.",
            ]);
        }
    }

    protected function assertImportIsEditable(
        AttendanceImport $attendanceImport
    ): void {
        if (! in_array($attendanceImport->status, [
            AttendanceImport::STATUS_UPLOADED,
            AttendanceImport::STATUS_REVIEWING,
            AttendanceImport::STATUS_FAILED,
        ], true)) {
            throw ValidationException::withMessages([
                'attendance_import' => 'Import ini tidak dapat diedit pada status saat ini.',
            ]);
        }
    }

    protected function persistFinalAttendance(
        AttendanceImport $attendanceImport,
        AttendanceImportRow $row,
        string $duplicateAction,
        ?int $userId
    ): void {
        $existing = EmployeeAttendance::query()
            ->where('employee_id', $row->employee_id)
            ->whereDate('attendance_date', $row->attendance_date)
            ->first();

        if ($existing && $duplicateAction === 'skip') {
            return;
        }

        if ($existing && $duplicateAction === 'error') {
            throw ValidationException::withMessages([
                'attendance_import' => 'Attendance final sudah tersedia untuk '
                    . ($row->employee_name ?: 'employee')
                    . ' pada '
                    . Carbon::parse($row->attendance_date)->format('d M Y')
                    . '.',
            ]);
        }

        $payload = [
            'attendance_import_id' => $attendanceImport->id,
            'attendance_import_row_id' => $row->id,
            'employee_id' => $row->employee_id,
            'working_hour_template_id' => $row->working_hour_template_id,
            'working_hours_template_raw' => $row->working_hours_template_raw,
            'attendance_date' => $row->attendance_date,
            'clock_in' => $row->clock_in,
            'clock_out' => $row->clock_out,
            'scheduled_start_time' => $row->scheduled_start_time,
            'scheduled_end_time' => $row->scheduled_end_time,
            'scheduled_work_minutes' => $row->scheduled_work_minutes,
            'worked_minutes' => $row->worked_minutes,
            'schedule_source' => $row->schedule_source,
            'schedule_is_inferred' => $row->schedule_is_inferred,
            'attendance_type' => $row->attendance_type,
            'punctuality_status' => $row->punctuality_status,
            'arrival_status' => $row->arrival_status,
            'departure_status' => $row->departure_status,
            'late_minutes' => $row->late_minutes,
            'early_leave_minutes' => $row->early_leave_minutes,
            'leave_type' => $row->leave_type,
            'leave_duration' => $row->leave_duration,
            'leave_session' => $row->leave_session,
            'leave_start_time' => $row->leave_start_time,
            'leave_end_time' => $row->leave_end_time,
            'leave_minutes' => $row->leave_minutes,
            'is_excused' => $row->is_excused,
            'leave_reason' => $row->leave_reason,
            'remarks' => $row->remarks,
            'source' => $row->source,
            'metadata' => [
                'raw_payload' => $row->raw_payload,
                'resolution_metadata' => $row->resolution_metadata,
                'validation_message' => $row->validation_message,

                /*
                | Keep the system-generated markers easy to consume after the
                | staging row has been confirmed into employee_attendances.
                */
                'system' => data_get($row->raw_payload, '_system'),
                'auto_clock_out' => (bool) data_get(
                    $row->raw_payload,
                    '_system.auto_clock_out',
                    false
                ),
                'generated_type' => data_get(
                    $row->raw_payload,
                    '_system.generated_type'
                ),
            ],
            'updated_by' => $userId,
        ];

        if ($row->is_excused && $row->leave_type) {
            $payload['leave_approved_by'] = $row->resolved_by ?: $userId;
            $payload['leave_approved_at'] = $row->resolved_at ?: now();
        }

        if ($existing) {
            $existing->fill($payload);
            $existing->save();

            return;
        }

        $payload['created_by'] = $userId;
        EmployeeAttendance::create($payload);
    }

    protected function refreshImportStatistics(
        AttendanceImport $attendanceImport
    ): void {
        $baseQuery = $attendanceImport->rows();

        $statistics = [
            'total_rows' => (clone $baseQuery)->count(),
            'imported_rows' => (clone $baseQuery)
                ->where('source', AttendanceImportRow::SOURCE_EXCEL)
                ->count(),
            'generated_rows' => (clone $baseQuery)
                ->where('source', AttendanceImportRow::SOURCE_GENERATED_GAP)
                ->count(),
            'valid_rows' => (clone $baseQuery)
                ->whereIn('review_status', [
                    AttendanceImportRow::REVIEW_VALID,
                    AttendanceImportRow::REVIEW_RESOLVED,
                    AttendanceImportRow::REVIEW_IGNORED,
                ])
                ->count(),
            'review_rows' => (clone $baseQuery)
                ->where('review_status', AttendanceImportRow::REVIEW_NEEDS_REVIEW)
                ->count(),
            'error_rows' => (clone $baseQuery)
                ->where('review_status', AttendanceImportRow::REVIEW_ERROR)
                ->count(),
            'duplicate_rows' => (clone $baseQuery)
                ->where('review_status', AttendanceImportRow::REVIEW_DUPLICATE)
                ->count(),
        ];

        $attendanceImport->update($statistics);
    }

    protected function buildImportSummary(
        AttendanceImport $attendanceImport
    ): array {
        $systemRows = $attendanceImport->rows()
            ->whereNotNull('raw_payload')
            ->get([
                'review_status',
                'raw_payload',
            ]);

        return [
            'employees' => $attendanceImport->rows()
                ->whereNotNull('employee_id')
                ->distinct('employee_id')
                ->count('employee_id'),

            'working_templates' => $attendanceImport->rows()
                ->whereNotNull('working_hour_template_id')
                ->distinct('working_hour_template_id')
                ->count('working_hour_template_id'),

            'present_rows' => $attendanceImport->rows()
                ->where('attendance_type', 'present')
                ->count(),

            'leave_rows' => $attendanceImport->rows()
                ->whereNotNull('leave_type')
                ->count(),

            'holiday_rows' => $attendanceImport->rows()
                ->where('attendance_type', 'holiday')
                ->count(),

            'missing_rows' => $attendanceImport->rows()
                ->where('attendance_type', 'missing')
                ->count(),

            'auto_clock_out_rows' => $systemRows
                ->filter(
                    fn (AttendanceImportRow $row) => (bool) data_get(
                        $row->raw_payload,
                        '_system.auto_clock_out',
                        false
                    )
                )
                ->count(),

            'exact_duplicate_rows' => $systemRows
                ->filter(
                    fn (AttendanceImportRow $row) =>
                        data_get(
                            $row->raw_payload,
                            '_system.duplicate.type'
                        ) === 'exact'
                        && data_get(
                            $row->raw_payload,
                            '_system.duplicate.role'
                        ) === 'ignored_exact'
                )
                ->count(),

            'conflicting_duplicate_rows' => $systemRows
                ->filter(
                    fn (AttendanceImportRow $row) =>
                        $row->review_status
                            === AttendanceImportRow::REVIEW_DUPLICATE
                        && data_get(
                            $row->raw_payload,
                            '_system.duplicate.type'
                        ) === 'conflict'
                )
                ->count(),

            'resolved_duplicate_rows' => $systemRows
                ->filter(
                    fn (AttendanceImportRow $row) =>
                        data_get(
                            $row->raw_payload,
                            '_system.duplicate.type'
                        ) === 'resolved_conflict'
                )
                ->count(),

            'late_rows' => $attendanceImport->rows()
                ->whereIn('arrival_status', [
                    'late',
                    'excused_late',
                ])
                ->count(),
        ];
    }

    protected function normalizeSettings(array $settings): array
    {
        $workingDays = $this->normalizeWorkingDays(
            $settings['default_working_days']
                ?? self::DEFAULT_WORKING_DAYS
        );

        $duplicateAction = (string) (
            $settings['duplicate_action']
                ?? 'update'
        );

        if (! in_array($duplicateAction, ['update', 'skip', 'error'], true)) {
            $duplicateAction = 'update';
        }

        return [
            'sheet_name' => trim((string) (
                $settings['sheet_name']
                    ?? self::DEFAULT_SHEET_NAME
            )),
            'default_working_days' => $workingDays,
            'default_start_time' => $this->normalizeTime(
                $settings['default_start_time']
                    ?? self::DEFAULT_START_TIME
            ) ?: self::DEFAULT_START_TIME,
            'default_end_time' => $this->normalizeTime(
                $settings['default_end_time']
                    ?? self::DEFAULT_END_TIME
            ) ?: self::DEFAULT_END_TIME,
            'late_tolerance_minutes' => max(
                (int) ($settings['late_tolerance_minutes'] ?? 0),
                0
            ),
            'duplicate_action' => $duplicateAction,
            'generate_missing_rows' => filter_var(
                $settings['generate_missing_rows'] ?? true,
                FILTER_VALIDATE_BOOL
            ),
            'generate_holiday_rows' => filter_var(
                $settings['generate_holiday_rows'] ?? true,
                FILTER_VALIDATE_BOOL
            ),
            'auto_clock_out_missing' => filter_var(
                $settings['auto_clock_out_missing'] ?? true,
                FILTER_VALIDATE_BOOL
            ),
            'include_future_dates' => filter_var(
                $settings['include_future_dates'] ?? false,
                FILTER_VALIDATE_BOOL
            ),
            'template_defaults' => is_array($settings['template_defaults'] ?? null)
                ? $settings['template_defaults']
                : [],
        ];
    }

    protected function normalizeWorkingDays(mixed $days): array
    {
        return collect(is_array($days) ? $days : self::DEFAULT_WORKING_DAYS)
            ->map(fn ($day) => (int) $day)
            ->filter(fn (int $day) => $day >= 1 && $day <= 7)
            ->unique()
            ->sort()
            ->values()
            ->all();
    }

    protected function assertSupportedFile(UploadedFile $file): void
    {
        $extension = Str::lower($file->getClientOriginalExtension());

        if (! in_array($extension, ['xlsx', 'xls'], true)) {
            throw ValidationException::withMessages([
                'file' => 'File attendance harus berformat .xlsx atau .xls.',
            ]);
        }
    }

    protected function storeUploadedFile(UploadedFile $file): string
    {
        $directory = 'attendance-imports/' . now()->format('Y/m');
        $extension = Str::lower($file->getClientOriginalExtension());
        $fileName = now()->format('YmdHis')
            . '_'
            . Str::uuid()
            . '.'
            . $extension;

        $storedPath = $file->storeAs(
            $directory,
            $fileName,
            'local'
        );

        if (! $storedPath) {
            throw ValidationException::withMessages([
                'file' => 'File attendance gagal disimpan.',
            ]);
        }

        return $storedPath;
    }

    protected function normalizeDate(mixed $value): ?string
    {
        if ($value instanceof DateTimeInterface) {
            return Carbon::instance($value)->toDateString();
        }

        if (is_numeric($value)) {
            try {
                return Carbon::instance(
                    ExcelDate::excelToDateTimeObject((float) $value)
                )->toDateString();
            } catch (Throwable) {
                return null;
            }
        }

        $text = trim((string) $value);

        if ($text === '' || str_starts_with($text, '#')) {
            return null;
        }

        try {
            return Carbon::parse($text)->toDateString();
        } catch (Throwable) {
            return null;
        }
    }

    protected function normalizeTime(mixed $value): ?string
    {
        if ($value instanceof DateTimeInterface) {
            return Carbon::instance($value)->format('H:i:s');
        }

        if (is_numeric($value)) {
            try {
                return Carbon::instance(
                    ExcelDate::excelToDateTimeObject((float) $value)
                )->format('H:i:s');
            } catch (Throwable) {
                return null;
            }
        }

        $text = trim((string) $value);

        if (
            $text === ''
            || str_starts_with($text, '#')
            || str_starts_with($text, '=')
        ) {
            return null;
        }

        foreach (['H:i:s', 'H:i', 'h:i A', 'h:i:s A'] as $format) {
            try {
                return Carbon::createFromFormat($format, $text)->format('H:i:s');
            } catch (Throwable) {
                // Try the next format.
            }
        }

        try {
            return Carbon::parse($text)->format('H:i:s');
        } catch (Throwable) {
            return null;
        }
    }

    protected function normalizeText(mixed $value): ?string
    {
        if ($value === null) {
            return null;
        }

        if (is_bool($value)) {
            return $value ? '1' : '0';
        }

        $text = trim((string) $value);

        return $text !== '' ? $text : null;
    }

    protected function normalizeEmployeeNumber(mixed $value): ?string
    {
        if (is_int($value)) {
            return (string) $value;
        }

        if (is_float($value)) {
            return rtrim(rtrim(number_format($value, 10, '.', ''), '0'), '.');
        }

        $text = trim((string) $value);

        if ($text === '' || $this->looksLikeLeaveText($text)) {
            return null;
        }

        return $text;
    }

    protected function isValidEmployeeNumber(mixed $value): bool
    {
        $text = trim((string) $value);

        if ($text === '' || $this->looksLikeLeaveText($text)) {
            return false;
        }

        /*
        | Current Evertime export uses numeric IDs. Requiring at least one digit
        | prevents phrases such as "Sick Leave" from becoming employee records,
        | while still allowing future alphanumeric IDs such as EMP-001.
        */
        return (bool) preg_match('/\d/', $text);
    }

    protected function detectLeaveInformation(
        array $rawRow,
        mixed $employeeNumberRaw,
        ?string $clockIn,
        ?string $clockOut
    ): array {
        $texts = collect([
            $employeeNumberRaw,
            $rawRow['reason'] ?? null,
            $rawRow['reason_2'] ?? null,
            $rawRow['remarks'] ?? null,
            $rawRow['status'] ?? null,
            $rawRow['status2'] ?? null,
        ])
            ->filter(fn ($value) => $value !== null && trim((string) $value) !== '')
            ->map(fn ($value) => Str::lower(trim((string) $value)))
            ->values();

        $combined = $texts->implode(' | ');

        $leaveType = match (true) {
            str_contains($combined, 'unpaid leave') => 'unpaid_leave',
            str_contains($combined, 'sick leave'),
            preg_match('/\bsick\b/', $combined) === 1,
            str_contains($combined, 'sakit') => 'sick_leave',
            str_contains($combined, 'annual leave'),
            str_contains($combined, 'paid leave'),
            str_contains($combined, 'cuti tahunan') => 'annual_leave',
            str_contains($combined, 'permission'),
            str_contains($combined, 'permit'),
            str_contains($combined, 'izin') => 'permission',
            str_contains($combined, 'leave'),
            str_contains($combined, 'cuti') => 'other',
            default => null,
        };

        $hasHalfDay = str_contains($combined, 'half day')
            || str_contains($combined, 'half-day')
            || str_contains($combined, 'setengah hari');

        $leaveSession = match (true) {
            str_contains($combined, 'late arrival'),
            str_contains($combined, 'izin terlambat'),
            str_contains($combined, 'datang terlambat') => 'late_arrival',
            str_contains($combined, 'early departure'),
            str_contains($combined, 'pulang cepat'),
            str_contains($combined, 'pulang awal') => 'early_departure',
            str_contains($combined, 'first half'),
            str_contains($combined, 'morning leave') => 'first_half',
            str_contains($combined, 'second half'),
            str_contains($combined, 'afternoon leave') => 'second_half',
            default => null,
        };

        $leaveWasDetected = $leaveType !== null;
        $hasAttendanceTime = $clockIn !== null || $clockOut !== null;

        if (! $leaveWasDetected) {
            return [
                'attendance_type' => 'present',
                'leave_type' => null,
                'leave_duration' => null,
                'leave_session' => null,
                'is_excused' => false,
                'leave_reason' => null,
                'leave_was_detected' => false,
                'leave_requires_review' => false,
            ];
        }

        $leaveDuration = $hasHalfDay || $hasAttendanceTime
            ? 'half_day'
            : 'full_day';

        $attendanceType = $leaveDuration === 'full_day'
            ? 'absent'
            : 'present';

        $requiresReview = $leaveType === 'other'
            || ($leaveDuration === 'half_day' && $leaveSession === null);

        return [
            'attendance_type' => $attendanceType,
            'leave_type' => $leaveType,
            'leave_duration' => $leaveDuration,
            'leave_session' => $leaveSession,
            'is_excused' => true,
            'leave_reason' => $combined,
            'leave_was_detected' => true,
            'leave_requires_review' => $requiresReview,
        ];
    }

    protected function looksLikeLeaveText(string $text): bool
    {
        $text = Str::lower(trim($text));

        return str_contains($text, 'leave')
            || str_contains($text, 'sick')
            || str_contains($text, 'permission')
            || str_contains($text, 'permit')
            || str_contains($text, 'cuti')
            || str_contains($text, 'sakit')
            || str_contains($text, 'izin')
            || str_contains($text, 'absent');
    }

    protected function normalizeRawPunctuality(?string $status): string
    {
        $status = Str::lower(trim((string) $status));

        if ($status === '' || str_starts_with($status, '#')) {
            return 'unknown';
        }

        if (
            str_contains($status, 'no late')
            || str_contains($status, 'on time')
        ) {
            return 'on_time';
        }

        if (str_contains($status, 'late')) {
            return 'late';
        }

        return 'unknown';
    }

    protected function calculateMinutesBetween(
        ?string $startTime,
        ?string $endTime
    ): ?int {
        if (! $startTime || ! $endTime) {
            return null;
        }

        try {
            $start = Carbon::createFromFormat('H:i:s', $startTime);
            $end = Carbon::createFromFormat('H:i:s', $endTime);

            if ($end->lessThan($start)) {
                $end->addDay();
            }

            return $start->diffInMinutes($end);
        } catch (Throwable) {
            return null;
        }
    }

    protected function timeDifferenceMinutes(
        string $startTime,
        string $endTime
    ): int {
        try {
            $start = Carbon::createFromFormat(
                'H:i:s',
                $startTime
            );

            $end = Carbon::createFromFormat(
                'H:i:s',
                $endTime
            );

            /*
            |--------------------------------------------------------------------------
            | Signed Time Difference
            |--------------------------------------------------------------------------
            |
            | Jangan addDay di sini.
            |
            | Method ini digunakan untuk membandingkan waktu aktual dengan jadwal:
            |
            | 08:00 → 08:15 = 15
            | 08:00 → 07:45 = -15
            |
            | Nilai negatif nantinya diubah menjadi 0 oleh resolvePunctuality().
            |
            */
            return (int) $start->diffInMinutes(
                $end,
                false
            );
        } catch (Throwable) {
            return 0;
        }
    }

    protected function firstUsefulValue(array $values): mixed
    {
        foreach ($values as $value) {
            $text = trim((string) $value);

            if (
                $value !== null
                && $text !== ''
                && ! str_starts_with($text, '#')
            ) {
                return $value;
            }
        }

        return null;
    }

    protected function normalizeRawPayload(array $rawRow): array
    {
        return collect($rawRow)
            ->reject(fn ($value, $key) => $key === '_row_number')
            ->map(function ($value) {
                if ($value instanceof DateTimeInterface) {
                    return Carbon::instance($value)->toIso8601String();
                }

                return $value;
            })
            ->all();
    }

    protected function buildSourceRowKey(
        ?string $attendanceDate,
        ?string $employeeName,
        ?string $employeeNumber,
        int $rowNumber
    ): string {
        return hash('sha256', implode('|', [
            $attendanceDate ?: 'no-date',
            Employee::normalizeName($employeeName),
            $employeeNumber ?: 'no-number',
            $rowNumber,
        ]));
    }
}
