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

class CartApiTest extends TestCase
{
    use RefreshDatabase;

    public function test_cart_requires_authentication(): void
    {
        $this->getJson('/api/v1/cart')->assertUnauthorized();
    }

    public function test_user_can_add_update_and_delete_cart_items(): void
    {
        $user = $this->createUser();
        $offer = $this->createAvailableOffer();

        $this->actingAs($user, 'jwt')
            ->postJson('/api/v1/cart/items', [
                'offer_id' => $offer->id,
                'quantity' => 2,
            ])
            ->assertCreated()
            ->assertJsonPath('data.offer_id', $offer->id)
            ->assertJsonPath('data.quantity', 2);

        $this->actingAs($user, 'jwt')
            ->postJson('/api/v1/cart/items', [
                'offer_id' => $offer->id,
            ])
            ->assertCreated()
            ->assertJsonPath('data.quantity', 3);

        $itemId = $user->cartItems()->firstOrFail()->id;

        $this->actingAs($user, 'jwt')
            ->getJson('/api/v1/cart')
            ->assertOk()
            ->assertJsonPath('data.summary.items_count', 3)
            ->assertJsonPath('data.summary.subtotal', 45)
            ->assertJsonPath('data.items.0.offer.id', $offer->id);

        $this->actingAs($user, 'jwt')
            ->patchJson("/api/v1/cart/items/{$itemId}", ['quantity' => 1])
            ->assertOk()
            ->assertJsonPath('data.quantity', 1);

        $this->actingAs($user, 'jwt')
            ->deleteJson("/api/v1/cart/items/{$itemId}")
            ->assertOk();

        $this->assertDatabaseMissing('cart_items', ['id' => $itemId]);
    }

    public function test_user_cannot_delete_another_users_cart_item(): void
    {
        $owner = $this->createUser('owner@example.com');
        $other = $this->createUser('other@example.com');
        $offer = $this->createAvailableOffer();

        $item = $owner->cartItems()->create([
            'offer_id' => $offer->id,
            'quantity' => 1,
        ]);

        $this->actingAs($other, 'jwt')
            ->deleteJson("/api/v1/cart/items/{$item->id}")
            ->assertNotFound();
    }

    private function createUser(string $email = 'client@example.com'): User
    {
        $role = Role::query()->firstOrCreate(['name' => UserRole::User->value]);

        return User::query()->create([
            'name' => 'Cliente Test',
            'username' => str($email)->before('@')->toString(),
            'email' => $email,
            'password' => 'Password123',
            'role_id' => $role->id,
            'status' => UserStatus::Active,
        ]);
    }

    private function createCompany(): Company
    {
        $role = Role::query()->firstOrCreate(['name' => UserRole::Company->value]);
        $user = User::query()->create([
            'name' => 'Spa Test',
            'username' => 'spa-test',
            'email' => 'spa@example.com',
            'password' => 'Password123',
            'role_id' => $role->id,
            'status' => UserStatus::Active,
        ]);

        return Company::query()->create([
            'user_id' => $user->id,
            'name' => 'Wellness Center SV',
            'nit' => '0614-111111-101-1',
            'address' => 'Colonia Escalon, San Salvador',
            'phone' => '2222-3333',
            'email' => 'spa@example.com',
            'status' => CompanyStatus::Approved,
            'commission_percentage' => 12.50,
            'approved_at' => now(),
        ]);
    }

    private function createAvailableOffer(): Offer
    {
        $category = Category::query()->create([
            'name' => 'Salud',
            'slug' => 'salud',
            'sort_order' => 1,
        ]);

        return Offer::query()->create([
            'company_id' => $this->createCompany()->id,
            'category_id' => $category->id,
            'title' => 'Masaje Relajante',
            'regular_price' => 25,
            'offer_price' => 15,
            'starts_at' => now()->subDay(),
            'ends_at' => now()->addDay(),
            'redeemable_until' => now()->addWeek(),
            'coupon_limit' => 100,
            'description' => 'Masaje relajante en spa.',
            'image_url' => 'https://example.com/spa.jpg',
            'is_featured' => true,
            'featured_sort_order' => 1,
            'status' => OfferStatus::Available,
        ]);
    }
}
