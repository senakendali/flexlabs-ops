<?php

namespace App\Services\MetaAds;

use App\Models\MetaAdsAiReport;
use App\Models\MetaAdsCampaignInsight;
use App\Services\Ai\GeminiClientService;
use Illuminate\Support\Collection;
use Throwable;

class MetaAdsAiAnalysisService
{
    public function __construct(
        protected GeminiClientService $geminiClientService
    ) {}

    public function analyzeLatestCampaigns(): array
    {
        $latestDateStop = MetaAdsCampaignInsight::query()->max('date_stop');

        if (! $latestDateStop) {
            return [
                'generated' => 0,
                'message' => 'No Meta Ads campaign insights available.',
            ];
        }

        $campaigns = MetaAdsCampaignInsight::query()
            ->whereDate('date_stop', $latestDateStop)
            ->orderByDesc('spend')
            ->get();

        $generated = 0;

        foreach ($campaigns as $campaign) {
            $this->analyzeCampaign($campaign);
            $generated++;
        }

        $this->analyzeOverview($campaigns);

        return [
            'generated' => $generated,
            'message' => "Generated AI analysis for {$generated} campaign(s).",
        ];
    }

    protected function analyzeCampaign(MetaAdsCampaignInsight $campaign): MetaAdsAiReport
    {
        $snapshot = $this->campaignSnapshot($campaign);

        $output = $this->geminiClientService->generateJson(
            $this->buildCampaignPrompt($snapshot)
        );

        return MetaAdsAiReport::updateOrCreate(
            [
                'report_type' => 'campaign',
                'campaign_id' => $campaign->campaign_id,
                'date_start' => $campaign->date_start?->toDateString(),
                'date_stop' => $campaign->date_stop?->toDateString(),
            ],
            [
                'campaign_name' => $campaign->campaign_name,
                'provider' => 'gemini',
                'model' => config('services.gemini.model'),
                'input_snapshot' => $snapshot,
                'output' => $output,
                'summary_text' => $output['summary'] ?? null,
                'health_status' => $output['health_status'] ?? null,
                'main_bottleneck' => $output['main_bottleneck'] ?? null,
                'generated_at' => now(),
            ]
        );
    }

    protected function analyzeOverview(Collection $campaigns): MetaAdsAiReport
    {
        $snapshot = [
            'period' => [
                'date_start' => optional($campaigns->min('date_start'))->toDateString(),
                'date_stop' => optional($campaigns->max('date_stop'))->toDateString(),
            ],
            'campaigns' => $campaigns
                ->map(fn (MetaAdsCampaignInsight $campaign) => $this->campaignSnapshot($campaign))
                ->values()
                ->all(),
        ];

        $output = $this->geminiClientService->generateJson(
            $this->buildOverviewPrompt($snapshot)
        );

        return MetaAdsAiReport::updateOrCreate(
            [
                'report_type' => 'overview',
                'campaign_id' => null,
                'date_start' => $snapshot['period']['date_start'],
                'date_stop' => $snapshot['period']['date_stop'],
            ],
            [
                'campaign_name' => null,
                'provider' => 'gemini',
                'model' => config('services.gemini.model'),
                'input_snapshot' => $snapshot,
                'output' => $output,
                'summary_text' => $output['summary'] ?? null,
                'health_status' => $output['health_status'] ?? null,
                'main_bottleneck' => $output['main_bottleneck'] ?? null,
                'generated_at' => now(),
            ]
        );
    }

    protected function campaignSnapshot(MetaAdsCampaignInsight $campaign): array
    {
        $spend = (float) $campaign->spend;
        $linkClick = (int) ($campaign->link_click ?: $campaign->inline_link_clicks);
        $lead = (int) $campaign->lead_form_submission;
        $whatsapp = (int) $campaign->whatsapp_chat;

        return [
            'campaign_id' => $campaign->campaign_id,
            'campaign_name' => $campaign->campaign_name,
            'date_start' => $campaign->date_start?->toDateString(),
            'date_stop' => $campaign->date_stop?->toDateString(),

            'spend' => $spend,
            'reach' => (int) $campaign->reach,
            'impressions' => (int) $campaign->impressions,
            'frequency' => (float) $campaign->frequency,
            'engagement' => (int) $campaign->engagement,
            'link_click' => $linkClick,
            'lead_form_submission' => $lead,
            'whatsapp_chat' => $whatsapp,

            'ctr' => (float) $campaign->ctr,
            'cpc' => (float) $campaign->cpc,
            'cpm' => (float) $campaign->cpm,

            'cost_per_lead' => $lead > 0 ? round($spend / $lead, 2) : null,
            'cost_per_whatsapp_chat' => $whatsapp > 0 ? round($spend / $whatsapp, 2) : null,

            'lead_conversion_rate' => $linkClick > 0 ? round(($lead / $linkClick) * 100, 1) : 0,
            'whatsapp_conversion_rate' => $linkClick > 0 ? round(($whatsapp / $linkClick) * 100, 1) : 0,
        ];
    }

    protected function buildCampaignPrompt(array $campaign): string
    {
        return 'Kamu adalah Meta Ads Performance Analyst untuk bisnis edukasi/workshop.

Analisa 1 campaign berikut berdasarkan funnel:
reach → impressions → frequency → engagement → link click → lead form submission → WhatsApp chat.

Tugas:
1. Berikan summary singkat.
2. Tentukan health_status: healthy, warning, critical, atau monitor.
3. Tentukan main_bottleneck.
4. Berikan blocking_factors berisi factor, evidence, severity.
5. Berikan recommended_steps step-by-step yang praktis.
6. Jangan mengarang data di luar JSON.
7. Semua rekomendasi harus berdasarkan angka.

Balas hanya JSON valid dengan struktur:
{
  "summary": "...",
  "health_status": "healthy|warning|critical|monitor",
  "main_bottleneck": "...",
  "blocking_factors": [
    {
      "factor": "...",
      "evidence": "...",
      "severity": "low|medium|high"
    }
  ],
  "recommended_steps": [
    "..."
  ]
}

Data campaign:
' . json_encode($campaign, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
    }

    protected function buildOverviewPrompt(array $snapshot): string
    {
        return 'Kamu adalah Meta Ads Performance Analyst untuk dashboard management.

Analisa semua campaign berikut.
Fokus pada:
- campaign terbaik
- campaign yang boros
- faktor penghambat utama
- prioritas langkah optimasi
- lead form submission dan WhatsApp chat

Balas hanya JSON valid dengan struktur:
{
  "summary": "...",
  "health_status": "healthy|warning|critical|monitor",
  "main_bottleneck": "...",
  "best_campaign": "...",
  "attention_campaigns": ["..."],
  "blocking_factors": [
    {
      "factor": "...",
      "evidence": "...",
      "severity": "low|medium|high"
    }
  ],
  "recommended_steps": [
    "..."
  ]
}

Data:
' . json_encode($snapshot, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
    }
}