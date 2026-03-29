<?php

namespace App\Modules\Auth\Http\Requests;

use App\Modules\Users\Domain\Enums\UserStatus;
use App\Modules\Users\Domain\Models\User;
use Illuminate\Auth\Events\Lockout;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class LoginRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'email' => Str::lower(trim((string) $this->input('email'))),
            'device_name' => trim((string) $this->input('device_name', 'flutter-mobile')),
        ]);
    }

    public function rules(): array
    {
        return [
            'email' => ['required', 'string', 'email', 'max:255'],
            'password' => ['required', 'string'],
            'device_name' => ['sometimes', 'string', 'max:100'],
        ];
    }

    public function authenticate(): User
    {
        $this->ensureIsNotRateLimited();

        $user = User::query()
            ->where('email', $this->string('email')->toString())
            ->first();

        if (
            ! $user
            || ! Hash::check($this->string('password')->toString(), $user->password)
            || $user->status !== UserStatus::Active
        ) {
            RateLimiter::hit($this->throttleKey());

            throw ValidationException::withMessages([
                'email' => [trans('auth.failed')],
            ]);
        }

        RateLimiter::clear($this->throttleKey());

        return $user;
    }

    public function deviceName(): string
    {
        return $this->string('device_name')->toString() ?: 'flutter-mobile';
    }

    public function ensureIsNotRateLimited(): void
    {
        if (! RateLimiter::tooManyAttempts($this->throttleKey(), 5)) {
            return;
        }

        event(new Lockout($this));

        $seconds = RateLimiter::availableIn($this->throttleKey());

        throw ValidationException::withMessages([
            'email' => [trans('auth.throttle', [
                'seconds' => $seconds,
                'minutes' => ceil($seconds / 60),
            ])],
        ]);
    }

    public function throttleKey(): string
    {
        return Str::transliterate(
            $this->string('email')->lower()->append('|', $this->ip())->toString()
        );
    }
}
