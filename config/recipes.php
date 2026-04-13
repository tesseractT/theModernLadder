<?php

return [
    'retention' => [
        'recent_history' => [
            'max_entries' => (int) env('RECIPE_RECENT_HISTORY_MAX_ENTRIES', 25),
        ],
    ],
];
