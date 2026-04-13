<?php

namespace App\Modules\Admin\Domain\Models;

use App\Modules\Admin\Domain\Enums\AdminEventStream;
use App\Modules\Users\Domain\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AdminEvent extends Model
{
    use HasFactory;
    use HasUlids;

    public $timestamps = false;

    protected $fillable = [
        'stream',
        'event',
        'actor_user_id',
        'target_type',
        'target_id',
        'request_id',
        'route_name',
        'metadata',
        'occurred_at',
        'created_at',
    ];

    protected function casts(): array
    {
        return [
            'stream' => AdminEventStream::class,
            'metadata' => 'array',
            'occurred_at' => 'datetime',
            'created_at' => 'datetime',
        ];
    }

    public function actor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'actor_user_id');
    }

    public function scopeSecurityAudit(Builder $query): Builder
    {
        return $query->where('stream', AdminEventStream::SecurityAudit->value);
    }

    public function scopeAiExplanationFailures(Builder $query): Builder
    {
        return $query->where('stream', AdminEventStream::AiExplanationFailure->value);
    }
}
