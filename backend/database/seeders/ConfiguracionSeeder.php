<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Configuracion;

class ConfiguracionSeeder extends Seeder
{
    public function run(): void
    {
        $configs = [
            ['clave' => 'incentivo_repartidor', 'valor' => '0.25', 'descripcion' => 'Porcentaje de incentivo para repartidor por entrega'],
            ['clave' => 'cuenta_banco', 'valor' => 'Banco Pichincha', 'descripcion' => 'Banco para pagos'],
            ['clave' => 'cuenta_numero', 'valor' => '1234567890', 'descripcion' => 'Número de cuenta'],
            ['clave' => 'cuenta_tipo', 'valor' => 'corriente', 'descripcion' => 'Tipo de cuenta'],
            ['clave' => 'cuenta_titular', 'valor' => 'Agromarket ULEAM', 'descripcion' => 'Titular de la cuenta'],
            ['clave' => 'cuenta_cedula', 'valor' => '1234567890', 'descripcion' => 'Cédula del titular'],
        ];

        foreach ($configs as $config) {
            \App\Models\Configuracion::firstOrCreate(
                ['clave' => $config['clave']],
                $config
            );
        }
    }
}