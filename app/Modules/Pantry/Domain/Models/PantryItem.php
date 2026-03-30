<?php

namespace App\Modules\Pantry\Domain\Models;

use App\Modules\Ingredients\Domain\Models\Ingredient;
use App\Modules\Pantry\Domain\Enums\PantryItemStatus;
use App\Modules\Users\Domain\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class PantryItem extends Model
{
    use HasFactory;
    use HasUlids;
    use SoftDeletes;

    protected $fillable = [
        'user_id',
        'ingredient_id',
        'entered_name',
        'quantity',
        'unit',
        'expires_on',
        'note',
        'status',
    ];

    protected function casts(): array
    {
        return [
            'expires_on' => 'date',
            'quantity' => 'decimal:2',
            'status' => PantryItemStatus::class,
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function ingredient(): BelongsTo
    {
        return $this->belongsTo(Ingredient::class);
    }

    public function scopeActive(Builder $query): Builder
    {
        return $query->where('status', PantryItemStatus::Active->value);
    }

    public function scopeOwnedBy(Builder $query, User|string $user): Builder
    {
        return $query->where(
            'user_id',
            $user instanceof User ? $user->getKey() : $user
        );
    }
}
