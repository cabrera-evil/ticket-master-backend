<?php

namespace App\Services;

use App\Enums\CompanyStatus;
use App\Enums\UserRole;
use App\Enums\UserStatus;
use App\Models\Role;
use App\Models\User;
use Illuminate\Support\Facades\DB;

class AuthRegistrationService
{
    public function registerClient(array $data): User
    {
        return DB::transaction(function () use ($data): User {
            $user = User::query()->create([
                'name' => $data['first_name'].' '.$data['last_name'],
                'username' => $data['username'],
                'email' => $data['email'],
                'password' => $data['password'],
                'role_id' => Role::where('name', 'client')->value('id'),
                'status' => UserStatus::Active,
            ]);

            $user->client()->create([
                'first_name' => $data['first_name'],
                'last_name' => $data['last_name'],
                'dui' => $data['dui'],
                'birth_date' => $data['birth_date'],
            ]);

            return $user->load('client');
        });
    }

    public function registerCompany(array $data): User
    {
        return DB::transaction(function () use ($data): User {
            $user = User::query()->create([
                'name' => $data['name'],
                'username' => $data['username'],
                'email' => $data['email'],
                'password' => $data['password'],
                'role_id' => Role::where('name', 'company')->value('id'),
                'status' => UserStatus::Active,
            ]);

            $user->company()->create([
                'name' => $data['name'],
                'nit' => $data['nit'],
                'address' => $data['address'],
                'phone' => $data['phone'],
                'email' => $data['email'],
                'status' => CompanyStatus::Pending,
            ]);

            return $user->load('company');
        });
    }

    public function createAdmin(array $data): User
    {
        return User::query()->create([
            'name' => $data['name'],
            'username' => $data['username'],
            'email' => $data['email'],
            'password' => $data['password'],
            'role_id' => Role::where('name', 'admin')->value('id'),
            'status' => UserStatus::Active,
        ]);
    }

    public function registerPortfolioUser(array $data): User
    {
        return User::query()->create([
            'name' => $data['firstName'].' '.$data['lastName'],
            'username' => $data['username'],
            'email' => $data['email'],
            'password' => $data['password'],
            'role_id' => Role::where('name', UserRole::Client->value)->value('id'),
            'status' => UserStatus::Active,
        ]);
    }
}
