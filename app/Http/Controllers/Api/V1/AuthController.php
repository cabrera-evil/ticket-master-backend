<?php

namespace App\Http\Controllers\Api\V1;

use App\Enums\UserStatus;
use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\ForgotPasswordRequest;
use App\Http\Requests\Auth\LoginRequest;
use App\Http\Requests\Auth\RegisterClientRequest;
use App\Http\Requests\Auth\RegisterCompanyRequest;
use App\Http\Requests\Auth\ResetPasswordRequest;
use App\Http\Resources\UserResource;
use App\Models\User;
use App\Services\AuthRegistrationService;
use App\Services\JwtService;
use Illuminate\Auth\AuthenticationException;
use Illuminate\Auth\Events\PasswordReset;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
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

    public function registerClient(RegisterClientRequest $request): JsonResponse
    {
        $user = $this->registrationService->registerClient($request->validated());
        $tokens = $this->jwtService->generateTokenPair($user->id);

        $response = $this->apiResponse(
            'Cliente registrado correctamente.',
            array_merge($tokens, ['user' => new UserResource($user)]),
            Response::HTTP_CREATED
        );

        $this->jwtService->attachCookies($tokens, $response);

        return $response;
    }

    public function registerCompany(RegisterCompanyRequest $request): JsonResponse
    {
        $user = $this->registrationService->registerCompany($request->validated());
        $tokens = $this->jwtService->generateTokenPair($user->id);

        $response = $this->apiResponse(
            'Empresa registrada correctamente. Queda pendiente de aprobacion.',
            array_merge($tokens, ['user' => new UserResource($user)]),
            Response::HTTP_CREATED
        );

        $this->jwtService->attachCookies($tokens, $response);

        return $response;
    }

    public function login(LoginRequest $request): JsonResponse
    {
        $login = $request->string('login')->toString();
        $user = User::query()
            ->where('email', $login)
            ->orWhere('username', $login)
            ->first();

        if ($user === null || ! Hash::check($request->string('password')->toString(), $user->password)) {
            throw ValidationException::withMessages([
                'login' => ['Las credenciales no son validas.'],
            ]);
        }

        if ($user->status !== UserStatus::Active) {
            return $this->apiResponse('La cuenta no esta activa.', statusCode: Response::HTTP_FORBIDDEN);
        }

        $tokens = $this->jwtService->generateTokenPair($user->id);

        $response = $this->apiResponse('Inicio de sesion correcto.', array_merge($tokens, [
            'user' => new UserResource($user->loadMissing(['role', 'client', 'company'])),
        ]));

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
            $response = $this->apiResponse('INVALID_REFRESH_TOKEN', statusCode: Response::HTTP_UNAUTHORIZED);
            $this->jwtService->invalidateAuthCookies($response);

            return $response;
        }

        $tokens = $this->jwtService->generateTokenPair($payload->sub);
        $response = $this->apiResponse('Tokens actualizados correctamente.', $tokens);
        $this->jwtService->attachCookies($tokens, $response);

        return $response;
    }

    public function logout(Request $request): JsonResponse
    {
        $response = $this->apiResponse('Sesion cerrada correctamente.');
        $this->jwtService->invalidateAuthCookies($response);

        return $response;
    }

    public function forgotPassword(ForgotPasswordRequest $request): JsonResponse
    {
        Password::sendResetLink($request->only('email'));

        return $this->apiResponse('Si el correo existe, se enviaran instrucciones para restablecer la contrasena.', statusCode: Response::HTTP_ACCEPTED);
    }

    public function resetPassword(ResetPasswordRequest $request): JsonResponse
    {
        $status = Password::reset(
            $request->only('email', 'password', 'password_confirmation', 'token'),
            function (User $user, string $password): void {
                $user->forceFill(['password' => $password])->save();

                event(new PasswordReset($user));
            }
        );

        if ($status !== Password::PASSWORD_RESET) {
            throw ValidationException::withMessages([
                'email' => ['El token de recuperacion no es valido o ha expirado.'],
            ]);
        }

        return $this->apiResponse('Contrasena actualizada correctamente.');
    }
}
