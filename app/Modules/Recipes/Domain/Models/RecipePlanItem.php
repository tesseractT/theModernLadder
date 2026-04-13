<?php

namespace App\Modules\Recipes\Domain\Models;

use App\Modules\Recipes\Domain\Enums\RecipePlanHorizon;
use App\Modules\Users\Domain\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class RecipePlanItem extends Model
{
    use HasFactory;
    use HasUlids;

    protected $fillable = [
        'user_id',
        'recipe_template_id',
        'horizon',
        'note',
    ];

    protected function casts(): array
    {
        return [
            'horizon' => RecipePlanHorizon::class,
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
