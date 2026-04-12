<?php

namespace App\Modules\Auth\Http\Controllers;

use App\Modules\Shared\Application\Services\SecurityAuditLogger;
use App\Modules\Shared\Http\Controllers\ApiController;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class LogoutAllController extends ApiController
{
    public function __invoke(Request $request, SecurityAuditLogger $securityAuditLogger): JsonResponse
    {
        $user = $request->user();
        $revokedTokenCount = $user?->tokens()->count() ?? 0;

        $user?->tokens()->delete();

        $securityAuditLogger->log(
            event: 'auth.logout_all.succeeded',
            request: $request,
            context: [
                'target_type' => 'user',
                'target_id' => $user?->getKey(),
                'revoked_token_count' => $revokedTokenCount,
            ],
        );

        return $this->respond([
            'message' => 'All active tokens revoked successfully.',
        ]);
    }
}
