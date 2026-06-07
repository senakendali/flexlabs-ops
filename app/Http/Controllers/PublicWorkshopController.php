<?php

namespace App\Http\Controllers;

use App\Models\Workshop;
use App\Models\WorkshopSchedule;
use Illuminate\Support\Carbon;

class PublicWorkshopController extends Controller
{
    public function index()
    {
        $today = now()->toDateString();

        $workshops = Workshop::query()
            ->where('is_active', true)
            ->with([
                'benefits' => fn ($query) => $query->orderBy('sort_order'),
                'schedules' => fn ($query) => $this->availableScheduleQuery($query, $today),
            ])
            ->orderBy('sort_order')
            ->orderBy('title')
            ->get()
            ->map(fn (Workshop $workshop) => $this->transformWorkshop($workshop));

        return view('public.workshop.index', [
            'workshops' => $workshops,
        ]);
    }

    public function show(string $slug)
    {
        $today = now()->toDateString();

        $workshop = Workshop::query()
            ->where('is_active', true)
            ->with([
                'benefits' => fn ($query) => $query->orderBy('sort_order'),
                'schedules' => fn ($query) => $this->availableScheduleQuery($query, $today),
            ])
            ->where('slug', $slug)
            ->firstOrFail();

        return view('public.workshop.show', [
            'workshop' => $this->transformWorkshop($workshop),
        ]);
    }

    private function availableScheduleQuery($query, string $today)
    {
        return $query
            ->where('is_active', true)
            ->where('status', 'open')
            ->whereDate('schedule_date', '>=', $today)
            ->orderBy('schedule_date')
            ->orderBy('start_time')
            ->orderBy('sort_order');
    }

    private function transformWorkshop(Workshop $workshop): array
    {
        $schedules = $workshop->schedules
            ->map(fn (WorkshopSchedule $schedule) => $this->transformSchedule($schedule, $workshop))
            ->values();

        $nearestSchedule = $schedules->first();

        return [
            'id' => $workshop->id,
            'slug' => $workshop->slug,
            'title' => $workshop->title,
            'badge' => $workshop->badge,
            'short_description' => $workshop->short_description,
            'overview' => $workshop->overview,

            /*
            |--------------------------------------------------------------------------
            | Default Workshop Price
            |--------------------------------------------------------------------------
            | Ini tetap harga default dari tabel workshops.
            | Kalau jadwal punya harga khusus, datanya ada di schedules.*.price.
            |--------------------------------------------------------------------------
            */
            'price' => (float) $workshop->price,
            'old_price' => $workshop->old_price !== null ? (float) $workshop->old_price : null,
            'formatted_price' => $this->formatRupiah($workshop->price),
            'formatted_old_price' => $this->formatRupiah($workshop->old_price),

            'rating' => (int) $workshop->rating,
            'rating_count' => (int) $workshop->rating_count,
            'duration' => $workshop->duration,
            'level' => $workshop->level,
            'category' => $workshop->category,
            'audience' => $workshop->audience,
            'image' => $workshop->image ?: 'images/hero.png',
            'intro_video_type' => $workshop->intro_video_type ?: 'youtube',
            'intro_video_url' => $workshop->intro_video_url,

            'benefits' => $workshop->benefits
                ->pluck('content')
                ->values()
                ->all(),

            /*
            |--------------------------------------------------------------------------
            | Available Schedules
            |--------------------------------------------------------------------------
            | Dipakai public page untuk:
            | - menampilkan jadwal tersedia
            | - dropdown pilih jadwal saat registrasi
            | - mengambil harga jadwal kalau berbeda dari harga workshop
            |--------------------------------------------------------------------------
            */
            'schedules' => $schedules->all(),
            'available_schedule_count' => $schedules->count(),
            'has_available_schedules' => $schedules->isNotEmpty(),
            'nearest_schedule' => $nearestSchedule,
        ];
    }

    private function transformSchedule(WorkshopSchedule $schedule, Workshop $workshop): array
    {
        $effectivePrice = $schedule->price !== null
            ? (float) $schedule->price
            : (float) $workshop->price;

        $effectiveOldPrice = $schedule->old_price !== null
            ? (float) $schedule->old_price
            : ($workshop->old_price !== null ? (float) $workshop->old_price : null);

        $scheduleDate = $schedule->schedule_date
            ? Carbon::parse($schedule->schedule_date)
            : null;

        $startTime = $this->formatTime($schedule->start_time);
        $endTime = $this->formatTime($schedule->end_time);

        $timeLabel = trim(implode(' - ', array_filter([
            $startTime,
            $endTime,
        ])));

        $quota = $schedule->quota !== null ? (int) $schedule->quota : null;
        $registeredCount = (int) ($schedule->registered_count ?? 0);

        return [
            'id' => $schedule->id,
            'workshop_id' => $schedule->workshop_id,

            'title' => $schedule->title,
            'display_title' => $schedule->title ?: $workshop->title,

            'schedule_date' => $scheduleDate?->format('Y-m-d'),
            'schedule_date_label' => $scheduleDate?->format('d M Y'),
            'schedule_day_label' => $scheduleDate?->format('l'),

            'start_time' => $startTime,
            'end_time' => $endTime,
            'time_label' => $timeLabel ?: '-',

            'location_type' => $schedule->location_type,
            'location_type_label' => $this->locationTypeLabel($schedule->location_type),
            'location' => $schedule->location,
            'meeting_url' => $schedule->meeting_url,

            'quota' => $quota,
            'registered_count' => $registeredCount,
            'remaining_quota' => $quota !== null
                ? max($quota - $registeredCount, 0)
                : null,
            'is_full' => $quota !== null && $registeredCount >= $quota,

            /*
            |--------------------------------------------------------------------------
            | Schedule Price
            |--------------------------------------------------------------------------
            | price/old_price adalah harga efektif untuk jadwal public.
            | raw_price/raw_old_price adalah isi asli dari tabel workshop_schedules.
            |--------------------------------------------------------------------------
            */
            'price' => $effectivePrice,
            'old_price' => $effectiveOldPrice,
            'raw_price' => $schedule->price !== null ? (float) $schedule->price : null,
            'raw_old_price' => $schedule->old_price !== null ? (float) $schedule->old_price : null,
            'formatted_price' => $this->formatRupiah($effectivePrice),
            'formatted_old_price' => $this->formatRupiah($effectiveOldPrice),

            'status' => $schedule->status,
            'status_label' => $this->statusLabel($schedule->status),
            'is_active' => (bool) $schedule->is_active,
            'sort_order' => (int) ($schedule->sort_order ?? 0),
            'notes' => $schedule->notes,
        ];
    }

    private function formatTime($time): ?string
    {
        if (! $time) {
            return null;
        }

        return substr((string) $time, 0, 5);
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
