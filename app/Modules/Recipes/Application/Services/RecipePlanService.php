<?php

namespace App\Modules\Recipes\Application\Services;

use App\Modules\Recipes\Application\DTO\StoreRecipePlanItemData;
use App\Modules\Recipes\Application\DTO\UpdateRecipePlanItemData;
use App\Modules\Recipes\Domain\Enums\RecipePlanHorizon;
use App\Modules\Recipes\Domain\Models\RecipePlanItem;
use App\Modules\Recipes\Domain\Models\RecipeTemplate;
use App\Modules\Users\Domain\Models\User;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Validation\ValidationException;

class RecipePlanService
{
    public function paginateForUser(
        User $user,
        int $perPage,
        ?RecipePlanHorizon $horizon = null,
    ): LengthAwarePaginator {
        return RecipePlanItem::query()
            ->ownedBy($user)
            ->when(
                $horizon !== null,
                fn ($query) => $query->where('horizon', $horizon->value)
            )
            ->whereHas('recipeTemplate', fn ($query) => $query->published())
            ->with([
                'recipeTemplate' => fn ($query) => $query->published(),
            ])
            ->orderByRaw("CASE horizon WHEN 'today' THEN 1 WHEN 'tomorrow' THEN 2 ELSE 3 END")
            ->orderByDesc('updated_at')
            ->paginate($perPage)
            ->withQueryString();
    }

    public function createForUser(User $user, StoreRecipePlanItemData $payload): RecipePlanItem
    {
        $recipeTemplate = $this->resolvePublishedTemplate($payload->recipeTemplateId);

        $planItem = RecipePlanItem::query()
            ->firstOrNew([
                'user_id' => $user->id,
                'recipe_template_id' => $recipeTemplate->id,
                'horizon' => $payload->horizon->value,
            ]);

        $planItem->note = $payload->note;
        $planItem->save();

        return $planItem->load('recipeTemplate');
    }

    public function update(RecipePlanItem $recipePlanItem, UpdateRecipePlanItemData $payload): RecipePlanItem
    {
        $attributes = $payload->attributes();

        if (array_key_exists('horizon', $attributes) && $payload->horizon !== null) {
            $this->ensureUniqueHorizon($recipePlanItem, $payload->horizon);
            $attributes['horizon'] = $payload->horizon->value;
        }

        $recipePlanItem->fill($attributes);
        $recipePlanItem->save();

        return $recipePlanItem->fresh(['recipeTemplate']);
    }

    public function delete(RecipePlanItem $recipePlanItem): void
    {
        $recipePlanItem->delete();
    }

    protected function ensureUniqueHorizon(
        RecipePlanItem $recipePlanItem,
        RecipePlanHorizon $horizon,
    ): void {
        $exists = RecipePlanItem::query()
            ->ownedBy($recipePlanItem->user_id)
            ->where('recipe_template_id', $recipePlanItem->recipe_template_id)
            ->where('horizon', $horizon->value)
            ->whereKeyNot($recipePlanItem->getKey())
            ->exists();

        if (! $exists) {
            return;
        }

        throw ValidationException::withMessages([
            'horizon' => ['This recipe is already planned for that horizon.'],
        ]);
    }

    protected function resolvePublishedTemplate(string $recipeTemplateId): RecipeTemplate
    {
        return RecipeTemplate::query()
            ->published()
            ->findOrFail($recipeTemplateId);
    }
}
