<?php

namespace App\Services\Ai;

use Illuminate\Support\Facades\Http;
use RuntimeException;

class GeminiClientService
{
    public function generateJson(string $prompt): array
    {
        $apiKey = config('services.gemini.api_key');
        $model = config('services.gemini.model', 'gemini-3.1-flash-lite');

        if (! $apiKey) {
            throw new RuntimeException('GEMINI_API_KEY is missing.');
        }

        $url = "https://generativelanguage.googleapis.com/v1beta/models/{$model}:generateContent";

        $response = Http::timeout(60)->post($url . '?key=' . $apiKey, [
            'contents' => [
                [
                    'role' => 'user',
                    'parts' => [
                        ['text' => $prompt],
                    ],
                ],
            ],
            'generationConfig' => [
                'responseMimeType' => 'application/json',
                'temperature' => 0.2,
            ],
        ]);

        if (! $response->successful()) {
            throw new RuntimeException('Gemini API error: ' . $response->body());
        }

        $text = data_get($response->json(), 'candidates.0.content.parts.0.text');

        if (! is_string($text) || trim($text) === '') {
            throw new RuntimeException('Gemini returned empty response.');
        }

        $decoded = json_decode($text, true);

        if (! is_array($decoded)) {
            throw new RuntimeException('Gemini response is not valid JSON: ' . $text);
        }

        return $decoded;
    }
}