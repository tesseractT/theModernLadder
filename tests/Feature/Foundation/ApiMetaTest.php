<?php

namespace Tests\Feature\Foundation;

use Tests\TestCase;

class ApiMetaTest extends TestCase
{
    public function test_it_returns_api_metadata(): void
    {
        $response = $this->getJson('/api/v1/meta');

        $response
            ->assertOk()
            ->assertJsonPath('api_version', 'v1')
            ->assertJsonPath('stack.auth', 'sanctum')
            ->assertJsonFragment(['name' => 'Ingredients'])
            ->assertJsonFragment(['name' => 'Recipes']);
    }

    public function test_successful_api_responses_include_a_generated_request_id_header(): void
    {
        $response = $this->getJson('/api/v1/meta');

        $response->assertOk()->assertHeader('X-Request-Id');

        $this->assertMatchesRegularExpression(
            '/\A[0-9a-f]{8}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{12}\z/i',
            (string) $response->headers->get('X-Request-Id')
        );
    }

    public function test_valid_caller_supplied_request_id_is_echoed_back_in_the_response_header(): void
    {
        $requestId = 'client-request-123';

        $this->withHeaders([
            'X-Request-Id' => $requestId,
        ])->getJson('/api/v1/meta')
            ->assertOk()
            ->assertHeader('X-Request-Id', $requestId);
    }

    public function test_api_responses_include_safe_default_security_headers(): void
    {
        $this->getJson('/api/v1/meta')
            ->assertOk()
            ->assertHeader('X-Content-Type-Options', 'nosniff')
            ->assertHeader('Referrer-Policy', 'no-referrer');
    }
}
