<?php

namespace App\Http\Controllers\Sales;

use App\Http\Controllers\Controller;
use App\Models\SalesDailyReport;
use App\Services\KommoService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Log;
use Illuminate\View\View;
use Throwable;

class SalesDailyReportController extends Controller
{
    public function index(Request $request): View
    {
        $query = SalesDailyReport::with('creator')
            ->latest('report_date')
            ->latest('id');

        if ($request->filled('date_from')) {
            $query->whereDate('report_date', '>=', $request->string('date_from')->toString());
        }

        if ($request->filled('date_to')) {
            $query->whereDate('report_date', '<=', $request->string('date_to')->toString());
        }

        $reports = $query->paginate(10)->withQueryString();

        $filters = [
            'date_from' => $request->string('date_from')->toString(),
            'date_to' => $request->string('date_to')->toString(),
        ];

        $salesDailyReportInsight = $this->getSalesDailyReportInsight($filters);
        $salesDailyReportAiSummaryText = $salesDailyReportInsight['summary_text'] ?? '';

        return view('sales.daily-reports.index', [
            'reports' => $reports,
            'filters' => $filters,
            'salesDailyReportInsight' => $salesDailyReportInsight,
            'salesDailyReportAiSummaryText' => $salesDailyReportAiSummaryText,
        ]);
    }

    public function create(): View
    {
        return view('sales.daily-reports.form', [
            'report' => new SalesDailyReport([
                'report_date' => now()->toDateString(),
                'total_leads' => 0,
                'interacted' => 0,
                'ignored' => 0,
                'closed_lost' => 0,
                'not_related' => 0,
                'warm_leads' => 0,
                'hot_leads' => 0,
                'consultation' => 0,
                'closed_deal' => 0,
                'revenue' => 0,
            ]),
            'isEdit' => false,
        ]);
    }

    public function store(Request $request): JsonResponse|RedirectResponse
    {
        $validated = $this->validateRequest($request);
        $validated['created_by'] = auth()->id();

        $report = SalesDailyReport::create($validated);

        if ($request->expectsJson()) {
            return response()->json([
                'success' => true,
                'message' => 'Sales daily report berhasil dibuat.',
                'data' => $report->load('creator'),
            ]);
        }

        return redirect()
            ->route('sales-daily-reports.index')
            ->with('success', 'Sales daily report berhasil dibuat.');
    }

    public function show(SalesDailyReport $salesDailyReport): View
    {
        $salesDailyReport->load('creator');

        return view('sales.daily-reports.show', [
            'report' => $salesDailyReport,
        ]);
    }

    public function edit(SalesDailyReport $salesDailyReport): View
    {
        return view('sales.daily-reports.form', [
            'report' => $salesDailyReport,
            'isEdit' => true,
        ]);
    }

    public function update(Request $request, SalesDailyReport $salesDailyReport): JsonResponse|RedirectResponse
    {
        $validated = $this->validateRequest($request);

        $salesDailyReport->update($validated);

        if ($request->expectsJson()) {
            return response()->json([
                'success' => true,
                'message' => 'Sales daily report berhasil diperbarui.',
                'data' => $salesDailyReport->fresh()->load('creator'),
            ]);
        }

        return redirect()
            ->route('sales-daily-reports.index')
            ->with('success', 'Sales daily report berhasil diperbarui.');
    }

    public function destroy(Request $request, SalesDailyReport $salesDailyReport): JsonResponse|RedirectResponse
    {
        $salesDailyReport->delete();

        if ($request->expectsJson()) {
            return response()->json([
                'success' => true,
                'message' => 'Sales daily report berhasil dihapus.',
            ]);
        }

        return redirect()
            ->route('sales-daily-reports.index')
            ->with('success', 'Sales daily report berhasil dihapus.');
    }


    public function kommoSummary(Request $request, KommoService $kommoService): JsonResponse
    {
        $validated = $request->validate([
            'date' => ['nullable', 'date'],
            'report_date' => ['nullable', 'date'],
        ]);

        $date = $validated['date']
            ?? $validated['report_date']
            ?? now()->toDateString();

        $date = Carbon::parse($date)->toDateString();

        try {
            $summary = $kommoService->getDailyLeadSummary(
                date: $date,
                timezone: config('app.timezone', 'Asia/Jakarta')
            );

            return response()->json([
                'success' => true,
                'message' => 'Data Kommo berhasil ditarik.',
                'data' => [
                    'total_leads' => (int) ($summary['total_leads'] ?? 0),
                    'interacted' => (int) ($summary['interacted'] ?? 0),
                    'ignored' => (int) ($summary['ignored'] ?? 0),
                    'closed_lost' => (int) ($summary['closed_lost'] ?? 0),
                    'not_related' => (int) ($summary['not_related'] ?? 0),
                    'warm_leads' => (int) ($summary['warm_leads'] ?? 0),
                    'hot_leads' => (int) ($summary['hot_leads'] ?? 0),
                    'consultation' => (int) ($summary['consultation'] ?? 0),
                ],
                'meta' => [
                    'date' => $date,
                    'timezone' => $summary['timezone'] ?? config('app.timezone', 'Asia/Jakarta'),
                    'pipeline_id' => $summary['pipeline_id'] ?? config('services.kommo.pipeline_id'),
                    'start_timestamp' => $summary['start_timestamp'] ?? null,
                    'end_timestamp' => $summary['end_timestamp'] ?? null,
                    'source' => 'kommo',
                ],
            ]);
        } catch (Throwable $exception) {
            Log::error('Failed to fetch Kommo daily lead summary.', [
                'date' => $date,
                'message' => $exception->getMessage(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Data Kommo belum bisa ditarik. Silakan cek konfigurasi Kommo atau coba lagi.',
                'error' => app()->hasDebugModeEnabled()
                    ? $exception->getMessage()
                    : null,
                'data' => [
                    'total_leads' => 0,
                    'interacted' => 0,
                    'ignored' => 0,
                    'closed_lost' => 0,
                    'not_related' => 0,
                    'warm_leads' => 0,
                    'hot_leads' => 0,
                    'consultation' => 0,
                ],
                'meta' => [
                    'date' => $date,
                    'source' => 'kommo',
                ],
            ], 500);
        }
    }

    protected function validateRequest(Request $request): array
    {
        return $request->validate([
            'report_date' => ['required', 'date'],

            'total_leads' => ['required', 'integer', 'min:0'],
            'interacted' => ['required', 'integer', 'min:0'],
            'ignored' => ['required', 'integer', 'min:0'],
            'closed_lost' => ['required', 'integer', 'min:0'],
            'not_related' => ['required', 'integer', 'min:0'],
            'warm_leads' => ['required', 'integer', 'min:0'],
            'hot_leads' => ['required', 'integer', 'min:0'],
            'consultation' => ['required', 'integer', 'min:0'],
            'closed_deal' => ['required', 'integer', 'min:0'],
            'revenue' => ['required', 'numeric', 'min:0'],

            'summary' => ['nullable', 'string'],
            'highlight' => ['nullable', 'string'],
            'notes' => ['nullable', 'string'],
        ]);
    }

    protected function getSalesDailyReportInsight(array $filters): array
    {
        [$dateFrom, $dateTo] = $this->resolveInsightPeriod($filters);

        $current = $this->getSalesDailyReportSummary($dateFrom, $dateTo);

        $startDate = Carbon::parse($dateFrom)->startOfDay();
        $endDate = Carbon::parse($dateTo)->startOfDay();
        $periodDays = max(1, $startDate->diffInDays($endDate) + 1);

        $previousTo = $startDate->copy()->subDay()->toDateString();
        $previousFrom = $startDate->copy()->subDays($periodDays)->toDateString();
        $previous = $this->getSalesDailyReportSummary($previousFrom, $previousTo);

        $rates = $this->buildSalesDailyReportRates($current);
        $previousRates = $this->buildSalesDailyReportRates($previous);

        $latestReport = SalesDailyReport::query()
            ->whereDate('report_date', '>=', $dateFrom)
            ->whereDate('report_date', '<=', $dateTo)
            ->latest('report_date')
            ->latest('id')
            ->first();

        $latestReportDate = $latestReport?->report_date
            ? Carbon::parse($latestReport->report_date)->toDateString()
            : null;

        $daysSinceLatestReport = $latestReportDate
            ? Carbon::parse($latestReportDate)->startOfDay()->diffInDays(now()->startOfDay())
            : null;

        $items = [];

        $reportCount = (int) $current['reports'];
        $totalLeads = (int) $current['total_leads'];
        $interacted = (int) $current['interacted'];
        $warmLeads = (int) $current['warm_leads'];
        $hotLeads = (int) $current['hot_leads'];
        $consultation = (int) $current['consultation'];
        $closedDeal = (int) $current['closed_deal'];
        $ignored = (int) $current['ignored'];
        $closedLost = (int) $current['closed_lost'];
        $notRelated = (int) $current['not_related'];
        $revenue = (float) $current['revenue'];

        $interactionRate = (float) $rates['interaction_rate'];
        $badLeadRate = (float) $rates['bad_lead_rate'];

        if ($reportCount <= 0) {
            $items[] = $this->salesInsightItem(
                'warning',
                'Belum ada sales report pada periode ini',
                $this->pickSalesInsightTemplate('no_report', [
                    'Belum ada sales daily report pada periode ini. Tim perlu memastikan aktivitas harian tetap dicatat agar performa leads, follow-up, dan closing bisa dibaca dengan jelas.',
                    'Data sales report belum masuk untuk periode ini. Prioritasnya adalah melengkapi laporan harian supaya dashboard bisa memberi gambaran pipeline yang akurat.',
                    'Belum ada laporan sales yang tercatat pada periode ini. Pastikan report harian diisi agar follow-up dan evaluasi funnel tidak tertunda.',
                ]),
                950
            );
        } elseif ($totalLeads <= 0) {
            $items[] = $this->salesInsightItem(
                'warning',
                'Leads belum masuk pada periode ini',
                $this->pickSalesInsightTemplate('no_leads', [
                    'Sales report sudah tercatat, tapi leads belum masuk pada periode ini. Fokus utama perlu diarahkan ke channel acquisition, campaign, referral, atau follow-up database lama.',
                    'Aktivitas report sudah ada, namun leads masih kosong. Tim perlu mengecek sumber leads dan memastikan campaign berjalan sesuai target.',
                    'Belum ada leads yang tercatat. Ini sinyal awal untuk memperkuat promosi, distribusi landing page, dan aktivitas outreach.',
                ]),
                900
            );
        } elseif ($interacted <= 0) {
            $items[] = $this->salesInsightItem(
                'warning',
                'Leads sudah masuk, tapi belum ada interaksi',
                $this->pickSalesInsightTemplate('no_interaction', [
                    'Leads sudah masuk, tapi belum ada interaksi tercatat. Tim sales perlu mempercepat kontak awal agar leads tidak terlalu lama menjadi cold lead.',
                    'Ada leads pada periode ini, namun belum terlihat interaksi. Prioritasnya adalah respon awal, follow-up pertama, dan pencatatan status komunikasi.',
                    'Pipeline awal sudah terbentuk dari leads, tetapi engagement belum terjadi. Semakin cepat leads dihubungi, semakin besar peluang masuk ke consultation atau closing.',
                ]),
                880,
                ['metric' => number_format($totalLeads) . ' leads']
            );
        } elseif ($closedDeal <= 0) {
            $items[] = $this->salesInsightItem(
                'warning',
                'Interaksi ada, tapi belum jadi closing',
                $this->pickSalesInsightTemplate('interaction_no_closing', [
                    'Leads dan interaksi sudah berjalan, tapi belum ada closed deal. Fokus berikutnya adalah mendorong consultation, mengawal hot leads, dan memperjelas next step calon peserta.',
                    'Funnel sales mulai bergerak dari sisi interaksi, namun belum berubah menjadi closing. Tim perlu memprioritaskan leads yang paling hangat.',
                    'Interaksi sudah ada, tapi closing belum terbentuk. Perlu cek objection, kebutuhan calon peserta, dan follow-up personal untuk leads yang paling siap lanjut.',
                ]),
                840,
                ['metric' => number_format($interacted) . ' interaksi']
            );
        } elseif ($revenue <= 0) {
            $items[] = $this->salesInsightItem(
                'warning',
                'Closing belum menghasilkan revenue',
                $this->pickSalesInsightTemplate('closing_no_revenue', [
                    'Closed deal sudah tercatat, tetapi revenue belum masuk di report. Cek apakah pembayaran belum terjadi, belum dikonfirmasi, atau angka revenue belum diisi.',
                    'Ada closed deal pada periode ini, tapi revenue masih kosong. Tim perlu memastikan proses pembayaran dan pencatatan revenue tidak tertunda.',
                    'Closing sudah ada, namun pemasukan belum tercatat. Fokus berikutnya adalah validasi invoice, payment confirmation, dan update report.',
                ]),
                830,
                ['metric' => number_format($closedDeal) . ' deal']
            );
        } else {
            $items[] = $this->salesInsightItem(
                'good',
                'Revenue dari aktivitas sales sudah tercatat',
                $this->pickSalesInsightTemplate('revenue_exists', [
                    'Revenue dari aktivitas sales periode ini sudah tercatat. Agar momentum tetap terjaga, tim perlu melanjutkan follow-up leads, consultation, dan hot leads yang masih berpotensi menjadi closing berikutnya.',
                    'Aktivitas sales periode ini sudah menghasilkan revenue. Fokus berikutnya adalah menjaga ritme follow-up agar peluang baru tetap bergerak.',
                    'Pemasukan sales sudah tercatat. Tim bisa menjaga momentum dengan mengawal leads hangat dan memastikan proses dari consultation ke closing tetap jelas.',
                ]),
                780,
                ['metric' => $this->formatCurrency($revenue)]
            );
        }

        if ($reportCount > 0 && $interactionRate > 0 && $interactionRate < 35) {
            $items[] = $this->salesInsightItem(
                'warning',
                'Interaction rate masih rendah',
                $this->pickSalesInsightTemplate('low_interaction_rate', [
                    'Interaction rate masih rendah. Cek kecepatan respon, script follow-up, dan channel komunikasi yang dipakai untuk menghubungi leads.',
                    'Leads sudah masuk, tapi rasio interaksi belum kuat. Tim perlu mempercepat respon awal dan memastikan setiap leads punya status follow-up yang jelas.',
                    'Interaksi belum optimal dibanding jumlah leads. Prioritasnya adalah memperbaiki kontak pertama dan mengurangi leads yang tidak sempat tersentuh.',
                ]),
                700,
                ['metric' => number_format($interactionRate, 1) . '%']
            );
        }

        if ($hotLeads > 0 && $closedDeal <= 0) {
            $items[] = $this->salesInsightItem(
                'warning',
                'Hot leads perlu dikonversi',
                $this->pickSalesInsightTemplate('hot_leads_no_closing', [
                    'Hot leads sudah ada, tapi belum ada closing. Tim perlu memperjelas langkah berikutnya, urgency, dan offer yang paling relevan.',
                    'Ada hot leads yang bisa diprioritaskan. Karena closing belum terjadi, follow-up personal perlu dilakukan lebih spesifik.',
                    'Hot leads menunjukkan peluang yang cukup dekat. Fokusnya adalah mengunci komitmen dan membantu calon peserta mengambil keputusan.',
                ]),
                760,
                ['metric' => number_format($hotLeads) . ' hot leads']
            );
        }

        if ($consultation > 0 && $closedDeal <= 0) {
            $items[] = $this->salesInsightItem(
                'warning',
                'Consultation belum berubah jadi closing',
                $this->pickSalesInsightTemplate('consultation_no_closing', [
                    'Consultation sudah terjadi, tapi belum berubah menjadi closing. Cek kembali objection, kecocokan program, dan follow-up setelah konsultasi.',
                    'Ada consultation pada periode ini, namun closed deal belum terbentuk. Tim perlu memastikan calon peserta punya pilihan program dan deadline yang jelas.',
                    'Tahap consultation sudah berjalan. Fokus berikutnya adalah mengawal leads yang sudah konsultasi agar tidak berhenti di tengah funnel.',
                ]),
                730,
                ['metric' => number_format($consultation) . ' consultation']
            );
        }

        if ($badLeadRate >= 40 && $totalLeads > 0) {
            $items[] = $this->salesInsightItem(
                'warning',
                'Kualitas leads perlu dievaluasi',
                $this->pickSalesInsightTemplate('bad_lead_rate', [
                    'Porsi ignored, closed lost, dan not related cukup tinggi. Tim perlu mengecek source leads, targeting campaign, dan kualitas audience yang masuk.',
                    'Banyak leads belum relevan atau tidak lanjut. Ini bisa menjadi sinyal untuk memperbaiki targeting, copy campaign, atau kualifikasi awal.',
                    'Lead quality perlu diperhatikan karena cukup banyak leads yang tidak masuk funnel serius. Evaluasi source dan pesan promosi bisa membantu memperbaiki kualitas pipeline.',
                ]),
                690,
                ['metric' => number_format($badLeadRate, 1) . '%']
            );
        }

        if ($warmLeads > 0 && $hotLeads <= 0 && $closedDeal <= 0) {
            $items[] = $this->salesInsightItem(
                'info',
                'Warm leads perlu dinaikkan',
                $this->pickSalesInsightTemplate('warm_to_hot', [
                    'Warm leads sudah ada, tapi belum naik menjadi hot leads atau closing. Tim bisa memperkuat edukasi benefit, proof, dan reminder konsultasi.',
                    'Ada warm leads yang masih bisa dikembangkan. Fokusnya adalah nurturing dan memperjelas alasan mereka perlu lanjut ke tahap berikutnya.',
                    'Warm leads menunjukkan demand awal. Langkah berikutnya adalah mendorong mereka ke consultation atau hot leads dengan follow-up yang lebih spesifik.',
                ]),
                560,
                ['metric' => number_format($warmLeads) . ' warm leads']
            );
        }

        if ($current['revenue'] > 0 || $previous['revenue'] > 0) {
            if ((float) $current['revenue'] > (float) $previous['revenue']) {
                $items[] = $this->salesInsightItem(
                    'good',
                    'Revenue naik dibanding periode sebelumnya',
                    $this->pickSalesInsightTemplate('revenue_up', [
                        'Revenue periode ini lebih tinggi dibanding periode sebelumnya. Pola follow-up yang berhasil bisa dipertahankan untuk mendorong closing berikutnya.',
                        'Performa revenue bergerak naik. Tim bisa mempelajari source dan pendekatan follow-up yang paling banyak menghasilkan deal.',
                        'Ada peningkatan revenue dibanding periode sebelumnya. Momentum ini bisa diperkuat dengan mengawal leads yang masih hangat.',
                    ]),
                    520,
                    ['metric' => '+' . $this->formatCurrency(abs((float) $current['revenue'] - (float) $previous['revenue']))]
                );
            } elseif ((float) $current['revenue'] < (float) $previous['revenue']) {
                $items[] = $this->salesInsightItem(
                    'warning',
                    'Revenue turun dibanding periode sebelumnya',
                    $this->pickSalesInsightTemplate('revenue_down', [
                        'Revenue periode ini masih di bawah periode sebelumnya. Perlu cek apakah penyebabnya leads lebih rendah, consultation turun, atau closing belum bergerak.',
                        'Pemasukan turun dibanding periode sebelumnya. Fokuskan perhatian pada leads yang paling dekat ke consultation atau closing.',
                        'Revenue sedang menurun dibanding periode sebelumnya. Tim perlu memastikan pipeline aktif tidak kehilangan momentum follow-up.',
                    ]),
                    650,
                    ['metric' => '-' . $this->formatCurrency(abs((float) $current['revenue'] - (float) $previous['revenue']))]
                );
            }
        }

        if ($daysSinceLatestReport !== null && $daysSinceLatestReport >= 2) {
            $items[] = $this->salesInsightItem(
                'warning',
                'Sales report belum update beberapa hari',
                $this->pickSalesInsightTemplate('stale_report', [
                    'Sales report terakhir belum terlalu baru. Pastikan report harian tetap diisi agar management bisa membaca kondisi funnel secara akurat.',
                    'Update sales report terakhir sudah beberapa hari lalu. Data harian perlu dijaga supaya evaluasi leads, follow-up, dan revenue tidak tertinggal.',
                    'Laporan sales perlu diperbarui secara konsisten. Jika data terakhir sudah lewat beberapa hari, insight funnel bisa kurang mencerminkan kondisi terbaru.',
                ]),
                min(760, 600 + $daysSinceLatestReport),
                ['metric' => $daysSinceLatestReport . ' hari']
            );
        }

        $items = $this->sortSalesInsightItems($items);

        if (empty($items)) {
            $items[] = $this->salesInsightItem(
                'info',
                'Sales daily report siap dipantau',
                'Data sales daily report sudah tersedia. Pantau leads, interaksi, consultation, closing, dan revenue secara rutin agar pipeline tetap bergerak.',
                300
            );
        }

        $summaryText = $this->buildSalesDailyReportSummaryText(
            items: $items,
            current: $current,
            rates: $rates,
            dateFrom: $dateFrom,
            dateTo: $dateTo,
            daysSinceLatestReport: $daysSinceLatestReport
        );

        $funLine = $this->getSalesDailyReportFunLine(
            current: $current,
            rates: $rates,
            daysSinceLatestReport: $daysSinceLatestReport
        );

        return [
            'generated_at' => now()->format('d M Y H:i'),
            'source' => 'local',
            'source_label' => 'Smart Local Sales Insight',
            'mode' => 'local_smart',
            'headline' => $items[0]['title'] ?? 'Sales Daily Report Insight',
            'summary_text' => trim($summaryText . "\n\n" . $funLine),
            'fun_line' => $funLine,
            'items' => array_slice($items, 0, 6),
            'focus' => array_slice($this->buildSalesDailyReportFocus($items), 0, 4),
            'metrics' => [
                'date_from' => $dateFrom,
                'date_to' => $dateTo,
                'reports' => $reportCount,
                'total_leads' => $totalLeads,
                'interacted' => $interacted,
                'warm_leads' => $warmLeads,
                'hot_leads' => $hotLeads,
                'consultation' => $consultation,
                'closed_deal' => $closedDeal,
                'ignored' => $ignored,
                'closed_lost' => $closedLost,
                'not_related' => $notRelated,
                'revenue' => $revenue,
                'interaction_rate' => $interactionRate,
                'consultation_rate' => (float) $rates['consultation_rate'],
                'closing_rate' => (float) $rates['closing_rate'],
                'bad_lead_rate' => $badLeadRate,
                'previous' => [
                    'date_from' => $previousFrom,
                    'date_to' => $previousTo,
                    'total_leads' => (int) $previous['total_leads'],
                    'closed_deal' => (int) $previous['closed_deal'],
                    'revenue' => (float) $previous['revenue'],
                    'interaction_rate' => (float) $previousRates['interaction_rate'],
                ],
            ],
        ];
    }

    protected function resolveInsightPeriod(array $filters): array
    {
        $dateFrom = trim((string) ($filters['date_from'] ?? ''));
        $dateTo = trim((string) ($filters['date_to'] ?? ''));

        try {
            $from = $dateFrom !== ''
                ? Carbon::parse($dateFrom)->startOfDay()
                : now()->subDays(29)->startOfDay();
        } catch (\Throwable) {
            $from = now()->subDays(29)->startOfDay();
        }

        try {
            $to = $dateTo !== ''
                ? Carbon::parse($dateTo)->startOfDay()
                : now()->startOfDay();
        } catch (\Throwable) {
            $to = now()->startOfDay();
        }

        if ($from->greaterThan($to)) {
            [$from, $to] = [$to, $from];
        }

        return [
            $from->toDateString(),
            $to->toDateString(),
        ];
    }

    protected function getSalesDailyReportSummary(string $dateFrom, string $dateTo): array
    {
        $query = SalesDailyReport::query()
            ->whereDate('report_date', '>=', $dateFrom)
            ->whereDate('report_date', '<=', $dateTo);

        return [
            'reports' => (int) (clone $query)->count(),
            'total_leads' => (int) (clone $query)->sum('total_leads'),
            'interacted' => (int) (clone $query)->sum('interacted'),
            'ignored' => (int) (clone $query)->sum('ignored'),
            'closed_lost' => (int) (clone $query)->sum('closed_lost'),
            'not_related' => (int) (clone $query)->sum('not_related'),
            'warm_leads' => (int) (clone $query)->sum('warm_leads'),
            'hot_leads' => (int) (clone $query)->sum('hot_leads'),
            'consultation' => (int) (clone $query)->sum('consultation'),
            'closed_deal' => (int) (clone $query)->sum('closed_deal'),
            'revenue' => (float) (clone $query)->sum('revenue'),
        ];
    }

    protected function buildSalesDailyReportRates(array $summary): array
    {
        $totalLeads = max(0, (int) ($summary['total_leads'] ?? 0));
        $interacted = max(0, (int) ($summary['interacted'] ?? 0));
        $consultation = max(0, (int) ($summary['consultation'] ?? 0));
        $closedDeal = max(0, (int) ($summary['closed_deal'] ?? 0));
        $ignored = max(0, (int) ($summary['ignored'] ?? 0));
        $closedLost = max(0, (int) ($summary['closed_lost'] ?? 0));
        $notRelated = max(0, (int) ($summary['not_related'] ?? 0));
        $revenue = (float) ($summary['revenue'] ?? 0);

        $badLeadCount = $ignored + $closedLost + $notRelated;

        return [
            'interaction_rate' => $totalLeads > 0 ? round(($interacted / $totalLeads) * 100, 1) : 0,
            'consultation_rate' => $totalLeads > 0 ? round(($consultation / $totalLeads) * 100, 1) : 0,
            'closing_rate' => $totalLeads > 0 ? round(($closedDeal / $totalLeads) * 100, 1) : 0,
            'bad_lead_rate' => $totalLeads > 0 ? round(($badLeadCount / $totalLeads) * 100, 1) : 0,
            'revenue_per_deal' => $closedDeal > 0 ? round($revenue / $closedDeal) : 0,
            'revenue_per_lead' => $totalLeads > 0 ? round($revenue / $totalLeads) : 0,
        ];
    }

    protected function buildSalesDailyReportSummaryText(
        array $items,
        array $current,
        array $rates,
        string $dateFrom,
        string $dateTo,
        ?int $daysSinceLatestReport
    ): string {
        $reportCount = (int) ($current['reports'] ?? 0);
        $totalLeads = (int) ($current['total_leads'] ?? 0);
        $interacted = (int) ($current['interacted'] ?? 0);
        $consultation = (int) ($current['consultation'] ?? 0);
        $hotLeads = (int) ($current['hot_leads'] ?? 0);
        $closedDeal = (int) ($current['closed_deal'] ?? 0);
        $revenue = (float) ($current['revenue'] ?? 0);

        if ($reportCount <= 0) {
            return 'Belum ada sales daily report pada periode ini. Tim perlu melengkapi laporan harian agar leads, follow-up, consultation, closing, dan revenue bisa dipantau dengan akurat.';
        }

        if ($totalLeads <= 0) {
            return 'Sales report sudah tercatat, tapi leads belum masuk pada periode ini. Fokus berikutnya adalah memperkuat campaign, referral, dan follow-up database lama agar funnel sales mulai bergerak.';
        }

        if ($revenue > 0 && $closedDeal > 0) {
            $sentence = 'Revenue sales report periode ini sudah tercatat sebesar ' . $this->formatCurrency($revenue) . ' dari ' . number_format($closedDeal) . ' closed deal. Agar momentum tetap terjaga, tim perlu melanjutkan follow-up leads, consultation, dan hot leads yang masih berpotensi menjadi closing berikutnya.';

            if ($daysSinceLatestReport !== null && $daysSinceLatestReport >= 2) {
                $sentence .= ' Report harian juga perlu diperbarui agar insight tetap mencerminkan kondisi terbaru.';
            }

            return $sentence;
        }

        if ($closedDeal > 0 && $revenue <= 0) {
            return 'Closed deal sudah tercatat, tapi revenue belum masuk di report. Tim perlu memastikan apakah pembayaran belum terjadi, belum dikonfirmasi, atau angka revenue belum diisi.';
        }

        if ($consultation > 0 && $closedDeal <= 0) {
            return 'Leads sudah bergerak sampai tahap consultation, tapi belum berubah menjadi closed deal. Prioritas tim adalah mengawal calon peserta setelah konsultasi, memperjelas offer, dan memastikan langkah berikutnya tidak menggantung.';
        }

        if ($hotLeads > 0 && $closedDeal <= 0) {
            return 'Hot leads sudah ada, tapi closed deal belum terbentuk. Tim perlu memprioritaskan follow-up personal, memperjelas urgency, dan membantu calon peserta mengambil keputusan.';
        }

        if ($interacted > 0 && $closedDeal <= 0) {
            return 'Leads dan interaksi sudah berjalan, tapi belum ada closed deal. Fokus berikutnya adalah mendorong consultation, mengawal leads hangat, dan memperjelas next step calon peserta.';
        }

        $primary = $items[0]['message'] ?? 'Sales daily report sudah siap dipantau.';
        $secondary = $items[1]['message'] ?? null;

        $summary = $primary;

        if ($secondary) {
            $summary .= ' ' . $secondary;
        }

        return $this->limitWords($summary, 85);
    }

    protected function getSalesDailyReportFunLine(
        array $current,
        array $rates,
        ?int $daysSinceLatestReport
    ): string {
        $reportCount = (int) ($current['reports'] ?? 0);
        $totalLeads = (int) ($current['total_leads'] ?? 0);
        $interacted = (int) ($current['interacted'] ?? 0);
        $hotLeads = (int) ($current['hot_leads'] ?? 0);
        $consultation = (int) ($current['consultation'] ?? 0);
        $closedDeal = (int) ($current['closed_deal'] ?? 0);
        $revenue = (float) ($current['revenue'] ?? 0);
        $badLeadRate = (float) ($rates['bad_lead_rate'] ?? 0);

        $contextKey = 'default';

        if ($reportCount <= 0) {
            $contextKey = 'no_report';
        } elseif ($totalLeads <= 0) {
            $contextKey = 'no_leads';
        } elseif ($interacted <= 0) {
            $contextKey = 'no_interaction';
        } elseif ($revenue > 0 && $closedDeal > 0) {
            $contextKey = 'revenue';
        } elseif ($closedDeal > 0 && $revenue <= 0) {
            $contextKey = 'closing_no_revenue';
        } elseif ($consultation > 0 && $closedDeal <= 0) {
            $contextKey = 'consultation';
        } elseif ($hotLeads > 0 && $closedDeal <= 0) {
            $contextKey = 'hot_leads';
        } elseif ($badLeadRate >= 40) {
            $contextKey = 'lead_quality';
        } elseif ($daysSinceLatestReport !== null && $daysSinceLatestReport >= 2) {
            $contextKey = 'stale_report';
        }

        $lines = match ($contextKey) {
            'no_report' => [
                "Ke warung beli ketoprak,\nTambah kerupuk biar ramai.\nReport sales jangan ngumpet dulu, kak,\nNanti robot bengong sampai damai.",
                "Jalan pagi pakai sepatu,\nLewat taman lihat merpati.\nReport harian isi dulu,\nBiar dashboard nggak nebak pakai intuisi.",
                "Beli kopi dekat kelurahan,\nGelasnya panas jangan digenggam.\nKalau report belum kelihatan,\nRobot ikut hening, sales ikut tegang.",
                "Ke pasar cari ikan tuna,\nPulangnya bawa daun pepaya.\nReport kosong bikin hampa suasana,\nYuk isi data biar sales bergaya.",
                "Naik angkot ke Pasar Minggu,\nSopirnya santai sambil bernyanyi.\nReport harian jangan ditunggu,\nBiar funnel nggak jadi misteri.",
                "Beli siomay pakai sambal,\nMakannya jangan sambil berlari.\nReport kosong bukan hal fatal,\nAsal diisi sebelum lupa diri.",
                "Ke kantor bawa bekal nasi,\nLauknya telur sama teri.\nReport harian perlu diisi,\nBiar management nggak cenayang sendiri.",
                "Pagi-pagi minum jamu,\nRasanya pahit tapi berguna.\nReport sales ditunggu-tunggu,\nBiar robot punya bahan bicara.",
            ],
            'no_leads' => [
                "Ke pasar beli semangka,\nPulangnya bawa buah naga.\nLeads belum masuk tak apa,\nYang penting campaign jangan rebahan juga.",
                "Beli bakso di pinggir jalan,\nKuahnya panas bikin semangat.\nKalau leads belum berdatangan,\nCopy campaign perlu dibuat lebih memikat.",
                "Jalan sore ke toko bunga,\nPilih melati warna putih.\nLeads sepi bukan bencana,\nCoba angle baru biar lebih menggigit.",
                "Beli es teh dekat sekolah,\nMinumnya santai di bawah tangga.\nLeads belum masuk jangan gelisah,\nDistribusi landing page gas lagi aja.",
                "Ke minimarket beli roti,\nSekalian ambil air mineral.\nLeads belum muncul hari ini,\nBesok targeting kita bikin lebih brutal.",
                "Naik motor ke arah Bekasi,\nLampu merahnya lama sekali.\nLeads belum masuk jangan emosi,\nHeadline mungkin kurang bikin jatuh hati.",
                "Beli martabak rasa coklat,\nDimakan bareng teh hangat.\nLeads belum banyak terlihat,\nOutreach jangan ikut tamat.",
                "Ke toko buku cari kamus,\nPulangnya beli gorengan.\nLeads kosong jangan terlalu serius,\nYang penting promosi tetap jalan.",
            ],
            'no_interaction' => [
                "Ke kantin beli risol,\nTambah cabai biar menggigit.\nLeads jangan cuma jadi penghuni Excel,\nSapa dulu biar nggak pamit.",
                "Beli kopi di pinggir kali,\nKopinya panas jangan ditinggal.\nLeads sudah masuk hari ini,\nFollow-up cepat biar nggak menghilang.",
                "Jalan sore lihat layangan,\nPutus benang jatuh ke kali.\nLeads sudah ada di genggaman,\nJangan jadi arsip abadi.",
                "Ke pasar beli ikan teri,\nPulangnya lewat gang sempit.\nLeads baru jangan dicuekin sendiri,\nNanti pindah hati sebelum sempat klik.",
                "Beli soto pakai koya,\nTambah jeruk biar segar.\nLeads masuk itu tanda bahagia,\nFollow-up awal jangan sampai kelar.",
                "Naik ojek ke Kemang,\nLewat jalan agak miring.\nLeads jangan cuma dipandang,\nChat dulu sebelum jadi dingin.",
                "Beli donat rasa stroberi,\nDimakan sambil duduk santai.\nLeads masuk jangan dibiarkan sepi,\nNanti peluang bilang dadah-bye.",
                "Ke taman lihat kupu-kupu,\nWarnanya cantik bikin tenang.\nLeads baru jangan malu-malu,\nSapa cepat biar peluang datang.",
            ],
            'revenue' => [
                "Jalan sore membeli cincau,\nDompet aman hati pun tenang.\nRevenue sudah mulai terpantau,\nGas follow-up biar makin menang.",
                "Ke toko roti beli brownies,\nDimakan bareng teh hangat.\nRevenue masuk bikin optimis,\nPipeline tetap harus dirawat.",
                "Beli nasi pakai rendang,\nTambah sambal biar pedas.\nRevenue masuk bikin senang,\nFollow-up jangan sampai lepas.",
                "Pergi pagi membeli bubur,\nTambah kerupuk biar mantap.\nRevenue masuk bikin bersyukur,\nClosing berikutnya kita sikat.",
                "Dari leads jadi pembayaran,\nPerjalanannya penuh perjuangan.\nRevenue sudah dalam catatan,\nSales lanjut dengan senyuman.",
                "Ke pasar membeli durian,\nPilih yang matang jangan yang mentah.\nRevenue masuk jadi pencapaian,\nBesok closing lagi jangan menyerah.",
                "Beli kopi rasa vanila,\nDiminum sambil duduk santai.\nRevenue masuk bikin gembira,\nLeads hangat jangan ditinggal lari.",
                "Makan siang lauknya ikan,\nMinumnya teh dalam gelas.\nRevenue boleh dirayakan,\nTapi follow-up tetap gas.",
                "Naik sepeda ke pinggir taman,\nMelihat bunga mulai berkembang.\nRevenue masuk tanda aman,\nTarget berikutnya tetap ditendang.",
                "Beli cilok dekat parkiran,\nBumbunya pedas bikin ketawa.\nRevenue masuk dalam laporan,\nSales boleh senyum, jangan terlena.",
            ],
            'closing_no_revenue' => [
                "Makan siang pakai sambal,\nMinumnya teh rasa leci.\nDeal sudah berhasil dikawal,\nPayment jangan lupa dikunci.",
                "Ke minimarket beli cemilan,\nAmbil juga minuman leci.\nClosing sudah jadi kenyataan,\nRevenue perlu dikonfirmasi.",
                "Beli sepatu warna biru,\nDipakai jalan ke kantor.\nDeal sudah bilang setuju,\nCek payment biar makin proper.",
                "Ke pasar membeli ikan patin,\nPulang lewat jalan raya.\nDeal sudah berhasil dipimpin,\nSekarang pastikan masuk dananya.",
                "Beli batik warna coklat,\nDipakai rapat hari Selasa.\nDeal sudah terlihat dekat,\nPayment jangan pura-pura puasa.",
                "Naik kereta ke Tanah Abang,\nTurun sebentar beli minuman.\nClosing sudah mulai terang,\nTinggal rapikan pembayaran.",
                "Pergi pagi membawa map,\nIsinya dokumen dan kuitansi.\nClosed deal sudah lengkap,\nRevenue jangan sampai sembunyi.",
                "Beli ketan di pinggir jalan,\nTambah kelapa biar gurih.\nDeal sudah masuk laporan,\nPayment jangan dibuat sedih.",
            ],
            'consultation' => [
                "Beli batik warna kecoklatan,\nDipakai rapat sama pimpinan.\nSudah ada consultation,\nTinggal dorong jadi keputusan.",
                "Ke toko alat tulis beli pulpen,\nPilih warna hitam elegan.\nConsultation sudah berjalan,\nFollow-up lanjut jangan sungkan.",
                "Beli donat rasa pandan,\nDimakan sambil lihat hujan.\nConsultation sudah di tangan,\nClosing butuh arahan lanjutan.",
                "Pergi ke kelas bawa catatan,\nCatat materi biar tak lupa.\nConsultation itu kesempatan,\nFollow-up rapi deal terbuka.",
                "Naik sepeda ke taman kota,\nBerhenti sebentar dekat gerbang.\nKalau konsultasi sudah tercipta,\nNext step jangan dibuat menggantung.",
                "Beli soto di hari Senin,\nTambah jeruk biar segar.\nConsultation sudah makin yakin,\nFollow-up rapi biar deal kelar.",
                "Pagi-pagi membeli ketan,\nDuduk santai di halaman.\nConsultation butuh kelanjutan,\nBiar prospek ambil keputusan.",
                "Ke pasar beli buah naga,\nPulangnya lewat jalan kecil.\nConsultation jangan sia-sia,\nBantu prospek sampai deal.",
            ],
            'hot_leads' => [
                "Ke toko beli gantungan,\nJangan lupa bayar parkir.\nHot leads sudah di genggaman,\nBantu ambil keputusan akhir.",
                "Beli sate di pinggir jalan,\nDibungkus pakai daun pisang.\nHot leads butuh kepastian,\nJangan sampai peluangnya hilang.",
                "Ke pasar beli mangga matang,\nPilih yang harum dan manis.\nHot leads sudah mulai datang,\nFollow-up personal jangan tipis.",
                "Naik kereta ke Sudirman,\nTurun sebentar beli minuman.\nHot leads butuh keyakinan,\nKasih offer paling relevan.",
                "Makan malam lauknya ikan,\nTambah sayur biar seimbang.\nHot leads perlu diarahkan,\nBiar closing segera datang.",
                "Jalan pagi membeli bubur,\nTambah kerupuk biar nikmat.\nHot leads jangan sampai kabur,\nFollow-up rapi bikin dekat.",
                "Ke toko bunga beli melati,\nWarnanya putih menawan hati.\nHot leads jangan dibiarkan sepi,\nBeri alasan untuk transaksi.",
                "Beli es kelapa di pinggir jalan,\nMinumnya sambil duduk santai.\nHot leads sudah kasih harapan,\nClosing tinggal dirapikan nanti.",
            ],
            'lead_quality' => [
                "Naik motor ke Cibubur,\nBan depannya agak goyang.\nKalau leads banyak yang kabur,\nCek audience dan pesan yang tayang.",
                "Beli kacamata di toko lama,\nPilih frame warna abu.\nKalau leads banyak tak sama,\nTargeting perlu dicek dulu.",
                "Ke pasar membeli kangkung,\nJangan lupa beli cabai.\nKalau leads banyak menggantung,\nCopy campaign perlu dirapikan lagi.",
                "Beli roti rasa stroberi,\nDimakan sambil minum kopi.\nKalau leads kurang relevan lagi,\nCek channel dan strategi.",
                "Malam hari lihat bintang,\nDuduk santai di depan rumah.\nKalau leads sering menghilang,\nSegmentasi perlu dibenahi arah.",
                "Ke toko buah beli semangka,\nPilih yang merah dan segar.\nKalau leads banyak tak suka,\nPesan campaign perlu ditakar.",
                "Naik angkot ke pasar lama,\nTurun sebentar beli jamu.\nLead quality perlu utama,\nBiar sales tidak terlalu sendu.",
                "Pagi cerah pergi ke kota,\nLewat jalan penuh reklame.\nKalau leads kurang tepat sasaran,\nPerbaiki audience dan campaign.",
            ],
            'stale_report' => [
                "Burung dara burung merpati,\nHinggap sebentar di atas pagar.\nReport jangan ditunda lagi,\nBiar dashboard tetap segar.",
                "Ke warung beli nasi uduk,\nDibungkus rapi pakai kertas.\nReport lama bikin data ngantuk,\nYuk update biar insight jelas.",
                "Beli kopi di pagi hari,\nKopinya pahit tapi nikmat.\nReport jangan ditunda lagi,\nBiar evaluasi tetap akurat.",
                "Naik sepeda ke taman kota,\nBerhenti sebentar lihat bunga.\nReport harian penting juga,\nBiar funnel bukan teka-teki semata.",
                "Beli ketoprak dekat stasiun,\nTambah kerupuk biar mantap.\nReport lama bikin bingung,\nUpdate data biar lengkap.",
                "Jalan sore lewat jembatan,\nMelihat sungai mengalir deras.\nReport harian jangan ketinggalan,\nBiar kondisi sales terbaca jelas.",
                "Pergi rapat membawa pena,\nCatatan rapi dalam map.\nKalau report belum terbaru,\nInsight dashboard kurang mantap.",
                "Makan siang pakai lalapan,\nTambah sambal biar terasa.\nReport perlu diperbarui harian,\nAgar keputusan tidak meraba-raba.",
            ],
            default => [
                "Leads datang silih berganti,\nAda yang hot ada yang ghosting.\nSales jangan patah hati,\nBesok bisa jadi closing.",
                "Ke pasar beli semangka,\nJangan lupa beli pepaya.\nKalau hari ini belum closing juga,\nTenang bro, follow-up tetap jalan ya.",
                "Beli kopi rasa vanila,\nDiminum sambil lihat langit.\nSales itu bukan cuma angka,\nTapi follow-up yang legit.",
                "Jalan sore cari angin,\nKetemu kucing di parkiran.\nKalau leads belum yakin,\nBantu pelan dengan penjelasan.",
                "Ke toko buku beli kamus,\nPulangnya beli gorengan.\nSales jangan terlalu serius,\nYang penting terukur harian.",
                "Kalau hari belum pecah telur,\nJangan panik di dashboard.\nBesok follow-up lebih teratur,\nClosing bisa jadi reward.",
                "Makan siang sambil diskusi,\nMinum teh hangat di gelas.\nSales butuh konsistensi,\nBukan cuma semangat sekilas.",
                "Ke kantor membawa bekal,\nIsinya nasi dan ayam.\nClosing memang butuh akal,\nFollow-up juga perlu nyaman.",
            ],
        };

        return $lines[random_int(0, count($lines) - 1)];
    }

    protected function buildSalesDailyReportFocus(array $items): array
    {
        $focus = [];

        foreach ($items as $item) {
            $title = strtolower((string) ($item['title'] ?? ''));
            $type = (string) ($item['type'] ?? 'info');

            if (str_contains($title, 'report')) {
                $focus[] = $this->salesInsightFocusItem($type, 'Lengkapi report harian', 'Pastikan data sales daily report diisi konsisten agar performa funnel bisa dipantau akurat.');
            } elseif (str_contains($title, 'leads belum masuk')) {
                $focus[] = $this->salesInsightFocusItem($type, 'Perkuat sumber leads', 'Cek campaign, referral, channel promosi, dan database lama untuk membuka pipeline baru.');
            } elseif (str_contains($title, 'interaksi') || str_contains($title, 'interaction')) {
                $focus[] = $this->salesInsightFocusItem($type, 'Percepat kontak awal', 'Pastikan leads baru segera dihubungi dan status follow-up tercatat jelas.');
            } elseif (str_contains($title, 'hot leads')) {
                $focus[] = $this->salesInsightFocusItem($type, 'Prioritaskan hot leads', 'Lakukan follow-up personal untuk leads yang paling siap lanjut ke keputusan.');
            } elseif (str_contains($title, 'consultation')) {
                $focus[] = $this->salesInsightFocusItem($type, 'Kawal setelah konsultasi', 'Pastikan calon peserta punya offer, deadline, dan langkah berikutnya yang jelas.');
            } elseif (str_contains($title, 'closing')) {
                $focus[] = $this->salesInsightFocusItem($type, 'Dorong closed deal', 'Fokus ke leads hangat dan consultation yang paling dekat menjadi closing.');
            } elseif (str_contains($title, 'revenue')) {
                $focus[] = $this->salesInsightFocusItem($type, 'Jaga momentum revenue', 'Lanjutkan follow-up leads, consultation, dan hot leads yang masih berpotensi menjadi pembayaran.');
            } elseif (str_contains($title, 'kualitas')) {
                $focus[] = $this->salesInsightFocusItem($type, 'Evaluasi kualitas leads', 'Review source leads, targeting, dan pesan campaign agar audience lebih relevan.');
            }
        }

        if (empty($focus)) {
            $focus = [
                $this->salesInsightFocusItem('info', 'Pantau funnel sales', 'Review leads, interaction, consultation, closed deal, dan revenue secara rutin.'),
                $this->salesInsightFocusItem('info', 'Jaga follow-up aktif', 'Pastikan leads hangat tidak berhenti di tengah funnel.'),
            ];
        }

        return $this->uniqueSalesInsightFocus($focus);
    }

    protected function salesInsightItem(string $type, string $title, string $message, int $score = 0, array $meta = []): array
    {
        return array_merge([
            'type' => $type,
            'level' => $type,
            'title' => $title,
            'message' => $message,
            'description' => $message,
            'score' => $score,
        ], $meta);
    }

    protected function salesInsightFocusItem(string $type, string $title, string $message): array
    {
        return [
            'type' => $type,
            'level' => $type,
            'title' => $title,
            'message' => $message,
            'description' => $message,
        ];
    }

    protected function sortSalesInsightItems(array $items): array
    {
        usort($items, function (array $a, array $b) {
            $scoreA = (int) ($a['score'] ?? 0);
            $scoreB = (int) ($b['score'] ?? 0);

            if ($scoreA === $scoreB) {
                return $this->salesInsightSeverityWeight($b['type'] ?? $b['level'] ?? 'info')
                    <=> $this->salesInsightSeverityWeight($a['type'] ?? $a['level'] ?? 'info');
            }

            return $scoreB <=> $scoreA;
        });

        return $items;
    }

    protected function salesInsightSeverityWeight(string $type): int
    {
        return match ($type) {
            'critical' => 5,
            'warning' => 4,
            'action' => 3,
            'good' => 2,
            'info' => 1,
            default => 0,
        };
    }

    protected function pickSalesInsightTemplate(string $key, array $templates): string
    {
        if (empty($templates)) {
            return '';
        }

        $seed = now()->format('Y-m-d') . '|sales-daily-report|' . $key;
        $index = abs(crc32($seed)) % count($templates);

        return $templates[$index];
    }

    protected function uniqueSalesInsightFocus(array $focus): array
    {
        $seen = [];
        $unique = [];

        foreach ($focus as $item) {
            $title = (string) ($item['title'] ?? '');

            if ($title === '' || isset($seen[$title])) {
                continue;
            }

            $seen[$title] = true;
            $unique[] = $item;
        }

        return $unique;
    }

    protected function formatCurrency(float $amount): string
    {
        return 'Rp ' . number_format($amount, 0, ',', '.');
    }

    protected function limitWords(string $text, int $limit): string
    {
        $words = preg_split('/\s+/', trim($text));

        if (! $words || count($words) <= $limit) {
            return trim($text);
        }

        return implode(' ', array_slice($words, 0, $limit)) . '...';
    }
}
