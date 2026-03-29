<?php

return [
    'modules' => [
        'shared' => [
            'name' => 'Shared',
            'description' => 'Cross-cutting API conventions, enums, middleware, and shared support.',
            'api_routes' => base_path('app/Modules/Shared/Routes/api.php'),
        ],
        'auth' => [
            'name' => 'Auth',
            'description' => 'Token-based authentication boundaries for mobile clients.',
            'api_routes' => base_path('app/Modules/Auth/Routes/api.php'),
        ],
        'users' => [
            'name' => 'Users',
            'description' => 'Accounts, profiles, and user-owned preferences.',
            'api_routes' => base_path('app/Modules/Users/Routes/api.php'),
        ],
        'pantry' => [
            'name' => 'Pantry',
            'description' => 'User pantry inventory and normalization-ready pantry records.',
            'api_routes' => base_path('app/Modules/Pantry/Routes/api.php'),
        ],
        'ingredients' => [
            'name' => 'Ingredients',
            'description' => 'Ingredients, aliases, pairings, and substitutions.',
            'api_routes' => base_path('app/Modules/Ingredients/Routes/api.php'),
        ],
        'recipes' => [
            'name' => 'Recipes',
            'description' => 'Recipe template catalog boundaries.',
            'api_routes' => base_path('app/Modules/Recipes/Routes/api.php'),
        ],
        'contributions' => [
            'name' => 'Contributions',
            'description' => 'User-submitted additions, updates, and structured review payloads.',
            'api_routes' => base_path('app/Modules/Contributions/Routes/api.php'),
        ],
        'moderation' => [
            'name' => 'Moderation',
            'description' => 'Moderation cases and review coordination.',
            'api_routes' => base_path('app/Modules/Moderation/Routes/api.php'),
        ],
        'reputation' => [
            'name' => 'Reputation',
            'description' => 'Contributor scoring aggregates and future reputation rules.',
            'api_routes' => base_path('app/Modules/Reputation/Routes/api.php'),
        ],
        'notifications' => [
            'name' => 'Notifications',
            'description' => 'Notification orchestration boundaries for future delivery channels.',
            'api_routes' => base_path('app/Modules/Notifications/Routes/api.php'),
        ],
        'admin' => [
            'name' => 'Admin',
            'description' => 'Administrative tooling, audit, and operational visibility.',
            'api_routes' => base_path('app/Modules/Admin/Routes/api.php'),
        ],
        'ai' => [
            'name' => 'AI',
            'description' => 'Reserved boundary for later server-side AI orchestration.',
            'api_routes' => base_path('app/Modules/AI/Routes/api.php'),
        ],
    ],
];
