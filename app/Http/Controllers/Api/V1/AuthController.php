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

        return $this->apiResponse('Cliente registrado correctamente.', new UserResource($user), Response::HTTP_CREATED);
    }

    public function registerCompany(RegisterCompanyRequest $request): JsonResponse
    {
        $user = $this->registrationService->registerCompany($request->validated());

        return $this->apiResponse('Empresa registrada correctamente. Queda pendiente de aprobacion.', new UserResource($user), Response::HTTP_CREATED);
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

        return $this->apiResponse('Inicio de sesion correcto.', [
            'user' => new UserResource($user->loadMissing(['role', 'client', 'company'])),
        ]);
    }

    public function logout(Request $request): JsonResponse
    {
        return $this->apiResponse('Sesion cerrada correctamente.');
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
