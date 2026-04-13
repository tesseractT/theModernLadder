<?php

namespace App\Modules\Contributions\Http\Resources;

use App\Modules\Contributions\Domain\Enums\ContributionSubjectType;
use App\Modules\Ingredients\Domain\Models\Ingredient;
use App\Modules\Recipes\Domain\Models\RecipeTemplate;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ContributionSubjectResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return match (true) {
            $this->resource instanceof Ingredient => [
                'type' => ContributionSubjectType::Ingredient->value,
                'id' => $this->id,
                'name' => $this->name,
                'slug' => $this->slug,
                'description' => $this->description,
            ],
            $this->resource instanceof RecipeTemplate => [
                'type' => ContributionSubjectType::RecipeTemplate->value,
                'id' => $this->id,
                'title' => $this->title,
                'slug' => $this->slug,
                'summary' => $this->summary,
            ],
            default => [
                'type' => ContributionSubjectType::fromModel($this->resource)?->value,
                'id' => $this->id,
            ],
        };
    }
}
