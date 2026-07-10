<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Categoria;

class CategoriaSeeder extends Seeder
{
    public function run(): void
    {
        $categorias = [
            ['nombre' => 'Bebidas', 'descripcion' => 'Jugos, gaseosas, aguas, energizantes'],
            ['nombre' => 'Snacks Salados', 'descripcion' => 'Papas, nachos, maní, chifles'],
            ['nombre' => 'Dulces y Chocolates', 'descripcion' => 'Caramelos, chocolates, galletas'],
            ['nombre' => 'Comida Rápida', 'descripcion' => 'Empanadas, sándwiches, hot dogs'],
            ['nombre' => 'Lácteos y Fríos', 'descripcion' => 'Yogures, helados, quesos'],
            ['nombre' => 'Desayuno', 'descripcion' => 'Café, té, sandwiches de desayuno'],
        ];

        foreach ($categorias as $cat) {
            Categoria::firstOrCreate(['nombre' => $cat['nombre']], $cat);
        }
    }
}