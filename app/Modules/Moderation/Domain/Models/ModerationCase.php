<?php

namespace App\Modules\Moderation\Domain\Models;

use App\Modules\Contributions\Domain\Models\Contribution;
use App\Modules\Moderation\Domain\Enums\ModerationCaseStatus;
use App\Modules\Users\Domain\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphTo;

class ModerationCase extends Model
{
    use HasFactory;
    use HasUlids;

    protected $fillable = [
        'subject_type',
        'subject_id',
        'contribution_id',
        'reported_by_user_id',
        'assigned_to_user_id',
        'status',
        'reason_code',
        'notes',
        'resolved_at',
    ];

    protected function casts(): array
    {
        return [
            'status' => ModerationCaseStatus::class,
            'resolved_at' => 'datetime',
        ];
    }

    public function subject(): MorphTo
    {
        return $this->morphTo();
    }

    public function contribution(): BelongsTo
    {
        return $this->belongsTo(Contribution::class);
    }

    public function scopeActive(Builder $query): Builder
    {
        return $query->whereIn('status', ModerationCaseStatus::activeValues());
    }

    public function actions(): HasMany
    {
        return $this->hasMany(ModerationAction::class);
    }

    public function reporter(): BelongsTo
    {
        return $this->belongsTo(User::class, 'reported_by_user_id');
    }

    public function assignee(): BelongsTo
    {
        return $this->belongsTo(User::class, 'assigned_to_user_id');
    }
}
