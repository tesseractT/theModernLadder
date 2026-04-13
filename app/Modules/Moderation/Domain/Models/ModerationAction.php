<?php

namespace App\Modules\Moderation\Domain\Models;

use App\Modules\Contributions\Domain\Enums\ContributionStatus;
use App\Modules\Contributions\Domain\Models\Contribution;
use App\Modules\Moderation\Domain\Enums\ModerationActionType;
use App\Modules\Users\Domain\Models\User;
use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ModerationAction extends Model
{
    use HasFactory;
    use HasUlids;

    protected $fillable = [
        'contribution_id',
        'moderation_case_id',
        'actor_user_id',
        'action',
        'from_status',
        'to_status',
        'reason_code',
        'notes',
        'request_id',
    ];

    protected function casts(): array
    {
        return [
            'action' => ModerationActionType::class,
            'from_status' => ContributionStatus::class,
            'to_status' => ContributionStatus::class,
        ];
    }

    public function contribution(): BelongsTo
    {
        return $this->belongsTo(Contribution::class);
    }

    public function moderationCase(): BelongsTo
    {
        return $this->belongsTo(ModerationCase::class);
    }

    public function actor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'actor_user_id');
    }
}
