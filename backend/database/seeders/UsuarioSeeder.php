<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Usuario;
use Illuminate\Support\Facades\Hash;

class UsuarioSeeder extends Seeder
{
    public function run(): void
    {
        // Admin
        Usuario::firstOrCreate(
            ['correo' => 'admin@live.uleam.edu.ec'],
            [
                'cedula' => '1234567890',
                'nombre_completo' => 'Administrador Sistema',
                'clave' => Hash::make('password123'),
                'rol' => 'administrador',
                'estado_repartidor' => 'no_postulado',
                'balance' => 0,
            ]
        );

        // Repartidores aprobados
        $repartidores = [
            ['cedula' => '0957226681', 'nombre' => 'Juan Pérez Repartidor', 'facultad' => 'Ciencias Administrativas'],
            ['cedula' => '0957226682', 'nombre' => 'María García Repartidor', 'facultad' => 'Ciencias Económicas'],
            ['cedula' => '0957226683', 'nombre' => 'Carlos López Repartidor', 'facultad' => 'Ingeniería'],
            ['cedula' => '0957226684', 'nombre' => 'Ana Martínez Repartidor', 'facultad' => 'Ciencias de la Salud'],
            ['cedula' => '0957226685', 'nombre' => 'Pedro Rodríguez Repartidor', 'facultad' => 'Ciencias Agrarias'],
        ];

        foreach ($repartidores as $r) {
            Usuario::firstOrCreate(
                ['correo' => "e{$r['cedula']}@live.uleam.edu.ec"],
                [
                    'cedula' => $r['cedula'],
                    'nombre_completo' => $r['nombre'],
                    'clave' => Hash::make('password123'),
                    'rol' => 'cliente',
                    'estado_repartidor' => 'aprobado',
                    'facultad' => $r['facultad'],
                    'balance' => rand(10, 100),
                ]
            );
        }

        // Clientes
        $clientes = [
            ['cedula' => '0957226681', 'nombre' => 'Luis Manuel Prado Gongora'],
            ['cedula' => '0957226682', 'nombre' => 'Ana María Sánchez'],
            ['cedula' => '0957226683', 'nombre' => 'Carlos Eduardo Vargas'],
            ['cedula' => '0957226684', 'nombre' => 'María Fernanda Torres'],
            ['cedula' => '0957226685', 'nombre' => 'Juan Carlos Mendoza'],
            ['cedula' => '0957226686', 'nombre' => 'Sofía Alejandra Herrera'],
            ['cedula' => '0957226687', 'nombre' => 'Diego Armando Ramírez'],
            ['cedula' => '0957226688', 'nombre' => 'Valeria Nicole Castro'],
            ['cedula' => '0957226689', 'nombre' => 'Andrés Felipe Gutiérrez'],
            ['cedula' => '0957226690', 'nombre' => 'Camila Andrea Rojas'],
        ];

        foreach ($clientes as $c) {
            Usuario::firstOrCreate(
                ['correo' => "e{$c['cedula']}@live.uleam.edu.ec"],
                [
                    'cedula' => $c['cedula'],
                    'nombre_completo' => $c['nombre'],
                    'clave' => Hash::make('password123'),
                    'rol' => 'cliente',
                    'estado_repartidor' => 'no_postulado',
                    'balance' => 0,
                ]
            );
        }

        // Repartidores pendientes/rechazados
        $pendientes = [
            ['cedula' => '0957226691', 'nombre' => 'Roberto Pendiente', 'facultad' => 'Ciencias Administrativas', 'estado' => 'pendiente'],
            ['cedula' => '0957226692', 'nombre' => 'Laura Rechazada', 'facultad' => 'Ciencias Económicas', 'estado' => 'rechazado'],
        ];

        foreach ($pendientes as $p) {
            Usuario::firstOrCreate(
                ['correo' => "e{$p['cedula']}@live.uleam.edu.ec"],
                [
                    'cedula' => $p['cedula'],
                    'nombre_completo' => $p['nombre'],
                    'clave' => Hash::make('password123'),
                    'rol' => 'cliente',
                    'estado_repartidor' => $p['estado'],
                    'facultad' => $p['facultad'],
                    'balance' => 0,
                ]
            );
        }
    }
}