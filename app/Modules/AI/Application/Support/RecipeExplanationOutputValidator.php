<?php

namespace App\Modules\AI\Application\Support;

use App\Modules\AI\Application\DTO\RecipeExplanationContext;
use App\Modules\AI\Application\Exceptions\InvalidRecipeExplanationException;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

class RecipeExplanationOutputValidator
{
    public function validate(array $payload, RecipeExplanationContext $context): array
    {
        $this->ensureNoUnexpectedKeys($payload);

        $validator = Validator::make($payload, [
            'headline' => ['required', 'string', 'min:3', 'max:120'],
            'why_it_fits' => ['required', 'string', 'min:10', 'max:400'],
            'taste_profile' => ['required', 'string', 'min:10', 'max:280'],
            'texture_profile' => ['required', 'string', 'min:10', 'max:280'],
            'substitution_guidance' => ['required', 'array', 'max:3'],
            'substitution_guidance.*' => ['string', 'min:3', 'max:180'],
            'quick_takeaways' => ['required', 'array', 'min:2', 'max:4'],
            'quick_takeaways.*' => ['string', 'min:3', 'max:140'],
            'follow_up_options' => ['required', 'array', 'min:1', 'max:3'],
            'follow_up_options.*.key' => [
                'required',
                'string',
                Rule::in($context->allowedFollowUpKeys()),
            ],
            'follow_up_options.*.label' => ['required', 'string', 'min:3', 'max:80', 'regex:/\?$/'],
        ]);

        if ($validator->fails()) {
            throw new InvalidRecipeExplanationException(
                'Recipe explanation output failed validation.',
                ['errors' => $validator->errors()->toArray()]
            );
        }

        $validated = $validator->validated();

        collect($validated['follow_up_options'] ?? [])->each(function (array $option): void {
            $this->ensureNoUnexpectedKeys($option, ['key', 'label']);
        });

        $normalized = [
            'headline' => Str::squish((string) $validated['headline']),
            'why_it_fits' => Str::squish((string) $validated['why_it_fits']),
            'taste_profile' => Str::squish((string) $validated['taste_profile']),
            'texture_profile' => Str::squish((string) $validated['texture_profile']),
            'substitution_guidance' => collect($validated['substitution_guidance'] ?? [])
                ->map(fn (string $value) => Str::squish($value))
                ->values()
                ->all(),
            'quick_takeaways' => collect($validated['quick_takeaways'] ?? [])
                ->map(fn (string $value) => Str::squish($value))
                ->values()
                ->all(),
            'follow_up_options' => collect($validated['follow_up_options'] ?? [])
                ->map(fn (array $option) => [
                    'key' => $option['key'],
                    'label' => Str::squish((string) $option['label']),
                ])
                ->values()
                ->all(),
        ];

        $this->ensureSafe($normalized);

        return $normalized;
    }

    protected function ensureNoUnexpectedKeys(array $payload, ?array $allowed = null): void
    {
        $allowedKeys = $allowed ?? [
            'headline',
            'why_it_fits',
            'taste_profile',
            'texture_profile',
            'substitution_guidance',
            'quick_takeaways',
            'follow_up_options',
        ];

        $unexpected = array_values(array_diff(array_keys($payload), $allowedKeys));

        if ($unexpected !== []) {
            throw new InvalidRecipeExplanationException(
                'Recipe explanation output contained unexpected fields.',
                ['unexpected_keys' => $unexpected]
            );
        }
    }

    protected function ensureSafe(array $payload): void
    {
        $fragments = collect([
            $payload['headline'] ?? null,
            $payload['why_it_fits'] ?? null,
            $payload['taste_profile'] ?? null,
            $payload['texture_profile'] ?? null,
            ...($payload['substitution_guidance'] ?? []),
            ...($payload['quick_takeaways'] ?? []),
            ...collect($payload['follow_up_options'] ?? [])->pluck('label')->all(),
        ])->filter()->values();

        $blockedPatterns = [
            '/\b(diagnos(?:e|is|ed)|treat(?:ment|s|ing)?|cure(?:s|d)?|heal(?:s|ed|ing)?|prevent(?:s|ed|ing)?|therapy|therapeutic|clinical(?:ly)?|prescription|symptom|disease|medical condition)\b/i',
            '/\b(diabetes|diabetic|hypertension|blood pressure|cholesterol|heart disease|celiac|allerg(?:y|ies)|anaphylaxis|asthma)\b/i',
            '/\b(allergy[- ]safe|allergen[- ]free|dairy[- ]free|gluten[- ]free|nut[- ]free)\b/i',
            '/\b\d+\s*(?:calories?|kcal|mg\s+(?:sodium|potassium|calcium)|g\s+(?:protein|fiber|fat)|grams?\s+of\s+(?:protein|fiber|fat))\b/i',
        ];

        foreach ($fragments as $fragment) {
            foreach ($blockedPatterns as $pattern) {
                if (preg_match($pattern, (string) $fragment) === 1) {
                    throw new InvalidRecipeExplanationException(
                        'Recipe explanation output crossed a safety boundary.',
                        ['fragment' => Arr::first([$fragment])]
                    );
                }
            }
        }
    }
}
