<?php

namespace App\Modules\Shared\Http\Controllers;

use Illuminate\Http\JsonResponse;

class ApiMetaController extends ApiController
{
    public function __invoke(): JsonResponse
    {
        return $this->respond([
            'name' => config('app.name'),
            'api_version' => config('api.version'),
            'stack' => [
                'framework' => 'laravel',
                'database' => 'postgresql',
                'cache' => 'redis',
                'queue' => 'redis',
                'auth' => 'sanctum',
            ],
            'modules' => collect(config('modules.modules', []))
                ->map(fn (array $module): array => [
                    'name' => $module['name'],
                    'description' => $module['description'],
                ])
                ->values()
                ->all(),
            'purpose' => 'Food discovery, recipe suggestions, ingredient pairing, substitutions, and general nutrition education.',
            'medical_scope' => 'Not designed for diagnosis, treatment, disease management, or other regulated-health decisioning.',
        ]);
    }
}
