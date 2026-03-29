<?php

namespace App\Modules\Auth\Http\Controllers;

use App\Modules\Shared\Http\Controllers\ApiController;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class LogoutController extends ApiController
{
    public function __invoke(Request $request): JsonResponse
    {
        $request->user()?->currentAccessToken()?->delete();

        return $this->respond([
            'message' => 'Logout completed successfully.',
        ]);
    }
}
