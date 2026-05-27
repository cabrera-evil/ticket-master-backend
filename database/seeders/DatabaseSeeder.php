<?php

namespace Database\Seeders;

use App\Enums\UserRole;
use App\Enums\UserStatus;
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
    }
}
