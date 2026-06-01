<?php

namespace Database\Seeders;

use App\Enums\CompanyStatus;
use App\Enums\OfferStatus;
use App\Enums\UserRole;
use App\Enums\UserStatus;
use App\Models\Category;
use App\Models\Company;
use App\Models\Offer;
use App\Models\Role;
use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    public function run(): void
    {
        foreach ([UserRole::Admin->value, UserRole::Company->value, UserRole::User->value] as $name) {
            Role::firstOrCreate(['name' => $name]);
        }

        User::query()->updateOrCreate([
            'email' => env('ADMIN_EMAIL', 'admin@lacuponera.test'),
        ], [
            'name' => env('ADMIN_NAME', 'Administrador La Cuponera SV'),
            'username' => env('ADMIN_USERNAME', 'admin'),
            'password' => env('ADMIN_PASSWORD', 'Password123'),
            'role_id' => Role::where('name', UserRole::Admin->value)->value('id'),
            'status' => UserStatus::Active,
        ]);

        $this->seedLandingCategories();
        $this->seedLandingOffers();
    }

    private function seedLandingCategories(): void
    {
        foreach ([
            ['name' => 'Restaurantes', 'slug' => 'restaurantes'],
            ['name' => 'Salud', 'slug' => 'salud'],
            ['name' => 'Tecnologia', 'slug' => 'tecnologia'],
            ['name' => 'Belleza', 'slug' => 'belleza'],
            ['name' => 'Viajes', 'slug' => 'viajes'],
            ['name' => 'Mas', 'slug' => 'mas'],
        ] as $index => $category) {
            Category::query()->updateOrCreate(
                ['slug' => $category['slug']],
                ['name' => $category['name'], 'sort_order' => $index + 1]
            );
        }
    }

    private function seedLandingOffers(): void
    {
        $companyRoleId = Role::where('name', UserRole::Company->value)->value('id');

        foreach ($this->landingOffers() as $index => $landingOffer) {
            $user = User::query()->updateOrCreate([
                'email' => $landingOffer['company_email'],
            ], [
                'name' => $landingOffer['company_name'],
                'username' => $landingOffer['company_username'],
                'password' => 'Password123',
                'role_id' => $companyRoleId,
                'status' => UserStatus::Active,
            ]);

            $company = Company::query()->updateOrCreate([
                'email' => $landingOffer['company_email'],
            ], [
                'user_id' => $user->id,
                'name' => $landingOffer['company_name'],
                'nit' => $landingOffer['nit'],
                'address' => $landingOffer['address'],
                'phone' => $landingOffer['phone'],
                'status' => CompanyStatus::Approved,
                'commission_percentage' => 12.50,
                'approved_at' => now(),
                'rejected_at' => null,
            ]);

            Offer::query()->updateOrCreate([
                'title' => $landingOffer['title'],
            ], [
                'company_id' => $company->id,
                'category_id' => Category::where('slug', $landingOffer['category_slug'])->value('id'),
                'regular_price' => $landingOffer['regular_price'],
                'offer_price' => $landingOffer['offer_price'],
                'starts_at' => now()->subDay(),
                'ends_at' => now()->addMonths(3),
                'redeemable_until' => now()->addMonths(4),
                'coupon_limit' => 250,
                'description' => $landingOffer['description'],
                'image_url' => $landingOffer['image_url'],
                'is_featured' => true,
                'featured_sort_order' => $index + 1,
                'status' => OfferStatus::Available,
            ]);
        }
    }

    private function landingOffers(): array
    {
        return [
            [
                'company_name' => 'The Burger Lab',
                'company_username' => 'burgerlab',
                'company_email' => 'burgerlab@lacuponera.test',
                'nit' => '0614-010190-101-1',
                'address' => 'San Salvador',
                'phone' => '2222-1001',
                'category_slug' => 'restaurantes',
                'title' => 'Menu Gourmet Doble con Papas Trufadas',
                'description' => 'Menu gourmet doble con papas trufadas para disfrutar en restaurante.',
                'regular_price' => 24.99,
                'offer_price' => 14.99,
                'image_url' => 'https://images.unsplash.com/photo-1568901346375-23c9450c58cd?auto=format&fit=crop&w=1200&q=80',
            ],
            [
                'company_name' => 'Wellness Center SV',
                'company_username' => 'wellnesssv',
                'company_email' => 'wellness@lacuponera.test',
                'nit' => '0614-010190-101-2',
                'address' => 'Colonia Escalon, San Salvador',
                'phone' => '2222-1002',
                'category_slug' => 'salud',
                'title' => 'Masaje Relajante de Piedras Volcanicas',
                'description' => 'Sesion de masaje relajante con piedras volcanicas en ambiente profesional.',
                'regular_price' => 60.00,
                'offer_price' => 45.00,
                'image_url' => 'https://images.unsplash.com/photo-1540555700478-4be289fbecef?auto=format&fit=crop&w=1200&q=80',
            ],
            [
                'company_name' => 'Tech Outlet',
                'company_username' => 'techoutlet',
                'company_email' => 'techoutlet@lacuponera.test',
                'nit' => '0614-010190-101-3',
                'address' => 'Santa Tecla',
                'phone' => '2222-1003',
                'category_slug' => 'tecnologia',
                'title' => 'Mantenimiento Preventivo y Limpieza Pro',
                'description' => 'Mantenimiento preventivo y limpieza profesional para laptop o equipo de escritorio.',
                'regular_price' => 35.00,
                'offer_price' => 29.75,
                'image_url' => 'https://images.unsplash.com/photo-1516321318423-f06f85e504b3?auto=format&fit=crop&w=1200&q=80',
            ],
        ];
    }
}
