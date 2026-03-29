<?php

return [
    'defaults' => [
        'dietary_patterns' => [],
        'preferred_cuisines' => [],
        'disliked_ingredients' => [],
        'measurement_system' => 'metric',
    ],

    'allowed' => [
        'dietary_patterns' => [
            'omnivore',
            'vegetarian',
            'vegan',
            'pescatarian',
            'halal',
            'kosher',
        ],
        'measurement_systems' => [
            'metric',
            'imperial',
        ],
    ],
];
