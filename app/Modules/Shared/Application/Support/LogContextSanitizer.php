<?php

namespace App\Modules\Shared\Application\Support;

use Illuminate\Support\Str;

class LogContextSanitizer
{
    protected const REDACTED = '[REDACTED]';

    protected const SENSITIVE_KEYS = [
        'access_token',
        'api_key',
        'apikey',
        'authorization',
        'bearer_token',
        'client_secret',
        'current_password',
        'openai_api_key',
        'password',
        'password_confirmation',
        'plain_text_token',
        'refresh_token',
        'secret',
        'token',
    ];

    public static function sanitize(array $context): array
    {
        $sanitized = [];

        foreach ($context as $key => $value) {
            if (is_string($key) && self::isSensitiveKey($key)) {
                $sanitized[$key] = self::REDACTED;

                continue;
            }

            $sanitized[$key] = match (true) {
                is_array($value) => self::sanitize($value),
                is_object($value) => method_exists($value, '__toString')
                    ? (string) $value
                    : $value::class,
                default => $value,
            };
        }

        return $sanitized;
    }

    protected static function isSensitiveKey(string $key): bool
    {
        return in_array(Str::snake($key), self::SENSITIVE_KEYS, true);
    }
}
