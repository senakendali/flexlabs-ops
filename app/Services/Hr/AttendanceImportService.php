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

                if ((bool) $settings['generate_missing_rows']) {
                    $this->generateMissingAttendanceRows(
                        attendanceImport: $attendanceImport,
                        employees: $context['employees']->values(),
                        settings: $settings
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
        $this->assertImportIsEditable($row->attendanceImport);

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

        $this->refreshImportStatistics($row->attendanceImport);

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
            $context['employees']->put((string) $employee->id, $employee);
        }

        $template = null;

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
        }

        if ($employee && $template) {
            $context['template_usage'][$employee->id][$template->id] =
                ($context['template_usage'][$employee->id][$template->id] ?? 0) + 1;
        }

        $schedule = $this->resolveScheduleSnapshot(
            template: $template,
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

        $duplicate = $this->findExistingStagingDuplicate(
            attendanceImport: $attendanceImport,
            employee: $employee,
            attendanceDate: $row['attendance_date']
        );

        if ($duplicate) {
            $review = [
                'review_status' => AttendanceImportRow::REVIEW_DUPLICATE,
                'validation_message' => 'Duplicate employee dan attendance date ditemukan dalam file yang sama.',
            ];
        }

        return AttendanceImportRow::create([
            'attendance_import_id' => $attendanceImport->id,
            'employee_id' => $employee?->id,
            'working_hour_template_id' => $template?->id,
            'working_hours_template_raw' => $row['working_hours_template_raw'],

            'row_number' => $row['row_number'],
            'source_row_key' => $row['source_row_key'],
            'attendance_date' => $row['attendance_date'],

            'employee_number_raw' => $row['employee_number_raw'],
            'employee_name_raw' => $row['employee_name_raw'],
            'employee_number' => $employee?->employee_number ?? $row['employee_number'],
            'employee_name' => $employee?->name ?? $row['employee_name'],

            'clock_in' => $row['clock_in'],
            'clock_out' => $row['clock_out'],
            'scheduled_start_time' => $schedule['scheduled_start_time'],
            'scheduled_end_time' => $schedule['scheduled_end_time'],
            'scheduled_work_minutes' => $schedule['scheduled_work_minutes'],
            'worked_minutes' => $this->calculateMinutesBetween(
                $row['clock_in'],
                $row['clock_out']
            ),
            'schedule_source' => $template ? 'excel' : 'unknown',
            'schedule_is_inferred' => false,

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
    protected function generateMissingAttendanceRows(
        AttendanceImport $attendanceImport,
        Collection $employees,
        array $settings
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

        $holidayDates = $this->getCompanyHolidayDates($dateFrom, $dateTo);

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

                if (! in_array($date->dayOfWeekIso, $workingDays, true)) {
                    continue;
                }

                if ($holidayDates->has($dateString)) {
                    continue;
                }

                if ($actualDates->has($dateString)) {
                    continue;
                }

                AttendanceImportRow::create([
                    'attendance_import_id' => $attendanceImport->id,
                    'employee_id' => $employee->id,
                    'working_hour_template_id' => $template?->id,
                    'working_hours_template_raw' => $template?->name,

                    'attendance_date' => $dateString,
                    'employee_number' => $employee->employee_number,
                    'employee_name' => $employee->name,
                    'employee_name_raw' => $employee->name,
                    'employee_number_raw' => $employee->employee_number,

                    'scheduled_start_time' => $schedule['scheduled_start_time'],
                    'scheduled_end_time' => $schedule['scheduled_end_time'],
                    'scheduled_work_minutes' => $schedule['scheduled_work_minutes'],
                    'schedule_source' => $template ? 'employee_default' : 'default_setting',
                    'schedule_is_inferred' => true,

                    'attendance_type' => 'missing',
                    'punctuality_status' => 'not_applicable',
                    'arrival_status' => 'not_applicable',
                    'departure_status' => 'not_applicable',

                    'source' => AttendanceImportRow::SOURCE_GENERATED_GAP,
                    'review_status' => AttendanceImportRow::REVIEW_NEEDS_REVIEW,
                    'validation_message' => 'Tidak ditemukan data attendance pada hari kerja. HR perlu memilih sakit, cuti, izin, absent, off day, holiday, atau data issue.',
                ]);
            }
        }
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

    protected function getCompanyHolidayDates(
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
            ->pluck('holiday_date')
            ->map(fn ($date) => Carbon::parse($date)->toDateString())
            ->flip();
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
            && (bool) data_get($template->metadata, 'requires_configuration')
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

    protected function findExistingStagingDuplicate(
        AttendanceImport $attendanceImport,
        ?Employee $employee,
        ?string $attendanceDate
    ): ?AttendanceImportRow {
        if (! $employee || ! $attendanceDate) {
            return null;
        }

        return $attendanceImport->rows()
            ->where('employee_id', $employee->id)
            ->whereDate('attendance_date', $attendanceDate)
            ->first();
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
            'missing_rows' => $attendanceImport->rows()
                ->where('attendance_type', 'missing')
                ->count(),
            'late_rows' => $attendanceImport->rows()
                ->whereIn('arrival_status', ['late', 'excused_late'])
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
