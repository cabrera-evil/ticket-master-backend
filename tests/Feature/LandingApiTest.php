<?php

namespace Tests\Feature;

use App\Enums\CompanyStatus;
use App\Enums\OfferStatus;
use App\Enums\UserRole;
use App\Enums\UserStatus;
use App\Models\Category;
use App\Models\Company;
use App\Models\Offer;
use App\Models\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class LandingApiTest extends TestCase
{
    use RefreshDatabase;

    public function test_categories_are_public_and_ordered(): void
    {
        Category::query()->create(['name' => 'Tecnologia', 'slug' => 'tecnologia', 'sort_order' => 2]);
        Category::query()->create(['name' => 'Restaurantes', 'slug' => 'restaurantes', 'sort_order' => 1]);

        $this->getJson('/api/v1/categories')
            ->assertOk()
            ->assertJsonPath('data.0.slug', 'restaurantes')
            ->assertJsonPath('data.1.slug', 'tecnologia');
    }

    public function test_featured_offers_only_returns_active_featured_offers_from_approved_companies(): void
    {
        $category = Category::query()->create([
            'name' => 'Restaurantes',
            'slug' => 'restaurantes',
            'sort_order' => 1,
        ]);

        $approvedCompany = $this->createCompany('approved@example.com', CompanyStatus::Approved);
        $pendingCompany = $this->createCompany('pending@example.com', CompanyStatus::Pending);

        $visibleOffer = $this->createOffer($approvedCompany, $category, [
            'title' => 'Oferta visible',
            'regular_price' => 20,
            'offer_price' => 10,
            'is_featured' => true,
        ]);

        $this->createOffer($approvedCompany, $category, [
            'title' => 'Oferta no destacada',
            'is_featured' => false,
        ]);

        $this->createOffer($pendingCompany, $category, [
            'title' => 'Oferta empresa pendiente',
            'is_featured' => true,
        ]);

        $this->getJson('/api/v1/offers/featured')
            ->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.id', $visibleOffer->id)
            ->assertJsonPath('data.0.merchant', $approvedCompany->name)
            ->assertJsonPath('data.0.discount', '-50%')
            ->assertJsonPath('data.0.category.slug', 'restaurantes');
    }

    public function test_offers_search_can_be_filtered_by_query(): void
    {
        $restaurant = Category::query()->create([
            'name' => 'Restaurantes',
            'slug' => 'restaurantes',
            'sort_order' => 1,
        ]);
        $tech = Category::query()->create([
            'name' => 'Tecnologia',
            'slug' => 'tecnologia',
            'sort_order' => 2,
        ]);
        $company = $this->createCompany('search@example.com', CompanyStatus::Approved);

        $this->createOffer($company, $restaurant, [
            'title' => 'Menu Gourmet Doble',
            'description' => 'Hamburguesa con papas.',
        ]);
        $matchingOffer = $this->createOffer($company, $tech, [
            'title' => 'Mantenimiento Preventivo',
            'description' => 'Limpieza profesional para laptop.',
            'is_featured' => false,
        ]);

        $this->getJson('/api/v1/offers/search?q=laptop')
            ->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.id', $matchingOffer->id);
    }

    public function test_offer_detail_returns_available_offer(): void
    {
        $category = Category::query()->create([
            'name' => 'Salud',
            'slug' => 'salud',
            'sort_order' => 1,
        ]);
        $company = $this->createCompany('detail@example.com', CompanyStatus::Approved);
        $offer = $this->createOffer($company, $category, [
            'title' => 'Masaje Relajante',
            'regular_price' => 60,
            'offer_price' => 45,
        ]);

        $this->getJson("/api/v1/offers/{$offer->id}")
            ->assertOk()
            ->assertJsonPath('data.id', $offer->id)
            ->assertJsonPath('data.merchant_details.phone', '2222-3333')
            ->assertJsonPath('data.category.slug', 'salud');
    }

    private function createCompany(string $email, CompanyStatus $status): Company
    {
        $role = Role::query()->firstOrCreate(['name' => UserRole::Company->value]);

        $user = User::query()->create([
            'name' => 'Empresa Test',
            'username' => str($email)->before('@')->toString(),
            'email' => $email,
            'password' => 'Password123',
            'role_id' => $role->id,
            'status' => UserStatus::Active,
        ]);

        return Company::query()->create([
            'user_id' => $user->id,
            'name' => 'Empresa Test',
            'nit' => fake()->unique()->numerify('####-######-###-#'),
            'address' => 'San Salvador',
            'phone' => '2222-3333',
            'email' => $email,
            'status' => $status,
            'commission_percentage' => 12.50,
            'approved_at' => $status === CompanyStatus::Approved ? now() : null,
        ]);
    }

    private function createOffer(Company $company, Category $category, array $overrides = []): Offer
    {
        return Offer::query()->create(array_merge([
            'company_id' => $company->id,
            'category_id' => $category->id,
            'title' => 'Oferta Test',
            'regular_price' => 25,
            'offer_price' => 20,
            'starts_at' => now()->subDay(),
            'ends_at' => now()->addDay(),
            'redeemable_until' => now()->addWeek(),
            'coupon_limit' => 100,
            'description' => 'Descripcion de prueba',
            'image_url' => 'https://example.com/offer.jpg',
            'is_featured' => true,
            'featured_sort_order' => 1,
            'status' => OfferStatus::Available,
        ], $overrides));
    }
}
