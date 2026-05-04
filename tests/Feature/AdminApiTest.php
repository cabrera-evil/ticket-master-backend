<?php

namespace Tests\Feature;

use App\Enums\CompanyStatus;
use App\Enums\UserRole;
use App\Models\Company;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class AdminApiTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_can_create_another_admin(): void
    {
        Sanctum::actingAs(User::factory()->admin()->create());

        $this->postJson('/api/v1/admin/users', [
            'name' => 'Admin Dos',
            'username' => 'admin2',
            'email' => 'admin2@example.com',
            'password' => 'Password123',
            'password_confirmation' => 'Password123',
        ])
            ->assertCreated()
            ->assertJsonPath('data.role', UserRole::Admin->value);
    }

    public function test_non_admin_cannot_create_admins(): void
    {
        Sanctum::actingAs(User::factory()->create());

        $this->postJson('/api/v1/admin/users', [
            'name' => 'Admin Dos',
            'username' => 'admin2',
            'email' => 'admin2@example.com',
            'password' => 'Password123',
            'password_confirmation' => 'Password123',
        ])->assertForbidden();
    }

    public function test_admin_can_list_pending_companies(): void
    {
        Sanctum::actingAs(User::factory()->admin()->create());
        $this->createCompany('pendiente@example.com');

        $this->getJson('/api/v1/admin/companies/pending')
            ->assertOk()
            ->assertJsonPath('data.0.email', 'pendiente@example.com');
    }

    public function test_admin_can_approve_pending_company(): void
    {
        $admin = User::factory()->admin()->create();
        Sanctum::actingAs($admin);
        $company = $this->createCompany('aprobar@example.com');

        $this->putJson("/api/v1/admin/companies/{$company->id}/approve", [
            'commission_percentage' => 12.5,
        ])
            ->assertOk()
            ->assertJsonPath('data.status', CompanyStatus::Approved->value)
            ->assertJsonPath('data.commission_percentage', '12.50');

        $this->assertDatabaseHas('company_approvals', [
            'company_id' => $company->id,
            'approved_by' => $admin->id,
            'action' => 'approved',
        ]);
    }

    public function test_admin_can_reject_pending_company(): void
    {
        $admin = User::factory()->admin()->create();
        Sanctum::actingAs($admin);
        $company = $this->createCompany('rechazar@example.com');

        $this->putJson("/api/v1/admin/companies/{$company->id}/reject", [
            'reason' => 'Datos incompletos.',
        ])
            ->assertOk()
            ->assertJsonPath('data.status', CompanyStatus::Rejected->value);

        $this->assertDatabaseHas('company_approvals', [
            'company_id' => $company->id,
            'approved_by' => $admin->id,
            'action' => 'rejected',
            'reason' => 'Datos incompletos.',
        ]);
    }

    public function test_admin_cannot_approve_non_pending_company(): void
    {
        Sanctum::actingAs(User::factory()->admin()->create());
        $company = $this->createCompany('aprobada@example.com', CompanyStatus::Approved);

        $this->putJson("/api/v1/admin/companies/{$company->id}/approve", [
            'commission_percentage' => 10,
        ])
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['company']);
    }

    private function createCompany(string $email, CompanyStatus $status = CompanyStatus::Pending): Company
    {
        $user = User::factory()->company()->create([
            'name' => 'Empresa Test',
            'email' => $email,
        ]);

        return $user->company()->create([
            'name' => 'Empresa Test',
            'nit' => fake()->unique()->numerify('####-######-###-#'),
            'address' => 'San Salvador',
            'phone' => '2222-3333',
            'email' => $email,
            'status' => $status,
        ]);
    }
}
