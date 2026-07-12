<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        $this->call([
            UserSeeder::class,
            ConfiguracionSeeder::class,
            //UsuarioSeeder::class,
            //CategoriaSeeder::class,
            //ProductoSeeder::class,
            //PedidoSeeder::class,
        ]);

        // Descomentar la siguiente línea SOLO para datos masivos de prueba (tarda ~2-3 min)
        // $this->call(MassiveUleamSeeder::class);
    }
}
