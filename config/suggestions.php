<?php

return [
    'defaults' => [
        'limit' => 5,
        'include_substitutions' => true,
    ],

    'limits' => [
        'max_results' => 10,
        'max_selected_pantry_items' => 25,
        'max_pairing_signals_per_candidate' => 3,
    ],

    'scoring' => [
        'required_match' => 40,
        'optional_match' => 8,
        'perfect_required_match' => 14,
        'goal_match' => 10,
        'substitution_coverage' => 8,
        'pairing_signal' => 3,
        'missing_without_substitution_penalty' => 18,
    ],
];
