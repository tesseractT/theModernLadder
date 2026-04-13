<?php

namespace App\Modules\Contributions\Application\Services;

use App\Modules\Contributions\Application\DTO\StoreContributionData;
use App\Modules\Contributions\Domain\Enums\ContributionStatus;
use App\Modules\Contributions\Domain\Models\Contribution;
use App\Modules\Users\Domain\Models\User;

class ContributionService
{
    public function submit(User $user, StoreContributionData $payload): Contribution
    {
        $subject = $payload->subjectType->resolvePublishedSubject($payload->subjectId);

        return Contribution::query()->create([
            'submitted_by_user_id' => $user->id,
            'subject_type' => $subject::class,
            'subject_id' => $subject->getKey(),
            'action' => $payload->type->action(),
            'type' => $payload->type,
            'status' => ContributionStatus::Pending,
            'payload' => $payload->payload,
        ]);
    }
}
