<?php

namespace App\Http\Controllers\Api\V1;

use App\Enums\UserStatus;
use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\ForgotPasswordRequest;
use App\Http\Requests\Auth\LoginRequest;
use App\Http\Requests\Auth\RegisterCompanyRequest;
use App\Http\Requests\Auth\RegisterRequest;
use App\Http\Requests\Auth\ResetPasswordRequest;
use App\Http\Requests\Auth\VerifyResetTokenRequest;
use App\Http\Resources\UserResource;
use App\Models\User;
use App\Services\AuthRegistrationService;
use App\Services\JwtService;
use Illuminate\Auth\AuthenticationException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Password;
use Illuminate\Validation\ValidationException;
use Symfony\Component\HttpFoundation\Response;

class AuthController extends Controller
{
    public function __construct(
        private readonly AuthRegistrationService $registrationService,
        private readonly JwtService $jwtService,
    ) {}

    public function register(RegisterRequest $request): JsonResponse
    {
        $user = $this->registrationService->registerPortfolioUser($request->validated());
        $tokens = $this->jwtService->generateTokenPair($user->id);

        $response = response()->json([
            'data' => array_merge($tokens, ['user' => new UserResource($user->loadMissing(['role', 'client', 'company']))]),
        ], Response::HTTP_CREATED);

        $this->jwtService->attachCookies($tokens, $response);

        return $response;
    }

    public function registerCompany(RegisterCompanyRequest $request): JsonResponse
    {
        $user = $this->registrationService->registerCompany($request->validated());
        $tokens = $this->jwtService->generateTokenPair($user->id);

        $response = response()->json([
            'data' => array_merge($tokens, ['user' => new UserResource($user->loadMissing(['role', 'client', 'company']))]),
        ], Response::HTTP_CREATED);

        $this->jwtService->attachCookies($tokens, $response);

        return $response;
    }

    public function login(LoginRequest $request): JsonResponse
    {
        $identifier = $request->string('identifier')->toString();
        $user = User::query()
            ->where('email', $identifier)
            ->orWhere('username', $identifier)
            ->first();

        if ($user === null || ! Hash::check($request->string('password')->toString(), $user->password)) {
            throw ValidationException::withMessages([
                'identifier' => ['Las credenciales no son validas.'],
            ]);
        }

        if ($user->status !== UserStatus::Active) {
            return response()->json(['message' => 'La cuenta no esta activa.'], Response::HTTP_FORBIDDEN);
        }

        $tokens = $this->jwtService->generateTokenPair($user->id);

        $response = response()->json([
            'data' => array_merge($tokens, [
                'user' => new UserResource($user->loadMissing(['role', 'client', 'company'])),
            ]),
        ]);

        $this->jwtService->attachCookies($tokens, $response);

        return $response;
    }

    public function refreshToken(Request $request): JsonResponse
    {
        $refreshToken = $request->cookie(config('jwt.refresh_cookie_name'));

        if (! $refreshToken) {
            throw new AuthenticationException('NO_REFRESH_TOKEN');
        }

        try {
            $payload = $this->jwtService->verifyRefreshToken($refreshToken);
        } catch (\Throwable) {
            $response = response()->json(['message' => 'INVALID_REFRESH_TOKEN'], Response::HTTP_UNAUTHORIZED);
            $this->jwtService->invalidateAuthCookies($response);

            return $response;
        }

        $tokens = $this->jwtService->generateTokenPair($payload->sub);
        $response = response()->json([
            'data' => array_merge($tokens, [
                'user' => new UserResource(User::query()->findOrFail($payload->sub)->loadMissing(['role', 'client', 'company'])),
            ]),
        ]);
        $this->jwtService->attachCookies($tokens, $response);

        return $response;
    }

    public function logout(): JsonResponse
    {
        $response = response()->json(['data' => ['message' => 'Logged out successfully']]);
        $this->jwtService->invalidateAuthCookies($response);

        return $response;
    }

    public function profile(Request $request): JsonResponse
    {
        $user = $request->user();
        if (! $user instanceof User) {
            throw new AuthenticationException('Unauthenticated.');
        }

        return response()->json([
            'data' => new UserResource($user->loadMissing(['role', 'client', 'company'])),
        ]);
    }

    public function requestResetToken(ForgotPasswordRequest $request): JsonResponse
    {
        Password::sendResetLink($request->only('email'));

        return response()->json([
            'data' => ['message' => 'If a user with that email exists, a reset token has been sent.'],
        ]);
    }

    public function verifyResetToken(VerifyResetTokenRequest $request): JsonResponse
    {
        if ($this->findEmailByResetToken($request->string('token')->toString()) === null) {
            return response()->json(['message' => 'RESET_PASSWORD_TOKEN_EXPIRED'], Response::HTTP_UNAUTHORIZED);
        }

        return response()->json([
            'data' => ['message' => 'Reset token is valid.'],
        ]);
    }

    public function resetPassword(ResetPasswordRequest $request): JsonResponse
    {
        $email = $this->findEmailByResetToken($request->string('token')->toString());

        if ($email === null) {
            return response()->json(['message' => 'RESET_PASSWORD_TOKEN_EXPIRED'], Response::HTTP_UNAUTHORIZED);
        }

        $status = Password::reset(
            [
                'email' => $email,
                'password' => $request->string('password')->toString(),
                'password_confirmation' => $request->string('password')->toString(),
                'token' => $request->string('token')->toString(),
            ],
            function (User $user, string $password): void {
                $user->forceFill(['password' => $password])->save();
            }
        );

        if ($status !== Password::PASSWORD_RESET) {
            return response()->json(['message' => 'RESET_PASSWORD_TOKEN_EXPIRED'], Response::HTTP_UNAUTHORIZED);
        }

        return response()->json([
            'data' => ['message' => 'Password has been reset successfully.'],
        ]);
    }

    private function findEmailByResetToken(string $token): ?string
    {
        $records = DB::table('password_reset_tokens')
            ->select('email', 'token')
            ->get();

        foreach ($records as $record) {
            if (Hash::check($token, $record->token)) {
                return (string) $record->email;
            }
        }

        return null;
    }
}
