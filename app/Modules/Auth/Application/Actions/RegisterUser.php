<?php

namespace App\Modules\Auth\Application\Actions;

use App\Modules\Users\Application\Services\AccountService;
use App\Modules\Users\Domain\Models\User;
use Illuminate\Support\Facades\DB;

class RegisterUser
{
    public function __construct(
        private readonly AccountService $accountService,
    ) {}

    public function execute(array $attributes): User
    {
        return DB::transaction(function () use ($attributes): User {
            $user = User::query()->create([
                'email' => $attributes['email'],
                'password' => $attributes['password'],
                'last_seen_at' => now(),
            ]);

            return $this->accountService->initialize($user, $attributes['name']);
        });
    }
}
