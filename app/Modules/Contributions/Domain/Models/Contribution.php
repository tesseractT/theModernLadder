<?php

namespace App\Modules\Contributions\Domain\Models;

use App\Modules\Contributions\Domain\Enums\ContributionAction;
use App\Modules\Contributions\Domain\Enums\ContributionStatus;
use App\Modules\Users\Domain\Models\User;
use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
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
        'status',
        'payload',
        'review_notes',
        'reviewed_at',
    ];

    protected function casts(): array
    {
        return [
            'action' => ContributionAction::class,
            'status' => ContributionStatus::class,
            'payload' => 'array',
            'reviewed_at' => 'datetime',
        ];
    }

    public function submitter(): BelongsTo
    {
        return $this->belongsTo(User::class, 'submitted_by_user_id');
    }

    public function reviewer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'reviewed_by_user_id');
    }

    public function subject(): MorphTo
    {
        return $this->morphTo();
    }
}
