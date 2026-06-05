<?php

namespace App\Http\Controllers;

use App\Models\EventLead;
use App\Models\LeadEvent;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\View\View;
use Throwable;

class PublicEventLeadController extends Controller
{
    /**
     * Halaman utama event.
     */
    public function index(): View
    {
        $featuredEvents = LeadEvent::query()
            ->active()
            ->featured()
            ->withCount('leads')
            ->ordered()
            ->get();

        $events = LeadEvent::query()
            ->active()
            ->withCount('leads')
            ->ordered()
            ->get();

        return view('public.events.index', [
            'featuredEvents' => $featuredEvents,
            'events' => $events,
        ]);
    }

    /**
     * Halaman detail / pendaftaran event.
     */
    public function show(string $slug): View
    {
        $event = LeadEvent::query()
            ->active()
            ->where('slug', $slug)
            ->firstOrFail();

        return view('public.events.show', [
            'event' => $event,
        ]);
    }

    /**
     * Submit lead dari landing page event.
     */
    public function store(Request $request, string $slug): JsonResponse|RedirectResponse
    {
        $event = LeadEvent::query()
            ->active()
            ->where('slug', $slug)
            ->firstOrFail();

        $validated = $request->validate(
            [
                'name' => ['required', 'string', 'max:150'],
                'email' => ['nullable', 'email', 'max:150'],
                'phone' => ['required', 'string', 'max:50'],

                'institution' => ['nullable', 'string', 'max:150'],
                'position' => ['nullable', 'string', 'max:100'],
                'city' => ['nullable', 'string', 'max:100'],

                'interest' => ['nullable', 'string', 'max:150'],
                'notes' => ['nullable', 'string', 'max:1000'],

                'is_consent_given' => ['required', 'accepted'],
            ],
            [
                'name.required' => 'Nama wajib diisi.',
                'phone.required' => 'Nomor WhatsApp wajib diisi.',
                'email.email' => 'Format email belum valid.',
                'is_consent_given.required' => 'Persetujuan wajib dicentang.',
                'is_consent_given.accepted' => 'Persetujuan wajib dicentang.',
            ]
        );

        try {
            $lead = EventLead::query()->create([
                'lead_event_id' => $event->id,

                'name' => $validated['name'],
                'email' => $validated['email'] ?? null,
                'phone' => $this->normalizePhone($validated['phone'] ?? null),

                'institution' => $validated['institution'] ?? null,
                'position' => $validated['position'] ?? null,
                'city' => $validated['city'] ?? null,

                'interest' => $validated['interest'] ?? null,
                'notes' => $validated['notes'] ?? null,

                'source' => 'event_landing_page',

                'utm_source' => $request->query('utm_source') ?: $request->input('utm_source'),
                'utm_medium' => $request->query('utm_medium') ?: $request->input('utm_medium'),
                'utm_campaign' => $request->query('utm_campaign') ?: $request->input('utm_campaign'),
                'utm_term' => $request->query('utm_term') ?: $request->input('utm_term'),
                'utm_content' => $request->query('utm_content') ?: $request->input('utm_content'),

                'status' => EventLead::STATUS_NEW,

                'is_consent_given' => $request->boolean('is_consent_given'),
                'consent_given_at' => $request->boolean('is_consent_given') ? now() : null,

                'ip_address' => $request->ip(),
                'user_agent' => substr((string) $request->userAgent(), 0, 1000),

                'metadata' => [
                    'submitted_from_url' => $request->fullUrl(),
                    'referer' => $request->headers->get('referer'),
                ],
            ]);

            $message = 'Pendaftaran berhasil dikirim. Tim FlexLabs akan menghubungi kamu segera.';

            if ($request->expectsJson() || $request->ajax()) {
                return response()->json([
                    'success' => true,
                    'message' => $message,
                    'data' => [
                        'id' => $lead->id,
                        'event' => $event->title,
                    ],
                ]);
            }

            return redirect()
                ->route('events.show', $event->slug)
                ->with('success', $message);
        } catch (Throwable $exception) {
            Log::error('Failed to submit event lead', [
                'event_id' => $event->id,
                'event_slug' => $event->slug,
                'message' => $exception->getMessage(),
            ]);

            $message = 'Pendaftaran belum berhasil dikirim. Coba beberapa saat lagi ya.';

            if ($request->expectsJson() || $request->ajax()) {
                return response()->json([
                    'success' => false,
                    'message' => $message,
                ], 500);
            }

            return redirect()
                ->route('events.show', $event->slug)
                ->withInput()
                ->with('error', $message);
        }
    }

    /**
     * Normalisasi nomor HP/WhatsApp sederhana.
     */
    private function normalizePhone(?string $phone): ?string
    {
        if (blank($phone)) {
            return null;
        }

        $phone = trim($phone);
        $phone = preg_replace('/[^\d+]/', '', $phone);

        if (str_starts_with($phone, '08')) {
            return '+62' . substr($phone, 1);
        }

        if (str_starts_with($phone, '628')) {
            return '+' . $phone;
        }

        return $phone;
    }
}
