<?php

namespace App\Modules\AI\Application\Services;

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
use App\Modules\Users\Domain\Models\User;
use Illuminate\Support\Facades\Log;

class RecipeTemplateExplanationService
{
    public function __construct(
        protected RecipeTemplateDetailService $recipeTemplateDetailService,
        protected RecipeExplanationProvider $provider,
        protected RecipeExplanationPromptBuilder $promptBuilder,
        protected RecipeExplanationOutputValidator $outputValidator,
        protected RecipeExplanationFallbackBuilder $fallbackBuilder,
    ) {}

    public function generateForUser(
        User $user,
        string $recipeTemplateId,
        string $requestId,
    ): RecipeExplanationPayload {
        $detail = $this->recipeTemplateDetailService->detailForUser($user, $recipeTemplateId);
        $context = RecipeExplanationContext::fromDetail(
            user: $user,
            detail: $detail,
            requestId: $requestId,
            promptVersion: (string) config('ai.explanations.prompt_version', 'recipe_template_explanation.v1'),
            schemaVersion: (string) config('ai.explanations.schema_version', 'recipe_template_explanation.v1'),
        );

        try {
            $prompt = $this->promptBuilder->build($context);
            $providerResponse = $this->provider->generate($prompt);
            $validated = $this->outputValidator->validate($providerResponse->payload, $context);

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
            );
        } catch (RecipeExplanationProviderException|InvalidRecipeExplanationException $exception) {
            Log::warning('recipe_template.explanation.failed', [
                'request_id' => $context->requestId,
                'user_id' => $context->userId,
                'template_id' => $context->templateId,
                'provider' => config('ai.provider'),
                'prompt_version' => $context->promptVersion,
                'schema_version' => $context->schemaVersion,
                'error' => class_basename($exception),
                'context' => $exception->context,
            ]);

            if ((bool) config('ai.explanations.fallback_enabled', true)) {
                $fallback = $this->fallbackBuilder->build($context);

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

            throw new RecipeExplanationUnavailableException(
                previous: $exception
            );
        }
    }

    protected function payload(
        RecipeExplanationContext $context,
        string $source,
        array $explanation,
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
                'generated_at' => now()->toIso8601String(),
                'schema_version' => $context->schemaVersion,
                'prompt_version' => $context->promptVersion,
                'cached' => false,
            ],
        );
    }
}
