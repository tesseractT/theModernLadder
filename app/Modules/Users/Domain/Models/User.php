<?php

namespace App\Modules\Users\Domain\Models;

use App\Modules\Contributions\Domain\Models\Contribution;
use App\Modules\Moderation\Domain\Models\ModerationCase;
use App\Modules\Pantry\Domain\Models\PantryItem;
use App\Modules\Recipes\Domain\Models\RecipeTemplate;
use App\Modules\Reputation\Domain\Models\ContributorScore;
use App\Modules\Users\Domain\Enums\UserStatus;
use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Str;
use Laravel\Sanctum\HasApiTokens;

class User extends Authenticatable
{
    use HasApiTokens;
    use HasFactory;
    use HasUlids;
    use Notifiable;
    use SoftDeletes;

    protected $fillable = [
        'email',
        'password',
        'status',
        'last_seen_at',
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'last_seen_at' => 'datetime',
            'password' => 'hashed',
            'status' => UserStatus::class,
        ];
    }

    public function profile(): HasOne
    {
        return $this->hasOne(Profile::class);
    }

    public function preferences(): HasMany
    {
        return $this->hasMany(UserPreference::class);
    }

    public function foodPreference(): HasOne
    {
        return $this->hasOne(UserPreference::class)
            ->where('key', UserPreference::FOOD_PREFERENCES_KEY);
    }

    public function pantryItems(): HasMany
    {
        return $this->hasMany(PantryItem::class);
    }

    public function contributions(): HasMany
    {
        return $this->hasMany(Contribution::class, 'submitted_by_user_id');
    }

    public function contributorScore(): HasOne
    {
        return $this->hasOne(ContributorScore::class);
    }

    public function recipeTemplates(): HasMany
    {
        return $this->hasMany(RecipeTemplate::class, 'created_by_user_id');
    }

    public function reportedModerationCases(): HasMany
    {
        return $this->hasMany(ModerationCase::class, 'reported_by_user_id');
    }

    public function assignedModerationCases(): HasMany
    {
        return $this->hasMany(ModerationCase::class, 'assigned_to_user_id');
    }

    public function resolvedFoodPreferences(): array
    {
        return array_replace(
            config('user_preferences.defaults', []),
            $this->foodPreference?->value ?? []
        );
    }

    public function defaultDisplayName(): string
    {
        return Str::headline((string) Str::of($this->email)->before('@'));
    }
}
