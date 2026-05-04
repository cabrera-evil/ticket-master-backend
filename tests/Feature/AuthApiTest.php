<?php

namespace Tests\Feature;

use App\Enums\CompanyStatus;
use App\Enums\UserRole;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Password;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class AuthApiTest extends TestCase
{
    use RefreshDatabase;

    public function test_client_can_register_and_login(): void
    {
        $payload = [
            'username' => 'cliente1',
            'email' => 'cliente1@example.com',
            'password' => 'Password123',
            'password_confirmation' => 'Password123',
            'first_name' => 'Ana',
            'last_name' => 'Perez',
            'dui' => '12345678-9',
            'birth_date' => now()->subYears(20)->toDateString(),
        ];

        $this->postJson('/api/v1/register/client', $payload)
            ->assertCreated()
            ->assertJsonPath('data.role', UserRole::Client->value)
            ->assertJsonPath('data.client.dui', '12345678-9');

        $this->postJson('/api/v1/login', [
            'login' => 'cliente1',
            'password' => 'Password123',
        ])
            ->assertOk()
            ->assertJsonPath('data.token_type', 'Bearer')
            ->assertJsonPath('data.user.role', UserRole::Client->value);
    }

    public function test_client_registration_validates_age_and_unique_fields(): void
    {
        User::factory()->create([
            'username' => 'duplicado',
            'email' => 'duplicado@example.com',
        ])->client()->create([
            'first_name' => 'Nombre',
            'last_name' => 'Apellido',
            'dui' => '00000000-0',
            'birth_date' => now()->subYears(25)->toDateString(),
        ]);

        $this->postJson('/api/v1/register/client', [
            'username' => 'duplicado',
            'email' => 'duplicado@example.com',
            'password' => 'Password123',
            'password_confirmation' => 'Password123',
            'first_name' => 'Menor',
            'last_name' => 'Edad',
            'dui' => '00000000-0',
            'birth_date' => now()->subYears(17)->toDateString(),
        ])
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['username', 'email', 'dui', 'birth_date']);
    }

    public function test_company_registers_as_pending(): void
    {
        $this->postJson('/api/v1/register/company', [
            'username' => 'empresa1',
            'email' => 'empresa1@example.com',
            'password' => 'Password123',
            'password_confirmation' => 'Password123',
            'name' => 'Empresa Uno',
            'nit' => '0614-010190-101-0',
            'address' => 'San Salvador',
            'phone' => '2222-3333',
        ])
            ->assertCreated()
            ->assertJsonPath('data.role', UserRole::Company->value)
            ->assertJsonPath('data.company.status', CompanyStatus::Pending->value);
    }

    public function test_login_rejects_invalid_credentials(): void
    {
        User::factory()->create([
            'username' => 'cliente2',
            'email' => 'cliente2@example.com',
            'password' => 'Password123',
        ]);

        $this->postJson('/api/v1/login', [
            'login' => 'cliente2',
            'password' => 'wrong-password',
        ])
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['login']);
    }

    public function test_login_is_rate_limited(): void
    {
        for ($attempt = 0; $attempt < 5; $attempt++) {
            $this->postJson('/api/v1/login', [
                'login' => 'missing@example.com',
                'password' => 'wrong-password',
            ])->assertUnprocessable();
        }

        $this->postJson('/api/v1/login', [
            'login' => 'missing@example.com',
            'password' => 'wrong-password',
        ])->assertTooManyRequests();
    }

    public function test_authenticated_user_can_logout(): void
    {
        $user = User::factory()->create();
        Sanctum::actingAs($user);

        $this->postJson('/api/v1/logout')
            ->assertOk()
            ->assertJsonPath('message', 'Sesion cerrada correctamente.');
    }

    public function test_password_can_be_reset_for_any_user_role(): void
    {
        $user = User::factory()->company()->create([
            'email' => 'empresa-reset@example.com',
            'password' => 'Password123',
        ]);
        $token = Password::createToken($user);

        $this->postJson('/api/v1/password/reset', [
            'email' => 'empresa-reset@example.com',
            'token' => $token,
            'password' => 'NewPassword123',
            'password_confirmation' => 'NewPassword123',
        ])->assertOk();

        $this->postJson('/api/v1/login', [
            'login' => 'empresa-reset@example.com',
            'password' => 'NewPassword123',
        ])->assertOk();
    }

    public function test_forgot_password_returns_generic_response(): void
    {
        $this->postJson('/api/v1/password/forgot', [
            'email' => 'missing@example.com',
        ])
            ->assertAccepted()
            ->assertJsonPath('message', 'Si el correo existe, se enviaran instrucciones para restablecer la contrasena.');
    }
}
