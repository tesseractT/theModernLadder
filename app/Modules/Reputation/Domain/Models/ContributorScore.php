<?php

namespace App\Modules\Reputation\Domain\Models;

use App\Modules\Users\Domain\Models\User;
use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ContributorScore extends Model
{
    use HasFactory;
    use HasUlids;

    protected $fillable = [
        'user_id',
        'score',
        'accepted_contributions_count',
        'rejected_contributions_count',
        'last_contribution_at',
    ];

    protected function casts(): array
    {
        return [
            'score' => 'integer',
            'accepted_contributions_count' => 'integer',
            'rejected_contributions_count' => 'integer',
            'last_contribution_at' => 'datetime',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
