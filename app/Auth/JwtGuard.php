<?php

namespace App\Auth;

use App\Services\JwtService;
use Illuminate\Auth\GuardHelpers;
use Illuminate\Contracts\Auth\Guard;
use Illuminate\Contracts\Auth\UserProvider;
use Illuminate\Http\Request;

class JwtGuard implements Guard
{
    use GuardHelpers;

    public function __construct(
        private readonly JwtService $jwtService,
        UserProvider $provider,
        private readonly Request $request,
    ) {
        $this->provider = $provider;
    }

    public function user(): mixed
    {
        if ($this->user !== null) {
            return $this->user;
        }

        $token = $this->jwtService->extractTokenFromRequest($this->request);
        if (! $token) {
            return null;
        }

        try {
            $payload = $this->jwtService->verifyAccessToken($token);
            $this->user = $this->provider->retrieveById($payload->sub);
        } catch (\Throwable) {
            return null;
        }

        return $this->user;
    }

    public function validate(array $credentials = []): bool
    {
        return false;
    }
}
