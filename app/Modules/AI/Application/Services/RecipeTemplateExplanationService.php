<?php

namespace App\Modules\AI\Application\Services;

use App\Modules\Admin\Application\Services\AdminEventRecorder;
use App\Modules\AI\Application\Contracts\RecipeExplanationProvider;
use App\Modules\AI\Application\DTO\RecipeExplanationContext;
use App\Modules\AI\Application\DTO\RecipeExplanationPayload;
use App\Modules\AI\Application\Exceptions\InvalidRecipeExplanationException;
use App\Modules\AI\Application\Exceptions\RecipeExplanationProviderException;
use App\Modules\AI\Application\Exceptions\RecipeExplanationUnavailableException;
use App\Modules\AI\Application\Support\RecipeExplanationFallbackBuilder;
use App\Modules\AI\Application\Support\RecipeExplanationOutputValidator;
use App\Modules\AI\Application\Support\RecipeExplanationPromptBuilder;
use App\Modules\Recipes\Application\Services\RecipeTemplateDetailService;
use App\Modules\Shared\Application\Support\LogContextSanitizer;
use App\Modules\Users\Domain\Models\User;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

class RecipeTemplateExplanationService
{
    public function __construct(
        protected RecipeTemplateDetailService $recipeTemplateDetailService,
        protected RecipeExplanationProvider $provider,
        protected RecipeExplanationPromptBuilder $promptBuilder,
        protected RecipeExplanationOutputValidator $outputValidator,
        protected RecipeExplanationFallbackBuilder $fallbackBuilder,
        protected AdminEventRecorder $adminEventRecorder,
    ) {}

    public function generateForUser(
        User $user,
        string $recipeTemplateId,
        string $requestId,
        ?string $routeName = null,
    ): RecipeExplanationPayload {
        $detail = $this->recipeTemplateDetailService->detailForUser($user, $recipeTemplateId);
        $context = RecipeExplanationContext::fromDetail(
            user: $user,
            detail: $detail,
            requestId: $requestId,
            promptVersion: (string) config('ai.explanations.prompt_version', 'recipe_template_explanation.v1'),
            schemaVersion: (string) config('ai.explanations.schema_version', 'recipe_template_explanation.v1'),
        );

        if ($cached = $this->cachedExplanation($context)) {
            Log::info('recipe_template.explanation.generated', [
                'request_id' => $context->requestId,
                'user_id' => $context->userId,
                'template_id' => $context->templateId,
                'source' => 'cache',
                'provider' => null,
                'model' => null,
                'latency_ms' => null,
                'prompt_version' => $context->promptVersion,
                'schema_version' => $context->schemaVersion,
            ]);

            return $this->payload(
                context: $context,
                source: 'ai',
                explanation: $cached['explanation'],
                cached: true,
                generatedAt: $cached['generated_at'],
            );
        }

        try {
            $prompt = $this->promptBuilder->build($context);
            $providerResponse = $this->provider->generate($prompt);
            $validated = $this->outputValidator->validate($providerResponse->payload, $context);
            $generatedAt = now()->toIso8601String();

            $this->storeCachedExplanation($context, $validated, $generatedAt);

            Log::info('recipe_template.explanation.generated', [
                'request_id' => $context->requestId,
                'user_id' => $context->userId,
                'template_id' => $context->templateId,
                'source' => 'ai',
                'provider' => $providerResponse->provider,
                'model' => $providerResponse->model,
                'latency_ms' => $providerResponse->latencyMs,
                'prompt_version' => $context->promptVersion,
                'schema_version' => $context->schemaVersion,
            ]);

            return $this->payload(
                context: $context,
                source: 'ai',
                explanation: $validated,
                generatedAt: $generatedAt,
            );
        } catch (RecipeExplanationProviderException|InvalidRecipeExplanationException $exception) {
            $sanitizedContext = LogContextSanitizer::sanitize($exception->context);

            Log::warning('recipe_template.explanation.failed', [
                'request_id' => $context->requestId,
                'user_id' => $context->userId,
                'template_id' => $context->templateId,
                'provider' => config('ai.provider'),
                'prompt_version' => $context->promptVersion,
                'schema_version' => $context->schemaVersion,
                'error' => class_basename($exception),
                'context' => $sanitizedContext,
            ]);

            if ((bool) config('ai.explanations.fallback_enabled', true)) {
                $fallback = $this->fallbackBuilder->build($context);

                $this->adminEventRecorder->recordAiExplanationFailure(
                    event: 'recipe_template.explanation.failed',
                    actorUserId: $context->userId,
                    targetId: $context->templateId,
                    requestId: $context->requestId,
                    routeName: $routeName,
                    metadata: [
                        'provider' => $sanitizedContext['provider'] ?? config('ai.provider'),
                        'model' => $sanitizedContext['model'] ?? null,
                        'failure_type' => class_basename($exception),
                        'failure_reason' => $sanitizedContext['reason'] ?? ($sanitizedContext['error_type'] ?? class_basename($exception)),
                        'error_status' => $sanitizedContext['status'] ?? null,
                        'error_code' => $sanitizedContext['error_code'] ?? null,
                        'fallback_used' => true,
                        'prompt_version' => $context->promptVersion,
                        'schema_version' => $context->schemaVersion,
                    ],
                );

                Log::info('recipe_template.explanation.generated', [
                    'request_id' => $context->requestId,
                    'user_id' => $context->userId,
                    'template_id' => $context->templateId,
                    'source' => 'fallback',
                    'provider' => config('ai.provider'),
                    'model' => null,
                    'latency_ms' => null,
                    'prompt_version' => $context->promptVersion,
                    'schema_version' => $context->schemaVersion,
                ]);

                return $this->payload(
                    context: $context,
                    source: 'fallback',
                    explanation: $fallback,
                );
            }

            $this->adminEventRecorder->recordAiExplanationFailure(
                event: 'recipe_template.explanation.failed',
                actorUserId: $context->userId,
                targetId: $context->templateId,
                requestId: $context->requestId,
                routeName: $routeName,
                metadata: [
                    'provider' => $sanitizedContext['provider'] ?? config('ai.provider'),
                    'model' => $sanitizedContext['model'] ?? null,
                    'failure_type' => class_basename($exception),
                    'failure_reason' => $sanitizedContext['reason'] ?? ($sanitizedContext['error_type'] ?? class_basename($exception)),
                    'error_status' => $sanitizedContext['status'] ?? null,
                    'error_code' => $sanitizedContext['error_code'] ?? null,
                    'fallback_used' => false,
                    'prompt_version' => $context->promptVersion,
                    'schema_version' => $context->schemaVersion,
                ],
            );

            throw new RecipeExplanationUnavailableException(
                previous: $exception
            );
        }
    }

    protected function payload(
        RecipeExplanationContext $context,
        string $source,
        array $explanation,
        bool $cached = false,
        ?string $generatedAt = null,
    ): RecipeExplanationPayload {
        return new RecipeExplanationPayload(
            templateId: $context->templateId,
            source: $source,
            explanation: [
                ...$explanation,
                'grounding' => $context->grounding(),
                'warnings_or_limits' => [
                    'Grounded only in the published recipe template, pantry fit, and substitution data already stored in the app.',
                    'Not medical, allergy-certainty, diagnosis, treatment, or disease-management advice.',
                ],
            ],
            meta: [
                'generated_at' => $generatedAt ?? now()->toIso8601String(),
                'schema_version' => $context->schemaVersion,
                'prompt_version' => $context->promptVersion,
                'cached' => $cached,
            ],
        );
    }

    protected function cachedExplanation(RecipeExplanationContext $context): ?array
    {
        if (! $this->cacheEnabled()) {
            return null;
        }

        $payload = Cache::get($this->cacheKey($context));

        if (! is_array($payload)) {
            return null;
        }

        if (! is_array($payload['explanation'] ?? null) || ! is_string($payload['generated_at'] ?? null)) {
            return null;
        }

        return $payload;
    }

    protected function storeCachedExplanation(
        RecipeExplanationContext $context,
        array $explanation,
        string $generatedAt,
    ): void {
        if (! $this->cacheEnabled()) {
            return;
        }

        Cache::put(
            $this->cacheKey($context),
            [
                'explanation' => $explanation,
                'generated_at' => $generatedAt,
            ],
            now()->addSeconds($this->cacheTtlSeconds())
        );
    }

    protected function cacheEnabled(): bool
    {
        return (bool) config('ai.explanations.cache.enabled', false);
    }

    protected function cacheTtlSeconds(): int
    {
        return max(1, (int) config('ai.explanations.cache.ttl_seconds', 300));
    }

    protected function cacheKey(RecipeExplanationContext $context): string
    {
        return 'recipe_template_explanations:'.hash('sha256', serialize($context->cachePayload()));
    }
}
