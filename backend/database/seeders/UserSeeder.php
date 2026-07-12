<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

// Esta es la tabla que realmente usa el panel de Filament (guard "web"),
// NO la tabla "usuarios" (esa es para el guard "usuario" / API móvil).
// Ver config/auth.php: guard "web" -> provider "users" -> App\Models\User.
class UserSeeder extends Seeder
{
    public function run(): void
    {
        User::firstOrCreate(
            ['email' => 'adminagromarket@gmail.com'],
            [
                'name' => 'Administrador',
                'password' => Hash::make('admin123'),
            ]
        );
    }
}
