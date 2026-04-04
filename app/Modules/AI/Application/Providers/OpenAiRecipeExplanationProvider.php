<?php

namespace App\Modules\AI\Application\Providers;

use App\Modules\AI\Application\Contracts\RecipeExplanationProvider;
use App\Modules\AI\Application\DTO\RecipeExplanationPrompt;
use App\Modules\AI\Application\DTO\RecipeExplanationProviderResponse;
use App\Modules\AI\Application\Exceptions\RecipeExplanationProviderException;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Support\Facades\Http;
use JsonException;

class OpenAiRecipeExplanationProvider implements RecipeExplanationProvider
{
    public function generate(RecipeExplanationPrompt $prompt): RecipeExplanationProviderResponse
    {
        $apiKey = config('services.openai.key');
        $model = (string) config('ai.providers.openai.model', 'gpt-5-mini');

        if (! is_string($apiKey) || trim($apiKey) === '') {
            throw new RecipeExplanationProviderException(
                'Recipe explanation provider is not configured.',
                ['provider' => 'openai', 'reason' => 'missing_api_key']
            );
        }

        $baseUrl = rtrim((string) config('ai.providers.openai.base_url', 'https://api.openai.com/v1'), '/');
        $timeout = max(1, (int) config('ai.timeout', 15));
        $retryTimes = max(0, (int) config('ai.retry.times', 1));
        $retrySleepMs = max(0, (int) config('ai.retry.sleep_ms', 250));
        $maxAttempts = 1 + $retryTimes;
        $requestPayload = [
            'model' => $model,
            'store' => (bool) config('ai.providers.openai.store', false),
            'instructions' => $prompt->instructions,
            'input' => $prompt->input,
            'text' => [
                'format' => [
                    'type' => 'json_schema',
                    'name' => $prompt->schemaName,
                    'strict' => true,
                    'schema' => $prompt->schema,
                ],
            ],
        ];

        $startedAt = microtime(true);

        for ($attempt = 1; $attempt <= $maxAttempts; $attempt++) {
            try {
                $response = Http::baseUrl($baseUrl)
                    ->acceptJson()
                    ->asJson()
                    ->withToken($apiKey)
                    ->timeout($timeout)
                    ->connectTimeout(min(5, $timeout))
                    ->post('/responses', $requestPayload);
            } catch (ConnectionException $exception) {
                if ($attempt < $maxAttempts) {
                    usleep($retrySleepMs * 1000);

                    continue;
                }

                throw new RecipeExplanationProviderException(
                    'Recipe explanation provider request failed.',
                    [
                        'provider' => 'openai',
                        'model' => $model,
                        'reason' => 'connection_exception',
                    ],
                    $exception
                );
            }

            if ($response->successful()) {
                return new RecipeExplanationProviderResponse(
                    payload: $this->decodeOutput($response->json()),
                    provider: 'openai',
                    model: $model,
                    latencyMs: (int) round((microtime(true) - $startedAt) * 1000),
                );
            }

            if ($response->serverError() && $attempt < $maxAttempts) {
                usleep($retrySleepMs * 1000);

                continue;
            }

            throw new RecipeExplanationProviderException(
                'Recipe explanation provider request failed.',
                [
                    'provider' => 'openai',
                    'model' => $model,
                    'status' => $response->status(),
                    'error_type' => $response->json('error.type'),
                    'error_code' => $response->json('error.code'),
                ]
            );
        }

        throw new RecipeExplanationProviderException(
            'Recipe explanation provider request failed.',
            ['provider' => 'openai', 'model' => $model]
        );
    }

    protected function decodeOutput(array $response): array
    {
        $outputText = $this->extractOutputText($response);

        try {
            $decoded = json_decode($outputText, true, 512, JSON_THROW_ON_ERROR);
        } catch (JsonException $exception) {
            throw new RecipeExplanationProviderException(
                'Recipe explanation provider returned malformed JSON.',
                ['provider' => 'openai', 'reason' => 'malformed_json'],
                $exception
            );
        }

        if (! is_array($decoded)) {
            throw new RecipeExplanationProviderException(
                'Recipe explanation provider returned an invalid payload.',
                ['provider' => 'openai', 'reason' => 'invalid_payload_shape']
            );
        }

        return $decoded;
    }

    protected function extractOutputText(array $response): string
    {
        $outputText = $response['output_text'] ?? null;

        if (is_string($outputText) && trim($outputText) !== '') {
            return trim($outputText);
        }

        $messageText = collect($response['output'] ?? [])
            ->filter(fn ($item) => ($item['type'] ?? null) === 'message')
            ->flatMap(fn ($item) => $item['content'] ?? [])
            ->filter(fn ($content) => ($content['type'] ?? null) === 'output_text')
            ->pluck('text')
            ->filter(fn ($text) => is_string($text) && trim($text) !== '')
            ->implode("\n");

        if ($messageText !== '') {
            return $messageText;
        }

        throw new RecipeExplanationProviderException(
            'Recipe explanation provider returned an empty response.',
            ['provider' => 'openai', 'reason' => 'empty_output']
        );
    }
}
