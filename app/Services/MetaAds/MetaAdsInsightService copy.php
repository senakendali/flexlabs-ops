<?php

namespace App\Services\MetaAds;

use App\Models\MetaAdsCampaignInsight;
use Illuminate\Support\Facades\Http;
use RuntimeException;

class MetaAdsInsightService
{
    public function syncCampaignInsights(string $datePreset = 'last_7d'): array
    {
        $version = config('services.meta_ads.graph_version');
        $token = config('services.meta_ads.access_token');
        $adAccountId = config('services.meta_ads.ad_account_id');

        if (! $token || ! $adAccountId) {
            throw new RuntimeException('Meta Ads config is missing. Check META_ACCESS_TOKEN and META_AD_ACCOUNT_ID.');
        }

        $url = "https://graph.facebook.com/{$version}/{$adAccountId}/insights";

        $response = Http::get($url, [
            'level' => 'campaign',
            'fields' => implode(',', [
                'campaign_id',
                'campaign_name',
                'spend',
                'reach',
                'impressions',
                'frequency',
                'clicks',
                'inline_link_clicks',
                'ctr',
                'cpc',
                'cpm',
                'actions',
                'cost_per_action_type',
                'date_start',
                'date_stop',
            ]),
            'date_preset' => $datePreset,
            'access_token' => $token,
        ]);

        if (! $response->successful()) {
            throw new RuntimeException('Meta API error: ' . $response->body());
        }

        $payload = $response->json();
        $rows = $payload['data'] ?? [];

        foreach ($rows as $row) {
            $normalized = $this->normalizeCampaignInsight($row, $adAccountId);

            MetaAdsCampaignInsight::updateOrCreate(
                [
                    'campaign_id' => $normalized['campaign_id'],
                    'date_start' => $normalized['date_start'],
                    'date_stop' => $normalized['date_stop'],
                ],
                $normalized
            );
        }

        return [
            'synced' => count($rows),
            'data' => $rows,
        ];
    }

    private function normalizeCampaignInsight(array $row, string $adAccountId): array
    {
        $actions = $row['actions'] ?? [];
        $costPerActions = $row['cost_per_action_type'] ?? [];

        $spend = (float) ($row['spend'] ?? 0);

        $engagement = $this->actionValue($actions, [
            'post_engagement',
            'page_engagement',
        ]);

        $linkClick = $this->actionValue($actions, [
            'link_click',
        ]);

        $leadFormSubmission = $this->actionValue($actions, [
            'lead',
            'onsite_conversion.lead_grouped',
            'offsite_complete_registration_add_meta_leads',
            'onsite_conversion.lead',
            'onsite_web_lead',
        ]);

        $whatsappChat = $this->actionValue($actions, [
            'onsite_conversion.messaging_conversation_started_7d',
            'onsite_conversion.total_messaging_connection',
            'onsite_conversion.messaging_first_reply',
        ]);

        return [
            'ad_account_id' => $adAccountId,

            'campaign_id' => $row['campaign_id'] ?? null,
            'campaign_name' => $row['campaign_name'] ?? null,

            'date_start' => $row['date_start'] ?? null,
            'date_stop' => $row['date_stop'] ?? null,

            'spend' => $spend,
            'reach' => (int) ($row['reach'] ?? 0),
            'impressions' => (int) ($row['impressions'] ?? 0),
            'frequency' => (float) ($row['frequency'] ?? 0),

            'clicks' => (int) ($row['clicks'] ?? 0),
            'inline_link_clicks' => (int) ($row['inline_link_clicks'] ?? 0),

            'ctr' => (float) ($row['ctr'] ?? 0),
            'cpc' => (float) ($row['cpc'] ?? 0),
            'cpm' => (float) ($row['cpm'] ?? 0),

            'engagement' => $engagement,
            'link_click' => $linkClick,
            'lead_form_submission' => $leadFormSubmission,
            'whatsapp_chat' => $whatsappChat,

            'cost_per_lead' => $leadFormSubmission > 0
                ? round($spend / $leadFormSubmission, 2)
                : null,

            'cost_per_whatsapp_chat' => $whatsappChat > 0
                ? round($spend / $whatsappChat, 2)
                : null,

            'actions' => $actions,
            'cost_per_action_type' => $costPerActions,
            'raw_payload' => $row,
        ];
    }

    private function actionValue(array $actions, array $types): int
    {
        foreach ($types as $type) {
            foreach ($actions as $action) {
                if (($action['action_type'] ?? null) === $type) {
                    return (int) ($action['value'] ?? 0);
                }
            }
        }

        return 0;
    }
}