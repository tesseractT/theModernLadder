<?php

namespace App\Modules\Recipes\Domain\Models;

use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class RecipeTemplateStep extends Model
{
    use HasFactory;
    use HasUlids;

    protected $fillable = [
        'recipe_template_id',
        'position',
        'instruction',
    ];

    protected function casts(): array
    {
        return [
            'position' => 'integer',
        ];
    }

    public function recipeTemplate(): BelongsTo
    {
        return $this->belongsTo(RecipeTemplate::class);
    }
}
