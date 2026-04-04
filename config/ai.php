<?php

return [
    'provider' => env('AI_EXPLANATION_PROVIDER', env('AI_DRIVER', 'openai')),

    'timeout' => (int) env('AI_EXPLANATION_TIMEOUT', env('AI_TIMEOUT', 15)),

    'retry' => [
        'times' => (int) env('AI_EXPLANATION_RETRY_TIMES', 1),
        'sleep_ms' => (int) env('AI_EXPLANATION_RETRY_SLEEP_MS', 250),
    ],

    'explanations' => [
        'prompt_version' => env('AI_EXPLANATION_PROMPT_VERSION', 'recipe_template_explanation.v1'),
        'schema_version' => env('AI_EXPLANATION_SCHEMA_VERSION', 'recipe_template_explanation.v1'),
        'fallback_enabled' => env('AI_EXPLANATION_FALLBACK_ENABLED', true),
        'cache' => [
            'enabled' => env('AI_EXPLANATION_CACHE_ENABLED', false),
            'ttl_seconds' => (int) env('AI_EXPLANATION_CACHE_TTL_SECONDS', 300),
        ],
    ],

    'providers' => [
        'openai' => [
            'base_url' => env('OPENAI_BASE_URL', 'https://api.openai.com/v1'),
            'model' => env('OPENAI_EXPLANATION_MODEL', 'gpt-5-mini'),
            'store' => env('OPENAI_STORE', false),
        ],
    ],
];
