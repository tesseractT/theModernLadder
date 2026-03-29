<?php

namespace App\Modules\Users\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Str;

class UpdateProfileRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    protected function prepareForValidation(): void
    {
        $normalized = [];

        if ($this->exists('display_name')) {
            $normalized['display_name'] = trim((string) $this->input('display_name'));
        }

        if ($this->exists('bio')) {
            $normalized['bio'] = $this->input('bio') === null
                ? null
                : trim((string) $this->input('bio'));
        }

        if ($this->exists('locale')) {
            $normalized['locale'] = Str::lower(trim((string) $this->input('locale')));
        }

        if ($this->exists('timezone')) {
            $normalized['timezone'] = $this->input('timezone') === null
                ? null
                : trim((string) $this->input('timezone'));
        }

        if ($this->exists('country_code')) {
            $normalized['country_code'] = $this->input('country_code') === null
                ? null
                : Str::upper(trim((string) $this->input('country_code')));
        }

        $this->merge($normalized);
    }

    public function rules(): array
    {
        return [
            'display_name' => ['sometimes', 'string', 'min:2', 'max:100'],
            'bio' => ['sometimes', 'nullable', 'string', 'max:500'],
            'locale' => ['sometimes', 'string', 'max:12'],
            'timezone' => ['sometimes', 'nullable', 'timezone'],
            'country_code' => ['sometimes', 'nullable', 'string', 'size:2'],
        ];
    }

    public function profileAttributes(): array
    {
        return $this->safe()->only([
            'display_name',
            'bio',
            'locale',
            'timezone',
            'country_code',
        ]);
    }
}
