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
use Illuminate\Auth\Events\PasswordReset;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Password;
use Illuminate\Validation\ValidationException;
use Symfony\Component\HttpFoundation\Response;

class AuthController extends Controller
{
    public function __construct(private readonly AuthRegistrationService $registrationService) {}

    public function registerClient(RegisterClientRequest $request): JsonResponse
    {
        $user = $this->registrationService->registerClient($request->validated());

        return response()->json([
            'message' => 'Cliente registrado correctamente.',
            'data' => new UserResource($user),
        ], Response::HTTP_CREATED);
    }

    public function registerCompany(RegisterCompanyRequest $request): JsonResponse
    {
        $user = $this->registrationService->registerCompany($request->validated());

        return response()->json([
            'message' => 'Empresa registrada correctamente. Queda pendiente de aprobacion.',
            'data' => new UserResource($user),
        ], Response::HTTP_CREATED);
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
            return response()->json([
                'message' => 'La cuenta no esta activa.',
            ], Response::HTTP_FORBIDDEN);
        }

        return response()->json([
            'message' => 'Inicio de sesion correcto.',
            'data' => [
                'user' => new UserResource($user->loadMissing(['role', 'client', 'company'])),
            ],
        ]);
    }

    public function logout(Request $request): JsonResponse
    {
        return response()->json([
            'message' => 'Sesion cerrada correctamente.',
        ]);
    }

    public function forgotPassword(ForgotPasswordRequest $request): JsonResponse
    {
        Password::sendResetLink($request->only('email'));

        return response()->json([
            'message' => 'Si el correo existe, se enviaran instrucciones para restablecer la contrasena.',
        ], Response::HTTP_ACCEPTED);
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

        return response()->json([
            'message' => 'Contrasena actualizada correctamente.',
        ]);
    }
}
