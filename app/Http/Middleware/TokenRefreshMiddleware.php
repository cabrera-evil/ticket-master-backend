<?php

namespace App\Http\Middleware;

use App\Services\JwtService;
use Closure;
use Illuminate\Auth\AuthenticationException;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class TokenRefreshMiddleware
{
    private const SKIP_PATHS = [
        'login',
        'register',
        'refresh-token',
        'password',
    ];

    public function __construct(private readonly JwtService $jwtService) {}

    public function handle(Request $request, Closure $next): Response
    {
        if ($this->shouldSkip($request)) {
            return $next($request);
        }

        $accessToken = $this->jwtService->extractTokenFromRequest($request);

        if ($accessToken && $this->jwtService->shouldRefreshToken($accessToken)) {
            return $this->refreshAndContinue($request, $next);
        }

        try {
            return $next($request);
        } catch (AuthenticationException) {
            if ($request->cookie(config('jwt.refresh_cookie_name'))) {
                return $this->refreshAndContinue($request, $next);
            }
            throw new AuthenticationException('Unauthenticated.');
        }
    }

    private function shouldSkip(Request $request): bool
    {
        $path = $request->path();

        foreach (self::SKIP_PATHS as $segment) {
            if (str_contains($path, $segment)) {
                return true;
            }
        }

        return false;
    }

    private function refreshAndContinue(Request $request, Closure $next): Response
    {
        $refreshToken = $request->cookie(config('jwt.refresh_cookie_name'));

        if (! $refreshToken) {
            throw new AuthenticationException('NO_REFRESH_TOKEN');
        }

        try {
            $payload = $this->jwtService->verifyRefreshToken($refreshToken);
        } catch (\Throwable) {
            $response = response()->json([
                'statusCode' => Response::HTTP_UNAUTHORIZED,
                'message' => 'INVALID_REFRESH_TOKEN',
            ], Response::HTTP_UNAUTHORIZED);

            $this->jwtService->invalidateAuthCookies($response);

            return $response;
        }

        // Generate tokens once, use them for both the request header and response cookies
        $tokens = $this->jwtService->generateTokenPair($payload->sub);

        $request->headers->set('Authorization', 'Bearer '.$tokens['jwt']);

        $response = $next($request);

        $this->jwtService->attachCookies($tokens, $response);
        $response->headers->set('X-Token-Refreshed', 'true');
        $response->headers->set('X-New-Access-Token', $tokens['jwt']);

        return $response;
    }
}
