<?php

namespace App\Modules\Ingredients\Domain\Models;

use App\Modules\Pantry\Domain\Models\PantryItem;
use App\Modules\Recipes\Domain\Models\RecipeTemplateIngredient;
use App\Modules\Shared\Domain\Enums\ContentStatus;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Ingredient extends Model
{
    use HasFactory;
    use HasUlids;
    use SoftDeletes;

    protected $fillable = [
        'name',
        'slug',
        'description',
        'status',
    ];

    protected function casts(): array
    {
        return [
            'status' => ContentStatus::class,
        ];
    }

    public function scopePublished(Builder $query): Builder
    {
        return $query->where('status', ContentStatus::Published->value);
    }

    public function aliases(): HasMany
    {
        return $this->hasMany(IngredientAlias::class);
    }

    public function pairings(): HasMany
    {
        return $this->hasMany(Pairing::class);
    }

    public function pairedBy(): HasMany
    {
        return $this->hasMany(Pairing::class, 'paired_ingredient_id');
    }

    public function substitutions(): HasMany
    {
        return $this->hasMany(Substitution::class);
    }

    public function substituteFor(): HasMany
    {
        return $this->hasMany(Substitution::class, 'substitute_ingredient_id');
    }

    public function pantryItems(): HasMany
    {
        return $this->hasMany(PantryItem::class);
    }

    public function recipeTemplateIngredients(): HasMany
    {
        return $this->hasMany(RecipeTemplateIngredient::class);
    }
}
