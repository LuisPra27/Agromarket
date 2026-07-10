<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        $this->call([
            ConfiguracionSeeder::class,
            UsuarioSeeder::class,
            CategoriaSeeder::class,
            ProductoSeeder::class,
            PedidoSeeder::class,
        ]);
    }
}