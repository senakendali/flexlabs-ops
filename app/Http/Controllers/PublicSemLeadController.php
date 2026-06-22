<?php

namespace App\Http\Controllers;

use App\Services\KommoService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\View\View;
use Throwable;

class PublicSemLeadController extends Controller
{
    public function index(Request $request): View
    {
        return view('public.sem-leads.index', [
            'pageTitle' => 'Konsultasi Program FlexLabs',
            'programSlug' => null,
            'selectedProgram' => null,
            'programOptions' => $this->programOptions(),
            'helpNeedOptions' => $this->helpNeedOptions(),
            'bestContactTimeOptions' => $this->bestContactTimeOptions(),
            'trackingFields' => $this->trackingFields($request),
            'formAction' => route('consultation.store'),
        ]);
    }

    public function show(Request $request, string $program): View
    {
        $selectedProgram = $this->resolveProgramInterest($program);

        return view('public.sem-leads.index', [
            'pageTitle' => 'Konsultasi ' . $selectedProgram . ' FlexLabs',
            'programSlug' => $program,
            'selectedProgram' => $selectedProgram,
            'programOptions' => $this->programOptions(),
            'helpNeedOptions' => $this->helpNeedOptions(),
            'bestContactTimeOptions' => $this->bestContactTimeOptions(),
            'trackingFields' => $this->trackingFields($request),
            'formAction' => route('consultation.program.store', ['program' => $program]),
        ]);
    }

    public function store(Request $request, ?string $program = null): RedirectResponse
    {
        $selectedProgram = $program
            ? $this->resolveProgramInterest($program)
            : $request->input('program_interest');

        $request->merge([
            'program_interest' => $selectedProgram,
        ]);

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:150'],
            'whatsapp_number' => [
                'required',
                'string',
                'max:30',
                'regex:/^[0-9+\-\s()]{8,30}$/',
            ],
            'program_interest' => ['required', 'string', 'max:150'],
            'help_need' => ['nullable', 'string', 'max:1000'],
            'best_contact_time' => [
                'nullable',
                'string',
                Rule::in(array_keys($this->bestContactTimeOptions())),
            ],

            // Honeypot anti bot. Field ini jangan ditampilkan ke user asli.
            'company_website' => ['nullable', 'max:0'],

            // Tracking fields
            'landing_page_url' => ['nullable', 'string', 'max:500'],
            'referrer_url' => ['nullable', 'string', 'max:500'],
            'utm_source' => ['nullable', 'string', 'max:255'],
            'utm_medium' => ['nullable', 'string', 'max:255'],
            'utm_campaign' => ['nullable', 'string', 'max:255'],
            'utm_content' => ['nullable', 'string', 'max:255'],
            'utm_term' => ['nullable', 'string', 'max:255'],
            'gclid' => ['nullable', 'string', 'max:255'],
            'gbraid' => ['nullable', 'string', 'max:255'],
            'wbraid' => ['nullable', 'string', 'max:255'],
        ], [
            'name.required' => 'Nama lengkap wajib diisi.',
            'whatsapp_number.required' => 'Nomor WhatsApp wajib diisi.',
            'whatsapp_number.regex' => 'Format nomor WhatsApp belum valid.',
            'program_interest.required' => 'Program yang diminati wajib dipilih.',
            'best_contact_time.in' => 'Waktu terbaik dihubungi tidak valid.',
            'company_website.max' => 'Request tidak valid.',
        ]);

        $leadData = [
            'name' => trim($validated['name']),
            'whatsapp_number' => $this->normalizeWhatsappNumber($validated['whatsapp_number']),
            'program_interest' => trim($validated['program_interest']),
            'help_need' => $this->nullableText($validated['help_need'] ?? null),
            'best_contact_time' => $validated['best_contact_time'] ?? null,

            'source' => 'google_sem',
            'landing_page_url' => $this->nullableText(
                $validated['landing_page_url'] ?? $request->fullUrl()
            ),
            'referrer_url' => $this->nullableText(
                $validated['referrer_url'] ?? $request->headers->get('referer')
            ),

            'utm_source' => $this->nullableText($validated['utm_source'] ?? $request->query('utm_source')),
            'utm_medium' => $this->nullableText($validated['utm_medium'] ?? $request->query('utm_medium')),
            'utm_campaign' => $this->nullableText($validated['utm_campaign'] ?? $request->query('utm_campaign')),
            'utm_content' => $this->nullableText($validated['utm_content'] ?? $request->query('utm_content')),
            'utm_term' => $this->nullableText($validated['utm_term'] ?? $request->query('utm_term')),

            'gclid' => $this->nullableText($validated['gclid'] ?? $request->query('gclid')),
            'gbraid' => $this->nullableText($validated['gbraid'] ?? $request->query('gbraid')),
            'wbraid' => $this->nullableText($validated['wbraid'] ?? $request->query('wbraid')),

            'kommo_sync_status' => 'pending',
            'kommo_lead_id' => null,
            'kommo_contact_id' => null,
            'kommo_synced_at' => null,
            'kommo_error' => null,

            'ip_address' => $request->ip(),
            'user_agent' => Str::limit((string) $request->userAgent(), 1000, ''),
            'created_at' => now(),
            'updated_at' => now(),
        ];

        $semLeadId = DB::table('sem_leads')->insertGetId($leadData);

        $this->syncLeadToKommo($semLeadId);

        return redirect()
            ->route('consultation.thank-you')
            ->with([
                'success' => 'Terima kasih! Tim FlexLabs akan menghubungi kamu melalui WhatsApp.',
                'sem_lead_id' => $semLeadId,
                'program_interest' => $leadData['program_interest'],
                'whatsapp_number' => $leadData['whatsapp_number'],
            ]);
    }

    public function thankYou(Request $request): View
    {
        return view('public.sem-leads.thank-you', [
            'pageTitle' => 'Terima Kasih - FlexLabs',
            'whatsappUrl' => $this->buildWhatsappUrl(),
        ]);
    }

    private function syncLeadToKommo(int $semLeadId): void
    {
        if (!class_exists(KommoService::class)) {
            return;
        }

        if (!config('services.kommo.enabled')) {
            return;
        }

        $lead = DB::table('sem_leads')
            ->where('id', $semLeadId)
            ->first();

        if (!$lead) {
            return;
        }

        try {
            DB::table('sem_leads')
                ->where('id', $semLeadId)
                ->update([
                    'kommo_sync_status' => 'processing',
                    'kommo_error' => null,
                    'updated_at' => now(),
                ]);

            /** @var KommoService $kommoService */
            $kommoService = app(KommoService::class);

            $response = $kommoService->createSemLead((array) $lead);

            if (empty($response)) {
                DB::table('sem_leads')
                    ->where('id', $semLeadId)
                    ->update([
                        'kommo_sync_status' => 'failed',
                        'kommo_error' => 'Empty response from Kommo API.',
                        'updated_at' => now(),
                    ]);

                return;
            }

            $kommoLeadId = $this->extractKommoLeadId($response);
            $kommoContactId = $this->extractKommoContactId($response);

            DB::table('sem_leads')
                ->where('id', $semLeadId)
                ->update([
                    'kommo_sync_status' => $kommoLeadId ? 'synced' : 'failed',
                    'kommo_lead_id' => $kommoLeadId,
                    'kommo_contact_id' => $kommoContactId,
                    'kommo_synced_at' => $kommoLeadId ? now() : null,
                    'kommo_error' => $kommoLeadId ? null : 'Kommo response received, but lead ID was not found.',
                    'updated_at' => now(),
                ]);
        } catch (Throwable $exception) {
            Log::error('Failed to sync SEM lead to Kommo.', [
                'sem_lead_id' => $semLeadId,
                'message' => $exception->getMessage(),
            ]);

            DB::table('sem_leads')
                ->where('id', $semLeadId)
                ->update([
                    'kommo_sync_status' => 'failed',
                    'kommo_error' => Str::limit($exception->getMessage(), 3000, ''),
                    'updated_at' => now(),
                ]);
        }
    }

    private function extractKommoLeadId(array $response): ?int
    {
        /*
        |--------------------------------------------------------------------------
        | Supported Kommo response shapes
        |--------------------------------------------------------------------------
        | Complex Leads biasanya return:
        | [
        |     [
        |         "id" => 50160097,
        |         "contact_id" => 46705221,
        |     ]
        | ]
        |
        | Tapi kita tetap support shape embedded untuk aman.
        |--------------------------------------------------------------------------
        */

        $lead = Arr::get($response, '0')
            ?? Arr::get($response, '_embedded.leads.0')
            ?? $response;

        $id = Arr::get($lead, 'id')
            ?? Arr::get($lead, 'lead_id');

        return $id ? (int) $id : null;
    }

    private function extractKommoContactId(array $response): ?int
    {
        $lead = Arr::get($response, '0')
            ?? Arr::get($response, '_embedded.leads.0')
            ?? $response;

        $contactId = Arr::get($lead, 'contact_id')
            ?? Arr::get($lead, '_embedded.contacts.0.id')
            ?? Arr::get($response, '_embedded.contacts.0.id');

        return $contactId ? (int) $contactId : null;
    }

    private function trackingFields(Request $request): array
    {
        return [
            'landing_page_url' => $request->fullUrl(),
            'referrer_url' => $request->headers->get('referer'),

            'utm_source' => $request->query('utm_source'),
            'utm_medium' => $request->query('utm_medium'),
            'utm_campaign' => $request->query('utm_campaign'),
            'utm_content' => $request->query('utm_content'),
            'utm_term' => $request->query('utm_term'),

            'gclid' => $request->query('gclid'),
            'gbraid' => $request->query('gbraid'),
            'wbraid' => $request->query('wbraid'),
        ];
    }

    private function programOptions(): array
    {
        return [
            'software-engineering' => 'Software Engineering',
            'ai-productivity' => 'AI Productivity',
            'ui-ux' => 'UI/UX Design',
            'data-analytics' => 'Data Analytics',
            'digital-marketing' => 'Digital Marketing',
            'belum-tahu' => 'Belum tahu, mau konsultasi dulu',
        ];
    }

    private function helpNeedOptions(): array
    {
        return [
            'belajar-dari-nol' => 'Mau belajar dari nol',
            'upgrade-skill-kerja' => 'Mau upgrade skill kerja',
            'pindah-karier' => 'Mau pindah karier',
            'bikin-portfolio' => 'Mau bikin portfolio',
            'konsultasi-program' => 'Mau konsultasi pilihan program',
            'lainnya' => 'Lainnya',
        ];
    }

    private function bestContactTimeOptions(): array
    {
        return [
            'secepatnya' => 'Secepatnya',
            'pagi' => 'Pagi',
            'siang' => 'Siang',
            'sore' => 'Sore',
            'malam' => 'Malam',
        ];
    }

    private function resolveProgramInterest(?string $program, ?string $fallback = null): string
    {
        if (!$program) {
            return $fallback ?: 'Belum tahu, mau konsultasi dulu';
        }

        $programOptions = $this->programOptions();

        if (isset($programOptions[$program])) {
            return $programOptions[$program];
        }

        return Str::of($program)
            ->replace('-', ' ')
            ->title()
            ->toString();
    }

    private function normalizeWhatsappNumber(string $value): string
    {
        $number = preg_replace('/[^0-9+]/', '', $value) ?: '';

        if (Str::startsWith($number, '+')) {
            $number = substr($number, 1);
        }

        if (Str::startsWith($number, '0')) {
            $number = '62' . substr($number, 1);
        }

        if (Str::startsWith($number, '8')) {
            $number = '62' . $number;
        }

        return $number;
    }

    private function nullableText(mixed $value): ?string
    {
        if ($value === null) {
            return null;
        }

        $value = trim((string) $value);

        return $value === '' ? null : $value;
    }

    private function buildWhatsappUrl(): string
    {
        $message = 'Halo FlexLabs! Saya sudah isi form konsultasi program. Mohon dibantu ya.';

        return 'https://wa.me/62811134759?' . http_build_query([
            'text' => $message,
        ], '', '&', PHP_QUERY_RFC3986);
    }
}