<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;

class UserSeeder extends Seeder
{
    public function run(): void
    {
        User::firstOrCreate(
            ['email' => 'admin@hutang.test'],
            [
                'name' => 'Administrator',
                'password' => 'password',
                'role' => User::ROLE_ADMIN,
            ]
        );

        User::firstOrCreate(
            ['email' => 'kasir@hutang.test'],
            [
                'name' => 'Kasir Utama',
                'password' => 'password',
                'role' => User::ROLE_KASIR,
            ]
        );
    }
}
