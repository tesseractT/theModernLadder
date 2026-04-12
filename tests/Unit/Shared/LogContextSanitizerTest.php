<?php

namespace Tests\Unit\Shared;

use App\Modules\Shared\Application\Support\LogContextSanitizer;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class LogContextSanitizerTest extends TestCase
{
    #[Test]
    public function it_redacts_nested_sensitive_values_while_preserving_safe_identifiers(): void
    {
        $sanitized = LogContextSanitizer::sanitize([
            'token' => 'plain-text-token',
            'plainTextToken' => 'another-token',
            'password' => 'Password123!',
            'target_id' => 'user-123',
            'token_id' => 'token-record-123',
            'nested' => [
                'authorization' => 'Bearer secret',
                'refresh_token' => 'refresh-secret',
                'safe' => 'keep-me',
            ],
        ]);

        $this->assertSame('[REDACTED]', $sanitized['token']);
        $this->assertSame('[REDACTED]', $sanitized['plainTextToken']);
        $this->assertSame('[REDACTED]', $sanitized['password']);
        $this->assertSame('user-123', $sanitized['target_id']);
        $this->assertSame('token-record-123', $sanitized['token_id']);
        $this->assertSame('[REDACTED]', $sanitized['nested']['authorization']);
        $this->assertSame('[REDACTED]', $sanitized['nested']['refresh_token']);
        $this->assertSame('keep-me', $sanitized['nested']['safe']);
    }
}
