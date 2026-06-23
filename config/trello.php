<?php

return [
    'base_url' => env('TRELLO_API_BASE_URL', 'https://api.trello.com/1'),

    'webhook' => [
        'callback_url' => env('TRELLO_WEBHOOK_CALLBACK_URL'),
        'description_prefix' => env('TRELLO_WEBHOOK_DESCRIPTION_PREFIX', 'FlexLabs OPS'),
    ],
];