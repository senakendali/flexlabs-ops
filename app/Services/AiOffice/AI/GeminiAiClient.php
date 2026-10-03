<?php

namespace App\Services\AiOffice\AI;

use Illuminate\Support\Facades\Http;
use RuntimeException;

class GeminiAiClient implements AiClientInterface
{
    /*
    |--------------------------------------------------------------------------
    | Structured Response
    |--------------------------------------------------------------------------
    */

    public function structured(
        string $systemPrompt,
        string $userPrompt,
        array $schema
    ): array {
        /*
        |--------------------------------------------------------------------------
        | Config
        |--------------------------------------------------------------------------
        */

        $apiKey =
            config(
                'services.gemini.api_key'
            );

        $model =
            config(
                'services.gemini.model'
            );

        $endpoint =
            rtrim(
                config(
                    'services.gemini.endpoint'
                ),
                '/'
            );

        $timeout =
            (int) config(
                'services.gemini.timeout',
                30
            );


        /*
        |--------------------------------------------------------------------------
        | Validate Config
        |--------------------------------------------------------------------------
        */

        if (!$apiKey) {
            throw new RuntimeException(
                'GEMINI_API_KEY belum dikonfigurasi.'
            );
        }


        if (!$model) {
            throw new RuntimeException(
                'GEMINI_MODEL belum dikonfigurasi.'
            );
        }


        if (!$endpoint) {
            throw new RuntimeException(
                'GEMINI_API_ENDPOINT belum dikonfigurasi.'
            );
        }


        /*
        |--------------------------------------------------------------------------
        | Generate Content
        |--------------------------------------------------------------------------
        */

        $response = Http::withHeaders([
            'x-goog-api-key' =>
                $apiKey,

            'Content-Type' =>
                'application/json',
        ])
            ->acceptJson()
            ->timeout(
                $timeout
            )
            ->post(
                $endpoint
                . '/models/'
                . $model
                . ':generateContent',

                [
                    /*
                    |--------------------------------------------------------------------------
                    | System Instruction
                    |--------------------------------------------------------------------------
                    */

                    'systemInstruction' => [
                        'parts' => [
                            [
                                'text' =>
                                    $systemPrompt,
                            ],
                        ],
                    ],


                    /*
                    |--------------------------------------------------------------------------
                    | User Request
                    |--------------------------------------------------------------------------
                    */

                    'contents' => [
                        [
                            'role' =>
                                'user',

                            'parts' => [
                                [
                                    'text' =>
                                        $userPrompt,
                                ],
                            ],
                        ],
                    ],


                    /*
                    |--------------------------------------------------------------------------
                    | Structured JSON Output
                    |--------------------------------------------------------------------------
                    */

                    'generationConfig' => [
                        'temperature' =>
                            0.1,

                        'responseMimeType' =>
                            'application/json',

                        'responseSchema' =>
                            $schema,
                    ],
                ]
            );


        /*
        |--------------------------------------------------------------------------
        | API Error
        |--------------------------------------------------------------------------
        */

        if ($response->failed()) {
            throw new RuntimeException(
                sprintf(
                    'Gemini request failed. HTTP %s: %s',
                    $response->status(),
                    $response->body()
                )
            );
        }


        /*
        |--------------------------------------------------------------------------
        | Response Payload
        |--------------------------------------------------------------------------
        */

        $payload =
            $response->json();


        /*
        |--------------------------------------------------------------------------
        | Extract Generated Text
        |--------------------------------------------------------------------------
        */

        $text =
            data_get(
                $payload,
                'candidates.0.content.parts.0.text'
            );


        if (
            !is_string($text)
            ||
            trim($text) === ''
        ) {
            throw new RuntimeException(
                'Gemini tidak mengembalikan structured output.'
            );
        }


        /*
        |--------------------------------------------------------------------------
        | Decode JSON
        |--------------------------------------------------------------------------
        */

        $decoded =
            json_decode(
                $text,
                true
            );


        if (!is_array($decoded)) {
            throw new RuntimeException(
                'Structured output Gemini bukan JSON yang valid.'
            );
        }


        return $decoded;
    }
}