<?php

return [

    'base_url' => env('PHILSMS_BASE_URL', 'https://dashboard.philsms.com/api/v3'),

    'api_token' => env('PHILSMS_API_TOKEN') ? trim((string) env('PHILSMS_API_TOKEN')) : null,

    'sender_id' => env('PHILSMS_SENDER_ID') ? trim((string) env('PHILSMS_SENDER_ID')) : 'AZAutoZol',

];
