<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Public Feedback Base URL
    |--------------------------------------------------------------------------
    |
    | Base URL untuk link feedback student.
    | Contoh production:
    | https://feedback.flexlabs.co.id
    |
    */

    'public_base_url' => env('FEEDBACK_PUBLIC_BASE_URL', env('APP_URL')),
];