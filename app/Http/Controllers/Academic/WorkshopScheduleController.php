<?php

namespace App\Http\Controllers\Academic;

use App\Http\Controllers\Controller;
use App\Models\Workshop;
use App\Models\WorkshopSchedule;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class WorkshopScheduleController extends Controller
{
    public function index(Request $request): View|JsonResponse
    {
        if ($request->expectsJson() || $request->ajax()) {
            return $this->jsonIndex($request);
        }

        $workshops = Workshop::query()
            ->where('is_active', true)
            ->orderBy('title')
            ->get([
                'id',
                'title',
                'price',
                'old_price',
            ]);

        return view('academic.workshop-schedules.index', compact('workshops'));
    }

    public function show(WorkshopSchedule $workshopSchedule): JsonResponse
    {
        $workshopSchedule->load('workshop:id,title,price,old_price');

        return response()->json([
            'success' => true,
            'data' => $this->transformSchedule($workshopSchedule),
        ]);
    }

    public function store(Request $request): JsonResponse
    {
        $validated = $this->validateSchedule($request);

        $workshop = Workshop::query()
            ->select(['id', 'title', 'price', 'old_price'])
            ->findOrFail($validated['workshop_id']);

        /*
        |--------------------------------------------------------------------------
        | Default Price From Workshop
        |--------------------------------------------------------------------------
        | Kalau admin tidak isi harga di schedule, otomatis ambil dari harga
        | default workshop. Tapi kalau admin isi manual, pakai harga schedule.
        |--------------------------------------------------------------------------
        */
        $validated['price'] = $validated['price'] ?? $workshop->price;
        $validated['old_price'] = $validated['old_price'] ?? $workshop->old_price;

        $validated['registered_count'] = $validated['registered_count'] ?? 0;
        $validated['sort_order'] = $validated['sort_order'] ?? 0;
        $validated['is_active'] = $request->boolean('is_active', true);

        $schedule = WorkshopSchedule::create($validated);
        $schedule->load('workshop:id,title,price,old_price');

        return response()->json([
            'success' => true,
            'message' => 'Jadwal workshop berhasil ditambahkan.',
            'data' => $this->transformSchedule($schedule),
        ]);
    }

    public function update(Request $request, WorkshopSchedule $workshopSchedule): JsonResponse
    {
        $validated = $this->validateSchedule($request, $workshopSchedule);

        $workshop = Workshop::query()
            ->select(['id', 'title', 'price', 'old_price'])
            ->findOrFail($validated['workshop_id']);

        $validated['price'] = $validated['price'] ?? $workshop->price;
        $validated['old_price'] = $validated['old_price'] ?? $workshop->old_price;

        $validated['registered_count'] = $validated['registered_count'] ?? $workshopSchedule->registered_count ?? 0;
        $validated['sort_order'] = $validated['sort_order'] ?? 0;
        $validated['is_active'] = $request->boolean('is_active');

        $workshopSchedule->update($validated);
        $workshopSchedule->refresh()->load('workshop:id,title,price,old_price');

        return response()->json([
            'success' => true,
            'message' => 'Jadwal workshop berhasil diperbarui.',
            'data' => $this->transformSchedule($workshopSchedule),
        ]);
    }

    public function destroy(WorkshopSchedule $workshopSchedule): JsonResponse
    {
        if ($workshopSchedule->participants()->exists()) {
            return response()->json([
                'success' => false,
                'message' => 'Jadwal workshop tidak bisa dihapus karena sudah memiliki peserta.',
            ], 422);
        }

        $workshopSchedule->delete();

        return response()->json([
            'success' => true,
            'message' => 'Jadwal workshop berhasil dihapus.',
        ]);
    }

    public function workshopPricing(Workshop $workshop): JsonResponse
    {
        return response()->json([
            'success' => true,
            'data' => [
                'id' => $workshop->id,
                'title' => $workshop->title,
                'price' => $workshop->price,
                'old_price' => $workshop->old_price,
                'formatted_price' => $this->formatRupiah($workshop->price),
                'formatted_old_price' => $this->formatRupiah($workshop->old_price),
            ],
        ]);
    }

    private function jsonIndex(Request $request): JsonResponse
    {
        $perPage = (int) $request->get('per_page', 10);

        if (! in_array($perPage, [10, 25, 50, 100], true)) {
            $perPage = 10;
        }

        $query = WorkshopSchedule::query()
            ->with('workshop:id,title,price,old_price')
            ->when($request->filled('workshop_id'), function ($query) use ($request) {
                $query->where('workshop_id', $request->integer('workshop_id'));
            })
            ->when($request->filled('status'), function ($query) use ($request) {
                $query->where('status', $request->get('status'));
            })
            ->when($request->filled('location_type'), function ($query) use ($request) {
                $query->where('location_type', $request->get('location_type'));
            })
            ->when($request->filled('is_active'), function ($query) use ($request) {
                $query->where('is_active', $request->boolean('is_active'));
            })
            ->when($request->filled('search'), function ($query) use ($request) {
                $search = trim((string) $request->get('search'));

                $query->where(function ($query) use ($search) {
                    $query
                        ->where('title', 'like', "%{$search}%")
                        ->orWhere('location', 'like', "%{$search}%")
                        ->orWhereHas('workshop', function ($query) use ($search) {
                            $query->where('title', 'like', "%{$search}%");
                        });
                });
            })
            ->orderBy('schedule_date')
            ->orderBy('start_time')
            ->orderBy('sort_order')
            ->latest();

        $schedules = $query
            ->paginate($perPage)
            ->withQueryString();

        return response()->json([
            'success' => true,
            'data' => $schedules->through(fn (WorkshopSchedule $schedule) => $this->transformSchedule($schedule)),
        ]);
    }

    private function validateSchedule(Request $request, ?WorkshopSchedule $workshopSchedule = null): array
    {
        $validated = $request->validate([
            'workshop_id' => [
                'required',
                'integer',
                Rule::exists('workshops', 'id'),
            ],
            'title' => ['nullable', 'string', 'max:255'],
            'schedule_date' => ['required', 'date'],
            'start_time' => ['nullable', 'date_format:H:i'],
            'end_time' => ['nullable', 'date_format:H:i'],

            'location_type' => [
                'required',
                Rule::in(['online', 'offline', 'hybrid']),
            ],
            'location' => ['nullable', 'string', 'max:255'],
            'meeting_url' => ['nullable', 'string', 'max:1000'],

            'quota' => ['nullable', 'integer', 'min:1'],
            'registered_count' => ['nullable', 'integer', 'min:0'],

            'price' => ['nullable', 'numeric', 'min:0'],
            'old_price' => ['nullable', 'numeric', 'min:0'],

            'status' => [
                'required',
                Rule::in([
                    'draft',
                    'open',
                    'closed',
                    'completed',
                    'cancelled',
                ]),
            ],

            'notes' => ['nullable', 'string'],
            'is_active' => ['nullable', 'boolean'],
            'sort_order' => ['nullable', 'integer', 'min:0'],
        ], [
            'workshop_id.required' => 'Workshop wajib dipilih.',
            'workshop_id.exists' => 'Workshop yang dipilih tidak valid.',
            'schedule_date.required' => 'Tanggal jadwal wajib diisi.',
            'schedule_date.date' => 'Tanggal jadwal tidak valid.',
            'start_time.date_format' => 'Format jam mulai harus HH:MM.',
            'end_time.date_format' => 'Format jam selesai harus HH:MM.',
            'location_type.required' => 'Tipe lokasi wajib dipilih.',
            'location_type.in' => 'Tipe lokasi tidak valid.',
            'quota.integer' => 'Kuota harus berupa angka.',
            'quota.min' => 'Kuota minimal 1.',
            'price.numeric' => 'Harga harus berupa angka.',
            'old_price.numeric' => 'Harga lama harus berupa angka.',
            'status.required' => 'Status wajib dipilih.',
            'status.in' => 'Status tidak valid.',
        ]);

        $this->validateTimeRange($validated);
        $this->validateQuota($validated, $workshopSchedule);

        return $validated;
    }

    private function validateTimeRange(array $validated): void
    {
        $startTime = $validated['start_time'] ?? null;
        $endTime = $validated['end_time'] ?? null;

        if (! $startTime || ! $endTime) {
            return;
        }

        if ($endTime <= $startTime) {
            throw ValidationException::withMessages([
                'end_time' => 'Jam selesai harus lebih besar dari jam mulai.',
            ]);
        }
    }

    private function validateQuota(array $validated, ?WorkshopSchedule $workshopSchedule = null): void
    {
        $quota = $validated['quota'] ?? null;

        if (! $quota) {
            return;
        }

        $registeredCount = $validated['registered_count']
            ?? $workshopSchedule?->registered_count
            ?? 0;

        if ($registeredCount > $quota) {
            throw ValidationException::withMessages([
                'quota' => 'Kuota tidak boleh lebih kecil dari jumlah peserta yang sudah terdaftar.',
            ]);
        }
    }

    private function transformSchedule(WorkshopSchedule $schedule): array
    {
        $effectivePrice = $schedule->price ?? $schedule->workshop?->price;
        $effectiveOldPrice = $schedule->old_price ?? $schedule->workshop?->old_price;

        return [
            'id' => $schedule->id,
            'workshop_id' => $schedule->workshop_id,
            'workshop' => $schedule->workshop ? [
                'id' => $schedule->workshop->id,
                'title' => $schedule->workshop->title,
                'price' => $schedule->workshop->price,
                'old_price' => $schedule->workshop->old_price,
                'formatted_price' => $this->formatRupiah($schedule->workshop->price),
                'formatted_old_price' => $this->formatRupiah($schedule->workshop->old_price),
            ] : null,

            'title' => $schedule->title,
            'display_title' => $schedule->title ?: $schedule->workshop?->title,

            'schedule_date' => optional($schedule->schedule_date)->format('Y-m-d'),
            'schedule_date_label' => optional($schedule->schedule_date)->translatedFormat('d M Y'),

            'start_time' => $this->formatTime($schedule->start_time),
            'end_time' => $this->formatTime($schedule->end_time),
            'time_label' => $this->makeTimeLabel($schedule->start_time, $schedule->end_time),

            'location_type' => $schedule->location_type,
            'location_type_label' => $this->locationTypeLabel($schedule->location_type),
            'location' => $schedule->location,
            'meeting_url' => $schedule->meeting_url,

            'quota' => $schedule->quota,
            'registered_count' => $schedule->registered_count,
            'remaining_quota' => $schedule->quota
                ? max($schedule->quota - $schedule->registered_count, 0)
                : null,

            'price' => $schedule->price,
            'old_price' => $schedule->old_price,
            'effective_price' => $effectivePrice,
            'effective_old_price' => $effectiveOldPrice,
            'formatted_price' => $this->formatRupiah($effectivePrice),
            'formatted_old_price' => $this->formatRupiah($effectiveOldPrice),

            'status' => $schedule->status,
            'status_label' => $this->statusLabel($schedule->status),

            'notes' => $schedule->notes,
            'is_active' => (bool) $schedule->is_active,
            'sort_order' => $schedule->sort_order,

            'created_at' => optional($schedule->created_at)->format('Y-m-d H:i:s'),
            'updated_at' => optional($schedule->updated_at)->format('Y-m-d H:i:s'),
        ];
    }

    private function formatTime(?string $time): ?string
    {
        if (! $time) {
            return null;
        }

        return substr($time, 0, 5);
    }

    private function makeTimeLabel(?string $startTime, ?string $endTime): string
    {
        $start = $this->formatTime($startTime);
        $end = $this->formatTime($endTime);

        if ($start && $end) {
            return "{$start} - {$end}";
        }

        return $start ?: ($end ?: '-');
    }

    private function locationTypeLabel(?string $locationType): string
    {
        return match ($locationType) {
            'online' => 'Online',
            'offline' => 'Offline',
            'hybrid' => 'Hybrid',
            default => '-',
        };
    }

    private function statusLabel(?string $status): string
    {
        return match ($status) {
            'draft' => 'Draft',
            'open' => 'Open',
            'closed' => 'Closed',
            'completed' => 'Completed',
            'cancelled' => 'Cancelled',
            default => '-',
        };
    }

    private function formatRupiah(null|int|float|string $amount): ?string
    {
        if ($amount === null || $amount === '') {
            return null;
        }

        return 'Rp ' . number_format((float) $amount, 0, ',', '.');
    }
}