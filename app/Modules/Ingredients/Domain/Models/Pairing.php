<?php

namespace App\Modules\Ingredients\Domain\Models;

use App\Modules\Shared\Domain\Enums\ContentStatus;
use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class Pairing extends Model
{
    use HasFactory;
    use HasUlids;
    use SoftDeletes;

    protected $fillable = [
        'ingredient_id',
        'paired_ingredient_id',
        'strength',
        'note',
        'status',
    ];

    protected function casts(): array
    {
        return [
            'strength' => 'integer',
            'status' => ContentStatus::class,
        ];
    }

    public function ingredient(): BelongsTo
    {
        return $this->belongsTo(Ingredient::class);
    }

    public function pairedIngredient(): BelongsTo
    {
        return $this->belongsTo(Ingredient::class, 'paired_ingredient_id');
    }
}
