<?php

namespace App\Http\Controllers;

use App\Models\Order;
use App\Models\PaymentSchedule;
use App\Models\Student;
use App\Models\Workshop;
use App\Models\WorkshopParticipant;
use App\Models\WorkshopSchedule;
use Illuminate\Database\QueryException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class PublicWorkshopController extends Controller
{
    private const DEFAULT_WORKSHOP_IMAGE = 'images/hero.png';

    private const ATTRIBUTION_SESSION_KEY = 'workshop_registration_attribution';

    public function index(Request $request)
    {
        $this->captureAttribution($request);

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

    public function show(Request $request, string $slug)
    {
        $this->captureAttribution($request);

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

    public function storeRegistration(Request $request, string $slug): JsonResponse
    {
        $today = now()->toDateString();

        $workshop = Workshop::query()
            ->where('is_active', true)
            ->where('slug', $slug)
            ->firstOrFail();

        $validated = $request->validate([
            'workshop_schedule_id' => [
                'required',
                'integer',
                Rule::exists('workshop_schedules', 'id')->where(function ($query) use ($today, $workshop) {
                    $query
                        ->where('workshop_id', $workshop->id)
                        ->where('is_active', true)
                        ->where('status', 'open')
                        ->whereDate('schedule_date', '>=', $today);
                }),
            ],
            'full_name' => ['required', 'string', 'max:255'],
            'email' => ['nullable', 'email', 'max:255'],
            'phone' => ['required', 'string', 'max:30'],
            'city' => ['nullable', 'string', 'max:255'],
            'goal' => ['nullable', 'string'],
            'notes' => ['nullable', 'string'],

            /*
            |--------------------------------------------------------------------------
            | Source Tracking / Campaign Attribution
            |--------------------------------------------------------------------------
            | Public form boleh mengirim data ini dari hidden fields / JavaScript.
            | Kalau tidak dikirim, controller akan fallback ke session attribution
            | yang ditangkap saat user membuka halaman workshop.
            |--------------------------------------------------------------------------
            */
            'input_source' => ['nullable', Rule::in(['admin', 'self_registration'])],
            'utm_source' => ['nullable', 'string', 'max:255'],
            'utm_medium' => ['nullable', 'string', 'max:255'],
            'utm_campaign' => ['nullable', 'string', 'max:255'],
            'utm_content' => ['nullable', 'string', 'max:255'],
            'utm_term' => ['nullable', 'string', 'max:255'],
            'referrer_url' => ['nullable', 'string', 'max:2048'],
            'landing_page_url' => ['nullable', 'string', 'max:2048'],
        ], [
            'workshop_schedule_id.required' => 'Jadwal workshop wajib dipilih.',
            'workshop_schedule_id.exists' => 'Jadwal workshop yang dipilih tidak valid atau sudah tidak tersedia.',
            'full_name.required' => 'Nama lengkap wajib diisi.',
            'email.email' => 'Format email tidak valid.',
            'phone.required' => 'Nomor WhatsApp wajib diisi.',
        ]);

        try {
            $participant = DB::transaction(function () use ($request, $validated, $workshop) {
                $workshopSchedule = WorkshopSchedule::query()
                    ->where('id', $validated['workshop_schedule_id'])
                    ->where('workshop_id', $workshop->id)
                    ->where('is_active', true)
                    ->where('status', 'open')
                    ->whereDate('schedule_date', '>=', now()->toDateString())
                    ->lockForUpdate()
                    ->firstOrFail();

                $this->ensureScheduleHasQuota($workshopSchedule);

                $attribution = $this->resolveAttributionData($request, $validated);

                $student = $this->findOrCreateStudent($validated);

                $this->ensureStudentIsNotRegistered(
                    $workshopSchedule->id,
                    $student->id
                );

                $price = $this->getWorkshopSchedulePrice($workshopSchedule, $workshop);
                $discount = 0;
                $finalPrice = max($price - $discount, 0);

                $participant = WorkshopParticipant::create(array_merge([
                    'workshop_id' => $workshop->id,
                    'workshop_schedule_id' => $workshopSchedule->id,
                    'student_id' => $student->id,
                    'status' => $finalPrice <= 0 ? 'confirmed' : 'pending_payment',
                    'registered_at' => now(),
                    'paid_at' => $finalPrice <= 0 ? now() : null,
                    'notes' => $validated['notes'] ?? $validated['goal'] ?? null,
                    'input_source' => 'self_registration',
                ], $attribution));

                $order = Order::create([
                    'student_id' => $student->id,
                    'order_type' => 'workshop',
                    'batch_id' => null,
                    'workshop_id' => $workshop->id,
                    'original_price' => $price,
                    'discount' => $discount,
                    'final_price' => $finalPrice,
                    'status' => $finalPrice <= 0 ? 'paid' : 'pending',
                    'notes' => $this->makeOrderNotes($workshop, $workshopSchedule),
                ]);

                $participant->update([
                    'order_id' => $order->id,
                ]);

                PaymentSchedule::create([
                    'order_id' => $order->id,
                    'title' => $this->makePaymentTitle($workshop, $workshopSchedule),
                    'amount' => $finalPrice,
                    'due_date' => now()->toDateString(),
                    'status' => $finalPrice <= 0 ? 'paid' : 'pending',
                    'notes' => 'Self registration workshop dari halaman public.',
                ]);

                $this->incrementScheduleRegisteredCount($workshopSchedule);

                return $participant->fresh([
                    'workshop',
                    'workshopSchedule',
                    'student',
                    'order.paymentSchedules',
                ]);
            });

            return response()->json([
                'success' => true,
                'message' => 'Pendaftaran workshop berhasil dikirim. Tim FlexLabs akan segera menghubungi kamu untuk konfirmasi pembayaran.',
                'data' => [
                    'participant' => $participant,
                    'payment_url' => $participant->order?->paymentSchedules?->first()?->payment_url ?? null,
                ],
            ]);
        } catch (ValidationException $exception) {
            throw $exception;
        } catch (QueryException $exception) {
            return response()->json([
                'success' => false,
                'message' => 'Peserta kemungkinan sudah terdaftar di jadwal workshop ini.',
                'error' => app()->isLocal() ? $exception->getMessage() : null,
            ], 422);
        } catch (\Throwable $exception) {
            report($exception);

            return response()->json([
                'success' => false,
                'message' => 'Gagal mengirim pendaftaran workshop.',
                'error' => app()->isLocal() ? $exception->getMessage() : null,
            ], 500);
        }
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
            'image' => $workshop->image ?: self::DEFAULT_WORKSHOP_IMAGE,
            'image_url' => $this->getWorkshopImageUrl($workshop),
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

    private function findOrCreateStudent(array $data): Student
    {
        $student = Student::query()
            ->when(! empty($data['email']), function ($query) use ($data) {
                $query->where('email', $data['email']);
            })
            ->when(empty($data['email']) && ! empty($data['phone']), function ($query) use ($data) {
                $query->where('phone', $data['phone']);
            })
            ->first();

        if ($student) {
            $student->update([
                'full_name' => $data['full_name'] ?? $student->full_name,
                'email' => $data['email'] ?? $student->email,
                'phone' => $data['phone'] ?? $student->phone,
                'city' => $data['city'] ?? $student->city,
                'goal' => $data['goal'] ?? $student->goal,
                'current_status' => 'Workshop Participant',
                'source' => 'workshop',
            ]);

            return $student;
        }

        return Student::create([
            'full_name' => $data['full_name'],
            'email' => $data['email'] ?? null,
            'phone' => $data['phone'],
            'city' => $data['city'] ?? null,
            'goal' => $data['goal'] ?? null,
            'current_status' => 'Workshop Participant',
            'source' => 'workshop',
            'status' => 'lead',
        ]);
    }

    private function ensureStudentIsNotRegistered(int $workshopScheduleId, int $studentId): void
    {
        $exists = WorkshopParticipant::query()
            ->where('workshop_schedule_id', $workshopScheduleId)
            ->where('student_id', $studentId)
            ->where('status', '!=', 'cancelled')
            ->exists();

        if ($exists) {
            throw ValidationException::withMessages([
                'workshop_schedule_id' => 'Kamu sudah terdaftar di jadwal workshop ini.',
            ]);
        }
    }

    private function ensureScheduleHasQuota(WorkshopSchedule $schedule): void
    {
        if ($schedule->quota === null) {
            return;
        }

        $registeredCount = (int) ($schedule->registered_count ?? 0);

        if ($registeredCount >= (int) $schedule->quota) {
            throw ValidationException::withMessages([
                'workshop_schedule_id' => 'Jadwal workshop ini sudah penuh. Silakan pilih jadwal lain.',
            ]);
        }
    }

    private function getWorkshopTitle(Workshop $workshop): string
    {
        return $workshop->title
            ?? $workshop->name
            ?? 'Workshop FlexLabs';
    }

    private function getWorkshopScheduleTitle(WorkshopSchedule $schedule): string
    {
        $date = $schedule->schedule_date
            ? Carbon::parse($schedule->schedule_date)->format('d M Y')
            : null;

        $time = trim(implode(' - ', array_filter([
            $schedule->start_time ? substr((string) $schedule->start_time, 0, 5) : null,
            $schedule->end_time ? substr((string) $schedule->end_time, 0, 5) : null,
        ])));

        return trim(implode(' - ', array_filter([
            $schedule->title,
            $date,
            $time,
        ]))) ?: 'Jadwal Workshop';
    }

    private function makeOrderNotes(Workshop $workshop, WorkshopSchedule $schedule): string
    {
        return 'Workshop: '
            . $this->getWorkshopTitle($workshop)
            . ' | Jadwal: '
            . $this->getWorkshopScheduleTitle($schedule);
    }

    private function makePaymentTitle(Workshop $workshop, WorkshopSchedule $schedule): string
    {
        return 'Pembayaran Workshop: '
            . $this->getWorkshopTitle($workshop)
            . ' - '
            . $this->getWorkshopScheduleTitle($schedule);
    }

    private function getWorkshopSchedulePrice(WorkshopSchedule $schedule, Workshop $workshop): float
    {
        return (float) (
            $schedule->price
            ?? $workshop->price
            ?? $workshop->final_price
            ?? $workshop->registration_fee
            ?? 0
        );
    }

    private function incrementScheduleRegisteredCount(WorkshopSchedule $schedule): void
    {
        $schedule->increment('registered_count');
    }

    private function captureAttribution(Request $request): void
    {
        $incomingAttribution = $this->extractAttributionFromRequest($request);

        $hasIncomingUtm = collect([
            'utm_source',
            'utm_medium',
            'utm_campaign',
            'utm_content',
            'utm_term',
        ])->contains(fn (string $key) => filled($incomingAttribution[$key] ?? null));

        $existingAttribution = session(self::ATTRIBUTION_SESSION_KEY, []);

        /*
        |--------------------------------------------------------------------------
        | Attribution Storage Strategy
        |--------------------------------------------------------------------------
        | - Kalau ada UTM baru, simpan sebagai attribution terbaru.
        | - Kalau belum ada session attribution, simpan landing/referrer awal.
        | - Kalau sudah ada session dan request berikutnya tanpa UTM, jangan overwrite
        |   campaign awal dengan URL internal.
        |--------------------------------------------------------------------------
        */
        if ($hasIncomingUtm || empty($existingAttribution)) {
            session([
                self::ATTRIBUTION_SESSION_KEY => array_merge(
                    $existingAttribution,
                    array_filter($incomingAttribution, fn ($value) => filled($value))
                ),
            ]);
        }
    }

    private function extractAttributionFromRequest(Request $request): array
    {
        return [
            'utm_source' => $this->cleanTrackingValue($request->query('utm_source')),
            'utm_medium' => $this->cleanTrackingValue($request->query('utm_medium')),
            'utm_campaign' => $this->cleanTrackingValue($request->query('utm_campaign')),
            'utm_content' => $this->cleanTrackingValue($request->query('utm_content')),
            'utm_term' => $this->cleanTrackingValue($request->query('utm_term')),
            'referrer_url' => $this->cleanTrackingValue($request->headers->get('referer'), 2048),
            'landing_page_url' => $this->cleanTrackingValue($request->fullUrl(), 2048),
        ];
    }

    private function resolveAttributionData(Request $request, array $validated): array
    {
        $sessionAttribution = session(self::ATTRIBUTION_SESSION_KEY, []);

        $referrerFromRequest = $request->input('referrer_url')
            ?: $request->headers->get('referer');

        $landingPageFromRequest = $request->input('landing_page_url')
            ?: $sessionAttribution['landing_page_url']
            ?? $request->headers->get('referer')
            ?? $request->fullUrl();

        return [
            'utm_source' => $this->cleanTrackingValue(
                $validated['utm_source'] ?? $request->input('utm_source') ?? $sessionAttribution['utm_source'] ?? null
            ),
            'utm_medium' => $this->cleanTrackingValue(
                $validated['utm_medium'] ?? $request->input('utm_medium') ?? $sessionAttribution['utm_medium'] ?? null
            ),
            'utm_campaign' => $this->cleanTrackingValue(
                $validated['utm_campaign'] ?? $request->input('utm_campaign') ?? $sessionAttribution['utm_campaign'] ?? null
            ),
            'utm_content' => $this->cleanTrackingValue(
                $validated['utm_content'] ?? $request->input('utm_content') ?? $sessionAttribution['utm_content'] ?? null
            ),
            'utm_term' => $this->cleanTrackingValue(
                $validated['utm_term'] ?? $request->input('utm_term') ?? $sessionAttribution['utm_term'] ?? null
            ),
            'referrer_url' => $this->cleanTrackingValue(
                $validated['referrer_url'] ?? $referrerFromRequest ?? $sessionAttribution['referrer_url'] ?? null,
                2048
            ),
            'landing_page_url' => $this->cleanTrackingValue(
                $validated['landing_page_url'] ?? $landingPageFromRequest,
                2048
            ),
        ];
    }

    private function cleanTrackingValue(mixed $value, int $limit = 255): ?string
    {
        if ($value === null) {
            return null;
        }

        $value = trim((string) $value);

        if ($value === '') {
            return null;
        }

        return mb_substr($value, 0, $limit);
    }

    private function getWorkshopImageUrl(Workshop $workshop): string
    {
        if (! empty($workshop->image)) {
            if (
                str_starts_with($workshop->image, 'images/')
                || str_starts_with($workshop->image, 'storage/')
                || str_starts_with($workshop->image, 'http://')
                || str_starts_with($workshop->image, 'https://')
            ) {
                return asset($workshop->image);
            }

            return Storage::disk('public')->url($workshop->image);
        }

        return asset(self::DEFAULT_WORKSHOP_IMAGE);
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
