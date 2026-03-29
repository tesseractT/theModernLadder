<?php

return [
    'version' => env('API_VERSION', 'v1'),
    'per_page' => (int) env('API_PER_PAGE', 15),
    'max_per_page' => (int) env('API_MAX_PER_PAGE', 50),
    'rate_limit_per_minute' => (int) env('API_RATE_LIMIT_PER_MINUTE', 60),
];
