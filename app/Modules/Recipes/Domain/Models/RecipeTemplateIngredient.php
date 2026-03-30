<?php

namespace App\Modules\Recipes\Domain\Models;

use App\Modules\Ingredients\Domain\Models\Ingredient;
use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class RecipeTemplateIngredient extends Model
{
    use HasFactory;
    use HasUlids;

    protected $fillable = [
        'recipe_template_id',
        'ingredient_id',
        'is_required',
        'sort_order',
    ];

    protected function casts(): array
    {
        return [
            'is_required' => 'boolean',
            'sort_order' => 'integer',
        ];
    }

    public function recipeTemplate(): BelongsTo
    {
        return $this->belongsTo(RecipeTemplate::class);
    }

    public function ingredient(): BelongsTo
    {
        return $this->belongsTo(Ingredient::class);
    }
}
