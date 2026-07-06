<?php

namespace Database\Seeders;

use App\Models\Configuracion;
use App\Models\User;
use App\Models\Usuario;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        // ── Usuario admin del panel Filament ──────────────────────────
        User::firstOrCreate(
            ['email' => 'adminagromarket@gmail.com'],
            [
                'name'     => 'Administrador',
                'password' => bcrypt('admin123'),
            ]
        );

        // ── Usuario admin en tabla usuarios (para pruebas de API) ─────
        Usuario::firstOrCreate(
            ['correo' => 'adminagromarket@gmail.com'],
            [
                'cedula'          => '0000000000',
                'nombre_completo' => 'Administrador',
                'clave'           => 'admin123',
                'rol'             => 'administrador',
                'estado_repartidor' => 'no_postulado',
                'balance'         => 0,
            ]
        );

        // ── Configuraciones del sistema ───────────────────────────────
        $configuraciones = [
            [
                'clave'       => 'incentivo_repartidor',
                'valor'       => '0.25',
                'descripcion' => 'Monto en USD que se acredita al repartidor por cada entrega completada.',
            ],
            [
                'clave'       => 'cuenta_banco',
                'valor'       => 'Banco Pichincha',
                'descripcion' => 'Nombre del banco para transferencias.',
            ],
            [
                'clave'       => 'cuenta_numero',
                'valor'       => '2207382104',
                'descripcion' => 'Número de cuenta para transferencias.',
            ],
            [
                'clave'       => 'cuenta_tipo',
                'valor'       => 'Ahorros',
                'descripcion' => 'Tipo de cuenta (Ahorros / Corriente).',
            ],
            [
                'clave'       => 'cuenta_titular',
                'valor'       => 'Facultad FCVT ULEAM',
                'descripcion' => 'Nombre del titular de la cuenta.',
            ],
            [
                'clave'       => 'cuenta_cedula',
                'valor'       => '1300000000',
                'descripcion' => 'Cédula o RUC del titular.',
            ],
        ];

        foreach ($configuraciones as $config) {
            Configuracion::firstOrCreate(
                ['clave' => $config['clave']],
                [
                    'valor'       => $config['valor'],
                    'descripcion' => $config['descripcion'],
                ]
            );
        }
    }
}
