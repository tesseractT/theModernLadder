<?php

namespace App\Modules\Admin\Domain\Enums;

enum AdminEventStream: string
{
    case SecurityAudit = 'security_audit';
    case AiExplanationFailure = 'ai_explanation_failure';

    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }
}
