<?php

namespace App\Modules\Users\Domain\Models;

use App\Modules\Contributions\Domain\Models\Contribution;
use App\Modules\Moderation\Domain\Models\ModerationCase;
use App\Modules\Pantry\Domain\Models\PantryItem;
use App\Modules\Recipes\Domain\Models\RecipePlanItem;
use App\Modules\Recipes\Domain\Models\RecipeTemplate;
use App\Modules\Recipes\Domain\Models\RecipeTemplateInteraction;
use App\Modules\Reputation\Domain\Models\ContributorScore;
use App\Modules\Users\Domain\Enums\UserRole;
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
            'role' => UserRole::class,
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

    public function recipeTemplateInteractions(): HasMany
    {
        return $this->hasMany(RecipeTemplateInteraction::class);
    }

    public function recipePlanItems(): HasMany
    {
        return $this->hasMany(RecipePlanItem::class);
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

    public function roleOrDefault(): UserRole
    {
        return $this->role ?? UserRole::User;
    }

    public function hasRole(UserRole|string $role): bool
    {
        $expectedRole = is_string($role) ? UserRole::from($role) : $role;

        return $this->roleOrDefault() === $expectedRole;
    }

    public function isUser(): bool
    {
        return $this->hasRole(UserRole::User);
    }

    public function isModerator(): bool
    {
        return $this->hasRole(UserRole::Moderator);
    }

    public function isAdmin(): bool
    {
        return $this->hasRole(UserRole::Admin);
    }

    public function canModerate(): bool
    {
        return $this->isModerator() || $this->isAdmin();
    }
}
