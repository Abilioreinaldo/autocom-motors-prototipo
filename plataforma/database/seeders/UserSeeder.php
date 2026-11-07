<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;

class UserSeeder extends Seeder
{
    public function run(): void
    {
        User::updateOrCreate(
            ['email' => 'admin@exemplo.com'],
            ['name' => 'Administrador', 'password' => 'password', 'role' => 'admin', 'is_active' => true]
        );

        User::updateOrCreate(
            ['email' => 'vendedor@exemplo.com'],
            ['name' => 'Vendedor Demo', 'password' => 'password', 'role' => 'vendedor', 'is_active' => true, 'phone' => '(15) 99999-0002']
        );
    }
}
