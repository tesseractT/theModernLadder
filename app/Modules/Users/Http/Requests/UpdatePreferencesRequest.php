<?php

namespace App\Modules\Users\Http\Requests;

use App\Modules\Users\Application\DTO\UpdateFoodPreferencesData;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Arr;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

class UpdatePreferencesRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    protected function prepareForValidation(): void
    {
        $normalizeStringArray = function (?array $values, bool $lowercase = false): ?array {
            if ($values === null) {
                return null;
            }

            $normalized = collect($values)
                ->filter(fn ($value) => $value !== null)
                ->map(fn ($value) => trim((string) $value))
                ->filter()
                ->map(fn (string $value) => $lowercase ? Str::lower($value) : $value)
                ->unique()
                ->values()
                ->all();

            return $normalized;
        };

        $normalized = [];

        if ($this->exists('dietary_patterns')) {
            $normalized['dietary_patterns'] = $normalizeStringArray(
                Arr::wrap($this->input('dietary_patterns')),
                true
            );
        }

        if ($this->exists('preferred_cuisines')) {
            $normalized['preferred_cuisines'] = $normalizeStringArray(
                Arr::wrap($this->input('preferred_cuisines'))
            );
        }

        if ($this->exists('disliked_ingredients')) {
            $normalized['disliked_ingredients'] = $normalizeStringArray(
                Arr::wrap($this->input('disliked_ingredients'))
            );
        }

        if ($this->exists('measurement_system')) {
            $normalized['measurement_system'] = Str::lower(
                trim((string) $this->input('measurement_system'))
            );
        }

        $this->merge($normalized);
    }

    public function rules(): array
    {
        return [
            'dietary_patterns' => ['sometimes', 'array', 'max:10'],
            'dietary_patterns.*' => [
                'string',
                Rule::in(config('user_preferences.allowed.dietary_patterns', [])),
            ],
            'preferred_cuisines' => ['sometimes', 'array', 'max:20'],
            'preferred_cuisines.*' => ['string', 'min:2', 'max:50'],
            'disliked_ingredients' => ['sometimes', 'array', 'max:50'],
            'disliked_ingredients.*' => ['string', 'min:1', 'max:80'],
            'measurement_system' => [
                'sometimes',
                'string',
                Rule::in(config('user_preferences.allowed.measurement_systems', [])),
            ],
        ];
    }

    public function payload(): UpdateFoodPreferencesData
    {
        return UpdateFoodPreferencesData::fromValidated(
            $this->safe()->only([
                'dietary_patterns',
                'preferred_cuisines',
                'disliked_ingredients',
                'measurement_system',
            ])
        );
    }
}
