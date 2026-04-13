<?php

namespace App\Modules\Contributions\Domain\Models;

use App\Modules\Contributions\Domain\Enums\ContributionAction;
use App\Modules\Contributions\Domain\Enums\ContributionStatus;
use App\Modules\Contributions\Domain\Enums\ContributionType;
use App\Modules\Moderation\Domain\Models\ModerationAction;
use App\Modules\Moderation\Domain\Models\ModerationCase;
use App\Modules\Users\Domain\Models\User;
use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphTo;

class Contribution extends Model
{
    use HasFactory;
    use HasUlids;

    protected $fillable = [
        'submitted_by_user_id',
        'reviewed_by_user_id',
        'subject_type',
        'subject_id',
        'action',
        'type',
        'status',
        'payload',
        'review_notes',
        'reviewed_at',
    ];

    protected function casts(): array
    {
        return [
            'action' => ContributionAction::class,
            'type' => ContributionType::class,
            'status' => ContributionStatus::class,
            'payload' => 'array',
            'reviewed_at' => 'datetime',
        ];
    }

    protected static function booted(): void
    {
        static::creating(function (self $contribution): void {
            if ($contribution->status === null) {
                $contribution->status = ContributionStatus::Pending;
            }
        });
    }

    public function submitter(): BelongsTo
    {
        return $this->belongsTo(User::class, 'submitted_by_user_id');
    }

    public function reviewer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'reviewed_by_user_id');
    }

    public function moderationCases(): HasMany
    {
        return $this->hasMany(ModerationCase::class);
    }

    public function moderationActions(): HasMany
    {
        return $this->hasMany(ModerationAction::class);
    }

    public function subject(): MorphTo
    {
        return $this->morphTo();
    }
}
