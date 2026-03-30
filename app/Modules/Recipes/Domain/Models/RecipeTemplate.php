<?php

namespace App\Modules\Recipes\Domain\Models;

use App\Modules\Recipes\Domain\Enums\RecipeDifficulty;
use App\Modules\Recipes\Domain\Enums\RecipeType;
use App\Modules\Shared\Domain\Enums\ContentStatus;
use App\Modules\Users\Domain\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class RecipeTemplate extends Model
{
    use HasFactory;
    use HasUlids;
    use SoftDeletes;

    protected $fillable = [
        'title',
        'slug',
        'recipe_type',
        'difficulty',
        'dietary_patterns',
        'summary',
        'instructions',
        'servings',
        'prep_minutes',
        'cook_minutes',
        'status',
        'created_by_user_id',
    ];

    protected function casts(): array
    {
        return [
            'recipe_type' => RecipeType::class,
            'difficulty' => RecipeDifficulty::class,
            'dietary_patterns' => 'array',
            'servings' => 'integer',
            'prep_minutes' => 'integer',
            'cook_minutes' => 'integer',
            'status' => ContentStatus::class,
        ];
    }

    public function scopePublished(Builder $query): Builder
    {
        return $query->where('status', ContentStatus::Published->value);
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by_user_id');
    }

    public function templateIngredients(): HasMany
    {
        return $this->hasMany(RecipeTemplateIngredient::class)
            ->orderBy('sort_order')
            ->orderBy('created_at');
    }

    public function steps(): HasMany
    {
        return $this->hasMany(RecipeTemplateStep::class)
            ->orderBy('position')
            ->orderBy('created_at');
    }
}
