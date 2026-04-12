<?php

namespace App\Modules\Auth\Http\Controllers;

use App\Modules\Shared\Application\Services\SecurityAuditLogger;
use App\Modules\Shared\Http\Controllers\ApiController;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class LogoutController extends ApiController
{
    public function __invoke(Request $request, SecurityAuditLogger $securityAuditLogger): JsonResponse
    {
        $currentToken = $request->user()?->currentAccessToken();

        $currentToken?->delete();

        $securityAuditLogger->log(
            event: 'auth.logout.succeeded',
            request: $request,
            context: [
                'target_type' => 'personal_access_token',
                'target_id' => $currentToken?->getKey(),
            ],
        );

        return $this->respond([
            'message' => 'Logout completed successfully.',
        ]);
    }
}
