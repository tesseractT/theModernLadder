<?php

namespace App\Modules\Recipes\Domain\Models;

use App\Modules\Recipes\Domain\Enums\RecipeTemplateInteractionSource;
use App\Modules\Recipes\Domain\Enums\RecipeTemplateInteractionType;
use App\Modules\Users\Domain\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class RecipeTemplateInteraction extends Model
{
    use HasFactory;
    use HasUlids;

    protected $fillable = [
        'user_id',
        'recipe_template_id',
        'interaction_type',
        'source',
        'goal',
        'interacted_at',
    ];

    protected function casts(): array
    {
        return [
            'interaction_type' => RecipeTemplateInteractionType::class,
            'source' => RecipeTemplateInteractionSource::class,
            'interacted_at' => 'datetime',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function recipeTemplate(): BelongsTo
    {
        return $this->belongsTo(RecipeTemplate::class);
    }

    public function scopeOwnedBy(Builder $query, User|string $user): Builder
    {
        return $query->where(
            'user_id',
            $user instanceof User ? $user->getKey() : $user
        );
    }
}
