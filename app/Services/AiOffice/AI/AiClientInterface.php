<?php

namespace App\Services\AiOffice\AI;

interface AiClientInterface
{
    public function structured(
        string $systemPrompt,
        string $userPrompt,
        array $schema
    ): array;
}