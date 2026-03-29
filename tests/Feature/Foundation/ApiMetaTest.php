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
}
