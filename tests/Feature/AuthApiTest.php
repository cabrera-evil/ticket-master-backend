<?php

namespace Tests\Feature;

use App\Models\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Password;
use Tests\TestCase;

class AuthApiTest extends TestCase
{
    use RefreshDatabase;

    public function test_user_can_register_and_login_with_portfolio_contract(): void
    {
        $payload = [
            'username' => 'cliente1',
            'firstName' => 'Ana',
            'lastName' => 'Perez',
            'email' => 'cliente1@example.com',
            'password' => 'Password123!',
        ];

        $this->postJson('/api/v1/auth/register', $payload)
            ->assertCreated()
            ->assertJsonStructure(['data' => ['jwt', 'refreshToken', 'user']])
            ->assertJsonPath('data.user.username', 'cliente1');

        $this->postJson('/api/v1/auth/login', [
            'identifier' => 'cliente1',
            'password' => 'Password123!',
        ])
            ->assertOk()
            ->assertJsonStructure(['data' => ['jwt', 'refreshToken', 'user']])
            ->assertJsonPath('data.user.username', 'cliente1');
    }

    public function test_register_validates_required_fields_and_uniqueness(): void
    {
        $roleId = Role::query()->where('name', 'client')->value('id');
        User::query()->create([
            'name' => 'Duplicado Usuario',
            'username' => 'duplicado',
            'email' => 'duplicado@example.com',
            'password' => 'Password123!',
            'role_id' => $roleId,
            'status' => 'active',
        ]);

        $this->postJson('/api/v1/auth/register', [
            'username' => 'duplicado',
            'firstName' => 'Test',
            'lastName' => 'User',
            'email' => 'duplicado@example.com',
            'password' => 'Password123!',
        ])
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['username', 'email']);
    }

    public function test_login_rejects_invalid_credentials(): void
    {
        User::factory()->create([
            'username' => 'cliente2',
            'email' => 'cliente2@example.com',
            'password' => 'Password123',
        ]);

        $this->postJson('/api/v1/auth/login', [
            'identifier' => 'cliente2',
            'password' => 'wrong-password',
        ])
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['identifier']);
    }

    public function test_login_is_rate_limited(): void
    {
        for ($attempt = 0; $attempt < 5; $attempt++) {
            $this->postJson('/api/v1/auth/login', [
                'identifier' => 'missing@example.com',
                'password' => 'wrong-password',
            ])->assertUnprocessable();
        }

        $this->postJson('/api/v1/auth/login', [
            'identifier' => 'missing@example.com',
            'password' => 'wrong-password',
        ])->assertTooManyRequests();
    }

    public function test_authenticated_user_can_get_profile_and_logout(): void
    {
        $user = User::factory()->create();
        $this->actingAs($user, 'jwt');

        $this->postJson('/api/v1/auth/profile')
            ->assertOk()
            ->assertJsonPath('data.id', $user->id);

        $this->postJson('/api/v1/auth/logout')
            ->assertOk()
            ->assertJsonPath('data.message', 'Logged out successfully');
    }

    public function test_password_can_be_reset_with_token_only_payload(): void
    {
        $user = User::factory()->create([
            'email' => 'empresa-reset@example.com',
            'password' => 'Password123!',
        ]);
        $token = Password::createToken($user);

        $this->postJson('/api/v1/auth/forgot-password/verify-token', [
            'token' => $token,
        ])->assertOk();

        $this->postJson('/api/v1/auth/forgot-password/reset-password', [
            'token' => $token,
            'password' => 'NewPassword123!',
        ])->assertOk();

        $this->postJson('/api/v1/auth/login', [
            'identifier' => 'empresa-reset@example.com',
            'password' => 'NewPassword123!',
        ])->assertOk();
    }

    public function test_forgot_password_returns_generic_response(): void
    {
        $this->postJson('/api/v1/auth/forgot-password/request-token', [
            'email' => 'missing@example.com',
        ])
            ->assertOk()
            ->assertJsonPath('data.message', 'If a user with that email exists, a reset token has been sent.');
    }

    public function test_healthcheck_returns_ok_status(): void
    {
        $this->getJson('/api/health')
            ->assertOk()
            ->assertExactJson(['status' => 'ok']);
    }
}
