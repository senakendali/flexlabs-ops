<?php

namespace App\Services\AiOffice\Agents;

use App\Services\AiOffice\Tools\Academic\GetActiveClassesTool;
use InvalidArgumentException;

class RakaAgentService
{
    public function __construct(
        private readonly GetActiveClassesTool $getActiveClassesTool
    ) {
    }


    /*
    |--------------------------------------------------------------------------
    | Handle Task
    |--------------------------------------------------------------------------
    */

    public function handleTask(
        string $action,
        array $input = []
    ): array {
        return match ($action) {
            'get_active_classes' =>
                $this->getActiveClasses($input),

            default => throw new InvalidArgumentException(
                sprintf(
                    'Raka tidak memiliki capability untuk action "%s".',
                    $action
                )
            ),
        };
    }


    /*
    |--------------------------------------------------------------------------
    | Get Active Classes
    |--------------------------------------------------------------------------
    */

    public function getActiveClasses(
        array $input = []
    ): array {
        $result =
            $this->getActiveClassesTool
                ->handle($input);

        return [
            'success' => $result['success'],

            'agent' => [
                'code' => 'raka',
                'name' => 'Raka',
                'role' => 'Academic Admin',
            ],

            'action' => 'get_active_classes',

            'input' => $input,

            'data' => $result['data'],

            'meta' => $result['meta'],
        ];
    }
}