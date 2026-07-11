<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Categoria;
use App\Models\Producto;

class ProductoSeeder extends Seeder
{
    public function run(): void
    {
        $categorias = Categoria::all()->keyBy('nombre');

        $productos = [
            // Bebidas
            ['nombre' => 'Jugo de Naranja 500ml', 'precio' => 1.50, 'stock' => 50, 'categoria' => 'Bebidas'],
            ['nombre' => 'Jugo de Maracuyá 500ml', 'precio' => 1.50, 'stock' => 40, 'categoria' => 'Bebidas'],
            ['nombre' => 'Gaseosa Cola 600ml', 'precio' => 1.25, 'stock' => 60, 'categoria' => 'Bebidas'],
            ['nombre' => 'Gaseosa Naranja 600ml', 'precio' => 1.25, 'stock' => 45, 'categoria' => 'Bebidas'],
            ['nombre' => 'Agua Mineral 600ml', 'precio' => 0.75, 'stock' => 80, 'categoria' => 'Bebidas'],
            ['nombre' => 'Energizante 250ml', 'precio' => 2.00, 'stock' => 30, 'categoria' => 'Bebidas'],
            ['nombre' => 'Té Frío Limón 500ml', 'precio' => 1.25, 'stock' => 35, 'categoria' => 'Bebidas'],
            ['nombre' => 'Café Frío 300ml', 'precio' => 1.50, 'stock' => 25, 'categoria' => 'Bebidas'],

            // Snacks Salados
            ['nombre' => 'Papas Clásicas 150g', 'precio' => 1.25, 'stock' => 50, 'categoria' => 'Snacks Salados'],
            ['nombre' => 'Papas con Queso 150g', 'precio' => 1.50, 'stock' => 40, 'categoria' => 'Snacks Salados'],
            ['nombre' => 'Chifles Plátano 100g', 'precio' => 1.00, 'stock' => 60, 'categoria' => 'Snacks Salados'],
            ['nombre' => 'Maní Salado 100g', 'precio' => 0.75, 'stock' => 55, 'categoria' => 'Snacks Salados'],
            ['nombre' => 'Nachos con Queso 120g', 'precio' => 1.75, 'stock' => 30, 'categoria' => 'Snacks Salados'],
            ['nombre' => 'Palitos de Queso 100g', 'precio' => 1.25, 'stock' => 40, 'categoria' => 'Snacks Salados'],

            // Dulces y Chocolates
            ['nombre' => 'Chocolate Bar 100g', 'precio' => 1.50, 'stock' => 45, 'categoria' => 'Dulces y Chocolates'],
            ['nombre' => 'Galletas de Chocolate 200g', 'precio' => 1.75, 'stock' => 35, 'categoria' => 'Dulces y Chocolates'],
            ['nombre' => 'Caramelos Menta 50g', 'precio' => 0.50, 'stock' => 80, 'categoria' => 'Dulces y Chocolates'],
            ['nombre' => 'Gomitas Frutas 100g', 'precio' => 1.00, 'stock' => 50, 'categoria' => 'Dulces y Chocolates'],
            ['nombre' => 'Barra Granola 40g', 'precio' => 0.75, 'stock' => 60, 'categoria' => 'Dulces y Chocolates'],

            // Comida Rápida
            ['nombre' => 'Empanada de Queso', 'precio' => 0.75, 'stock' => 50, 'categoria' => 'Comida Rápida'],
            ['nombre' => 'Empanada de Carne', 'precio' => 0.75, 'stock' => 50, 'categoria' => 'Comida Rápida'],
            ['nombre' => 'Empanada de Pollo', 'precio' => 0.75, 'stock' => 40, 'categoria' => 'Comida Rápida'],
            ['nombre' => 'Sándwich de Pollo', 'precio' => 2.50, 'stock' => 20, 'categoria' => 'Comida Rápida'],
            ['nombre' => 'Sándwich de Jamón y Queso', 'precio' => 2.25, 'stock' => 25, 'categoria' => 'Comida Rápida'],
            ['nombre' => 'Hot Dog', 'precio' => 2.00, 'stock' => 30, 'categoria' => 'Comida Rápida'],
            ['nombre' => 'Hamburguesa Simple', 'precio' => 3.00, 'stock' => 15, 'categoria' => 'Comida Rápida'],

            // Lácteos y Fríos
            ['nombre' => 'Yogur Natural 180g', 'precio' => 0.75, 'stock' => 40, 'categoria' => 'Lácteos y Fríos'],
            ['nombre' => 'Yogur con Frutas 180g', 'precio' => 0.85, 'stock' => 35, 'categoria' => 'Lácteos y Fríos'],
            ['nombre' => 'Helado Paleta Vainilla', 'precio' => 0.75, 'stock' => 45, 'categoria' => 'Lácteos y Fríos'],
            ['nombre' => 'Helado Paleta Chocolate', 'precio' => 0.75, 'stock' => 40, 'categoria' => 'Lácteos y Fríos'],
            ['nombre' => 'Queso Fresco 200g', 'precio' => 1.50, 'stock' => 20, 'categoria' => 'Lácteos y Fríos'],

            // Desayuno
            ['nombre' => 'Café Americano', 'precio' => 1.00, 'stock' => 50, 'categoria' => 'Desayuno'],
            ['nombre' => 'Café con Leche', 'precio' => 1.25, 'stock' => 40, 'categoria' => 'Desayuno'],
            ['nombre' => 'Té de Hierbas', 'precio' => 0.75, 'stock' => 30, 'categoria' => 'Desayuno'],
            ['nombre' => 'Sándwich de Huevo', 'precio' => 1.75, 'stock' => 20, 'categoria' => 'Desayuno'],
            ['nombre' => 'Tostada con Queso', 'precio' => 1.50, 'stock' => 25, 'categoria' => 'Desayuno'],
        ];

        foreach ($productos as $p) {
            Producto::firstOrCreate(
                ['nombre' => $p['nombre']],
                [
                    'categoria_id' => $categorias[$p['categoria']]->id,
                    'precio' => $p['precio'],
                    'stock' => $p['stock'],
                    'imagen_url' => null,
                ]
            );
        }
    }
}