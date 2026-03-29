<?php

namespace App\Modules\Shared\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;

abstract class ApiController extends Controller
{
    protected function respond(array $payload, int $status = 200): JsonResponse
    {
        return response()->json($payload, $status);
    }
}
