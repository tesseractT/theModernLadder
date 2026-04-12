<?php

namespace App\Modules\Shared\Http\Middleware;

use App\Modules\Users\Domain\Enums\UserStatus;
use Closure;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureActiveUser
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if ($user !== null && $user->status !== UserStatus::Active) {
            throw new AuthorizationException;
        }

        return $next($request);
    }
}
