<?php

return [
    'version' => env('API_VERSION', 'v1'),
    'per_page' => (int) env('API_PER_PAGE', 15),
    'max_per_page' => (int) env('API_MAX_PER_PAGE', 50),
    'rate_limit_per_minute' => (int) env('API_RATE_LIMIT_PER_MINUTE', 60),
    'route_rate_limits' => [
        'auth' => [
            'register' => [
                'per_minute' => (int) env('API_AUTH_REGISTER_RATE_LIMIT_PER_MINUTE', 5),
            ],
            'login' => [
                'per_minute' => (int) env('API_AUTH_LOGIN_RATE_LIMIT_PER_MINUTE', 10),
                'credential_lockout' => [
                    'max_attempts' => (int) env('API_AUTH_LOGIN_CREDENTIAL_MAX_ATTEMPTS', 5),
                    'decay_seconds' => (int) env('API_AUTH_LOGIN_CREDENTIAL_DECAY_SECONDS', 60),
                ],
            ],
            'logout' => [
                'per_minute' => (int) env('API_AUTH_LOGOUT_RATE_LIMIT_PER_MINUTE', 30),
            ],
        ],
        'recipes' => [
            'explanation' => [
                'per_minute' => (int) env('API_RECIPE_EXPLANATION_RATE_LIMIT_PER_MINUTE', 5),
            ],
        ],
        'contributions' => [
            'store' => [
                'per_minute' => (int) env('API_CONTRIBUTION_STORE_RATE_LIMIT_PER_MINUTE', 10),
            ],
            'report' => [
                'per_minute' => (int) env('API_CONTRIBUTION_REPORT_RATE_LIMIT_PER_MINUTE', 15),
            ],
        ],
        'moderation' => [
            'actions' => [
                'per_minute' => (int) env('API_MODERATION_ACTION_RATE_LIMIT_PER_MINUTE', 30),
            ],
        ],
        'admin' => [
            'read' => [
                'per_minute' => (int) env('API_ADMIN_READ_RATE_LIMIT_PER_MINUTE', 60),
            ],
        ],
    ],
];
