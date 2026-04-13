<?php

namespace App\Modules\Recipes\Application\Services;

use App\Modules\Recipes\Application\DTO\SaveRecipeTemplateSuggestionData;
use App\Modules\Recipes\Domain\Enums\RecipeTemplateInteractionSource;
use App\Modules\Recipes\Domain\Enums\RecipeTemplateInteractionType;
use App\Modules\Recipes\Domain\Models\RecipeTemplate;
use App\Modules\Recipes\Domain\Models\RecipeTemplateInteraction;
use App\Modules\Users\Domain\Models\User;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

class RecipeTemplateInteractionService
{
    public function paginateFavoritesForUser(User $user, int $perPage): LengthAwarePaginator
    {
        return $this->interactionPaginator($user, RecipeTemplateInteractionType::Favorite, $perPage);
    }

    public function paginateSavedSuggestionsForUser(User $user, int $perPage): LengthAwarePaginator
    {
        return $this->interactionPaginator($user, RecipeTemplateInteractionType::SavedSuggestion, $perPage);
    }

    public function paginateRecentHistoryForUser(User $user, int $perPage): LengthAwarePaginator
    {
        return $this->interactionPaginator($user, RecipeTemplateInteractionType::RecentHistory, $perPage);
    }

    public function favorite(User $user, string $recipeTemplateId): RecipeTemplateInteraction
    {
        return $this->upsertInteraction(
            user: $user,
            recipeTemplate: $this->resolvePublishedTemplate($recipeTemplateId),
            interactionType: RecipeTemplateInteractionType::Favorite,
        );
    }

    public function unfavorite(User $user, string $recipeTemplateId): void
    {
        $this->removeInteraction($user, $recipeTemplateId, RecipeTemplateInteractionType::Favorite);
    }

    public function saveSuggestionForUser(
        User $user,
        string $recipeTemplateId,
        SaveRecipeTemplateSuggestionData $payload
    ): RecipeTemplateInteraction {
        return $this->upsertInteraction(
            user: $user,
            recipeTemplate: $this->resolvePublishedTemplate($recipeTemplateId),
            interactionType: RecipeTemplateInteractionType::SavedSuggestion,
            source: $payload->source,
            goal: $payload->goal,
        );
    }

    public function removeSavedSuggestion(User $user, string $recipeTemplateId): void
    {
        $this->removeInteraction($user, $recipeTemplateId, RecipeTemplateInteractionType::SavedSuggestion);
    }

    public function recordRecentHistory(
        User $user,
        string $recipeTemplateId,
        RecipeTemplateInteractionSource $source,
        ?string $goal = null,
    ): RecipeTemplateInteraction {
        $interaction = $this->upsertInteraction(
            user: $user,
            recipeTemplate: $this->resolvePublishedTemplate($recipeTemplateId),
            interactionType: RecipeTemplateInteractionType::RecentHistory,
            source: $source,
            goal: $goal,
        );

        $this->trimRecentHistory($user);

        return $interaction;
    }

    protected function interactionPaginator(
        User $user,
        RecipeTemplateInteractionType $interactionType,
        int $perPage
    ): LengthAwarePaginator {
        return RecipeTemplateInteraction::query()
            ->ownedBy($user)
            ->where('interaction_type', $interactionType->value)
            ->whereHas('recipeTemplate', fn ($query) => $query->published())
            ->with([
                'recipeTemplate' => fn ($query) => $query->published(),
            ])
            ->orderByDesc('interacted_at')
            ->orderByDesc('updated_at')
            ->paginate($perPage)
            ->withQueryString();
    }

    protected function upsertInteraction(
        User $user,
        RecipeTemplate $recipeTemplate,
        RecipeTemplateInteractionType $interactionType,
        ?RecipeTemplateInteractionSource $source = null,
        ?string $goal = null,
    ): RecipeTemplateInteraction {
        $interaction = RecipeTemplateInteraction::query()
            ->firstOrNew([
                'user_id' => $user->id,
                'recipe_template_id' => $recipeTemplate->id,
                'interaction_type' => $interactionType->value,
            ]);

        $interaction->fill([
            'source' => $source?->value,
            'goal' => $goal,
            'interacted_at' => now(),
        ]);
        $interaction->save();

        return $interaction->load('recipeTemplate');
    }

    protected function removeInteraction(
        User $user,
        string $recipeTemplateId,
        RecipeTemplateInteractionType $interactionType
    ): void {
        RecipeTemplateInteraction::query()
            ->ownedBy($user)
            ->where('recipe_template_id', $recipeTemplateId)
            ->where('interaction_type', $interactionType->value)
            ->delete();
    }

    protected function trimRecentHistory(User $user): void
    {
        $maxEntries = max(1, (int) config('recipes.retention.recent_history.max_entries', 25));

        $staleIds = RecipeTemplateInteraction::query()
            ->ownedBy($user)
            ->where('interaction_type', RecipeTemplateInteractionType::RecentHistory->value)
            ->orderByDesc('interacted_at')
            ->orderByDesc('updated_at')
            ->pluck('id')
            ->slice($maxEntries)
            ->values()
            ->all();

        if ($staleIds === []) {
            return;
        }

        RecipeTemplateInteraction::query()
            ->whereIn('id', $staleIds)
            ->delete();
    }

    protected function resolvePublishedTemplate(string $recipeTemplateId): RecipeTemplate
    {
        return RecipeTemplate::query()
            ->published()
            ->findOrFail($recipeTemplateId);
    }
}
