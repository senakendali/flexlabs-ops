<?php

namespace App\Services\Trello;

use App\Models\TrelloIntegration;
use Illuminate\Http\Client\RequestException;
use Illuminate\Support\Facades\Http;
use InvalidArgumentException;
use Throwable;

class TrelloWebhookRegistrar
{
    public function register(TrelloIntegration $integration, bool $forceCreate = false): array
    {
        $this->validateIntegration($integration);

        $callbackUrl = $this->resolveCallbackUrl($integration);
        $description = $this->buildDescription($integration);

        if ($integration->webhook_id && ! $forceCreate) {
            try {
                $webhook = $this->updateWebhook(
                    integration: $integration,
                    callbackUrl: $callbackUrl,
                    description: $description
                );

                $integration->forceFill([
                    'webhook_id' => $webhook['id'] ?? $integration->webhook_id,
                    'callback_url' => $webhook['callbackURL'] ?? $callbackUrl,
                    'status' => 'active',
                    'is_active' => true,
                    'last_registered_at' => now(),
                    'last_error' => null,
                    'raw_payload' => $webhook,
                ])->save();

                return [
                    'mode' => 'updated',
                    'webhook' => $webhook,
                ];
            } catch (RequestException $exception) {
                // Kalau webhook lama sudah tidak valid / tidak ditemukan,
                // kita fallback create webhook baru.
                if (! in_array(optional($exception->response)->status(), [400, 404], true)) {
                    throw $exception;
                }
            }
        }

        $webhook = $this->createWebhook(
            integration: $integration,
            callbackUrl: $callbackUrl,
            description: $description
        );

        $integration->forceFill([
            'webhook_id' => $webhook['id'] ?? null,
            'callback_url' => $webhook['callbackURL'] ?? $callbackUrl,
            'status' => 'active',
            'is_active' => true,
            'last_registered_at' => now(),
            'last_error' => null,
            'raw_payload' => $webhook,
        ])->save();

        return [
            'mode' => 'created',
            'webhook' => $webhook,
        ];
    }

    public function getWebhook(TrelloIntegration $integration): ?array
    {
        if (! $integration->webhook_id) {
            return null;
        }

        $this->validateIntegration($integration);

        return Http::acceptJson()
            ->timeout(20)
            ->retry(2, 500)
            ->get($this->url(
                path: '/tokens/' . rawurlencode($integration->api_token) . '/webhooks/' . rawurlencode($integration->webhook_id),
                query: [
                    'key' => $integration->api_key,
                    'token' => $integration->api_token,
                ]
            ))
            ->throw()
            ->json();
    }

    private function createWebhook(
        TrelloIntegration $integration,
        string $callbackUrl,
        string $description
    ): array {
        return Http::acceptJson()
            ->timeout(20)
            ->retry(2, 500)
            ->post($this->url(
                path: '/tokens/' . rawurlencode($integration->api_token) . '/webhooks',
                query: [
                    'key' => $integration->api_key,
                    'token' => $integration->api_token,
                    'callbackURL' => $callbackUrl,
                    'idModel' => $integration->trello_board_id,
                    'description' => $description,
                ]
            ))
            ->throw()
            ->json();
    }

    private function updateWebhook(
        TrelloIntegration $integration,
        string $callbackUrl,
        string $description
    ): array {
        return Http::acceptJson()
            ->timeout(20)
            ->retry(2, 500)
            ->put($this->url(
                path: '/tokens/' . rawurlencode($integration->api_token) . '/webhooks/' . rawurlencode($integration->webhook_id),
                query: [
                    'key' => $integration->api_key,
                    'token' => $integration->api_token,
                    'callbackURL' => $callbackUrl,
                    'idModel' => $integration->trello_board_id,
                    'description' => $description,
                ]
            ))
            ->throw()
            ->json();
    }

    private function validateIntegration(TrelloIntegration $integration): void
    {
        if (! $integration->api_key) {
            throw new InvalidArgumentException("API key kosong untuk integration: {$integration->source_key}");
        }

        if (! $integration->api_token) {
            throw new InvalidArgumentException("API token kosong untuk integration: {$integration->source_key}");
        }

        if (! $integration->trello_board_id) {
            throw new InvalidArgumentException("Trello board ID kosong untuk integration: {$integration->source_key}");
        }
    }

    private function resolveCallbackUrl(TrelloIntegration $integration): string
    {
        $callbackUrl = $integration->callback_url ?: config('trello.webhook.callback_url');

        if (! $callbackUrl) {
            throw new InvalidArgumentException('Trello webhook callback URL belum diset.');
        }

        return $callbackUrl;
    }

    private function buildDescription(TrelloIntegration $integration): string
    {
        $prefix = config('trello.webhook.description_prefix', 'FlexLabs OPS');

        return trim($prefix . ' - ' . $integration->name);
    }

    private function url(string $path, array $query = []): string
    {
        $baseUrl = rtrim(config('trello.base_url', 'https://api.trello.com/1'), '/');
        $path = '/' . ltrim($path, '/');

        if (empty($query)) {
            return $baseUrl . $path;
        }

        return $baseUrl . $path . '?' . http_build_query($query, '', '&', PHP_QUERY_RFC3986);
    }
}