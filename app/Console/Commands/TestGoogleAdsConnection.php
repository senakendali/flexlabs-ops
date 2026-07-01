<?php

namespace App\Console\Commands;

use Google\Ads\GoogleAds\Lib\OAuth2TokenBuilder;
use Illuminate\Console\Command;
use RuntimeException;
use Throwable;

class TestGoogleAdsConnection extends Command
{
    protected $signature = 'google-ads:test';

    protected $description = 'Test Google Ads API connection and fetch basic customer/campaign data.';

    public function handle(): int
    {
        $this->info('Testing Google Ads API connection...');

        try {
            $this->ensureConfigured();

            $version = $this->resolveGoogleAdsVersion();
            $this->line('Detected Google Ads API client version: ' . $version);

            $googleAdsClient = $this->makeGoogleAdsClient($version);
            $googleAdsServiceClient = $googleAdsClient->getGoogleAdsServiceClient();

            $customerId = $this->customerId();

            $this->newLine();
            $this->line('Customer ID: ' . $customerId);
            $this->line('Login Customer ID: ' . ($this->loginCustomerId() ?: '-'));

            $customer = $this->fetchCustomer($googleAdsServiceClient, $version, $customerId);
            $campaigns = $this->fetchCampaigns($googleAdsServiceClient, $version, $customerId);

            $this->newLine();
            $this->info('Connection OK.');

            $this->table(
                ['Metric', 'Value'],
                [
                    ['Customer ID', $customer['id'] ?? $customerId],
                    ['Customer Name', $customer['name'] ?? '-'],
                    ['Currency', $customer['currency'] ?? '-'],
                    ['Time Zone', $customer['time_zone'] ?? '-'],
                    ['Campaigns Fetched', count($campaigns)],
                ]
            );

            if (! empty($campaigns)) {
                $this->newLine();
                $this->table(
                    ['Campaign ID', 'Name', 'Status'],
                    collect($campaigns)
                        ->map(fn (array $campaign) => [
                            $campaign['id'] ?? '-',
                            $campaign['name'] ?? '-',
                            $campaign['status'] ?? '-',
                        ])
                        ->all()
                );
            }

            return self::SUCCESS;
        } catch (Throwable $e) {
            $this->error('Google Ads test failed.');
            $this->newLine();
            $this->line($e->getMessage());

            return self::FAILURE;
        }
    }

    protected function makeGoogleAdsClient(string $version)
    {
        $builderClass = "Google\\Ads\\GoogleAds\\Lib\\{$version}\\GoogleAdsClientBuilder";

        if (! class_exists($builderClass)) {
            throw new RuntimeException("Google Ads client builder class not found: {$builderClass}");
        }

        $oAuth2Credential = (new OAuth2TokenBuilder())
            ->withClientId(config('services.google_ads.client_id'))
            ->withClientSecret(config('services.google_ads.client_secret'))
            ->withRefreshToken(config('services.google_ads.refresh_token'))
            ->build();

        $builder = (new $builderClass())
            ->withOAuth2Credential($oAuth2Credential)
            ->withDeveloperToken(config('services.google_ads.developer_token'));

        if ($this->loginCustomerId()) {
            $builder->withLoginCustomerId((int) $this->loginCustomerId());
        }

        return $builder->build();
    }

    protected function fetchCustomer($googleAdsServiceClient, string $version, string $customerId): array
    {
        $query = <<<GAQL
SELECT
  customer.id,
  customer.descriptive_name,
  customer.currency_code,
  customer.time_zone
FROM customer
LIMIT 1
GAQL;

        $request = $this->makeSearchRequest($version, $customerId, $query);
        $response = $googleAdsServiceClient->search($request);

        foreach ($response->iterateAllElements() as $row) {
            $customer = $row->getCustomer();

            return [
                'id' => $customer->getId(),
                'name' => $customer->getDescriptiveName(),
                'currency' => $customer->getCurrencyCode(),
                'time_zone' => $customer->getTimeZone(),
            ];
        }

        return [];
    }

    protected function fetchCampaigns($googleAdsServiceClient, string $version, string $customerId): array
    {
        $query = <<<GAQL
SELECT
  campaign.id,
  campaign.name,
  campaign.status
FROM campaign
WHERE campaign.status != 'REMOVED'
ORDER BY campaign.id DESC
LIMIT 10
GAQL;

        $request = $this->makeSearchRequest($version, $customerId, $query);
        $response = $googleAdsServiceClient->search($request);

        $campaigns = [];

        foreach ($response->iterateAllElements() as $row) {
            $campaign = $row->getCampaign();

            $campaigns[] = [
                'id' => $campaign->getId(),
                'name' => $campaign->getName(),
                'status' => $campaign->getStatus(),
            ];
        }

        return $campaigns;
    }

    protected function makeSearchRequest(string $version, string $customerId, string $query)
    {
        $requestClass = "Google\\Ads\\GoogleAds\\{$version}\\Services\\SearchGoogleAdsRequest";

        if (! class_exists($requestClass)) {
            throw new RuntimeException("Google Ads search request class not found: {$requestClass}");
        }

        return new $requestClass([
            'customer_id' => $customerId,
            'query' => $query,
        ]);
    }

    protected function resolveGoogleAdsVersion(): string
    {
        $versions = [
            'V22',
            'V21',
            'V20',
            'V19',
            'V18',
            'V17',
        ];

        foreach ($versions as $version) {
            $class = "Google\\Ads\\GoogleAds\\Lib\\{$version}\\GoogleAdsClientBuilder";

            if (class_exists($class)) {
                return $version;
            }
        }

        throw new RuntimeException('No supported Google Ads API client builder version was found.');
    }

    protected function ensureConfigured(): void
    {
        if (! config('services.google_ads.enabled')) {
            throw new RuntimeException('Google Ads integration is disabled.');
        }

        $required = [
            'developer_token',
            'client_id',
            'client_secret',
            'refresh_token',
            'customer_id',
        ];

        foreach ($required as $key) {
            if (blank(config("services.google_ads.{$key}"))) {
                throw new RuntimeException("Google Ads config is missing: {$key}");
            }
        }
    }

    protected function customerId(): string
    {
        return preg_replace('/\D+/', '', (string) config('services.google_ads.customer_id'));
    }

    protected function loginCustomerId(): ?string
    {
        $loginCustomerId = preg_replace('/\D+/', '', (string) config('services.google_ads.login_customer_id'));

        return $loginCustomerId !== ''
            ? $loginCustomerId
            : null;
    }
}