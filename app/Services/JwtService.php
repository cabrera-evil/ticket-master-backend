<?php

namespace App\Services;

use Firebase\JWT\JWT;
use Firebase\JWT\Key;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Cookie;
use Symfony\Component\HttpFoundation\Response;

class JwtService
{
    private string $secret;
    private string $refreshSecret;
    private int $ttl;
    private int $refreshTtl;
    private string $cookieName;
    private string $refreshCookieName;

    public function __construct()
    {
        $this->secret = config('jwt.secret');
        $this->refreshSecret = config('jwt.refresh_secret');
        $this->ttl = config('jwt.ttl');
        $this->refreshTtl = config('jwt.refresh_ttl');
        $this->cookieName = config('jwt.cookie_name');
        $this->refreshCookieName = config('jwt.refresh_cookie_name');
    }

    public function generateAccessToken(int $userId): string
    {
        $now = time();

        return JWT::encode([
            'sub' => $userId,
            'iat' => $now,
            'exp' => $now + $this->ttl,
        ], $this->secret, 'HS256');
    }

    public function generateRefreshToken(int $userId): string
    {
        $now = time();

        return JWT::encode([
            'sub' => $userId,
            'iat' => $now,
            'exp' => $now + $this->refreshTtl,
        ], $this->refreshSecret, 'HS256');
    }

    /** Generate both tokens without touching any response. */
    public function generateTokenPair(int $userId): array
    {
        return [
            'jwt' => $this->generateAccessToken($userId),
            'refreshToken' => $this->generateRefreshToken($userId),
        ];
    }

    public function verifyAccessToken(string $token): object
    {
        return JWT::decode($token, new Key($this->secret, 'HS256'));
    }

    public function verifyRefreshToken(string $token): object
    {
        return JWT::decode($token, new Key($this->refreshSecret, 'HS256'));
    }

    public function decodeWithoutVerification(string $token): ?object
    {
        try {
            $parts = explode('.', $token);
            if (count($parts) !== 3) {
                return null;
            }

            $payload = json_decode(base64_decode(strtr($parts[1], '-_', '+/')));

            return $payload ?: null;
        } catch (\Throwable) {
            return null;
        }
    }

    /** Attach pre-generated token cookies to an existing response. */
    public function attachCookies(array $tokens, Response $response): void
    {
        $secure = app()->isProduction();

        $response->headers->setCookie(
            Cookie::create($this->cookieName)
                ->withValue($tokens['jwt'])
                ->withExpires(time() + $this->ttl)
                ->withHttpOnly(true)
                ->withSecure($secure)
                ->withSameSite('lax')
        );

        $response->headers->setCookie(
            Cookie::create($this->refreshCookieName)
                ->withValue($tokens['refreshToken'])
                ->withExpires(time() + $this->refreshTtl)
                ->withHttpOnly(true)
                ->withSecure($secure)
                ->withSameSite('lax')
        );
    }

    /** Generate a token pair and set them as cookies on the given response. */
    public function setAuthCookies(int $userId, Response $response): array
    {
        $tokens = $this->generateTokenPair($userId);
        $this->attachCookies($tokens, $response);

        return $tokens;
    }

    public function invalidateAuthCookies(Response $response): void
    {
        $response->headers->setCookie(
            Cookie::create($this->cookieName)
                ->withValue('')
                ->withExpires(time() - 3600)
                ->withHttpOnly(true)
                ->withSameSite('lax')
        );

        $response->headers->setCookie(
            Cookie::create($this->refreshCookieName)
                ->withValue('')
                ->withExpires(time() - 3600)
                ->withHttpOnly(true)
                ->withSameSite('lax')
        );
    }

    public function extractTokenFromRequest(Request $request): ?string
    {
        $authHeader = $request->header('Authorization');
        if ($authHeader && str_starts_with($authHeader, 'Bearer ')) {
            return substr($authHeader, 7);
        }

        return $request->cookie($this->cookieName) ?: null;
    }

    public function shouldRefreshToken(string $token): bool
    {
        $payload = $this->decodeWithoutVerification($token);
        if (! $payload || ! isset($payload->exp)) {
            return false;
        }

        return ($payload->exp - time()) < 300;
    }
}
