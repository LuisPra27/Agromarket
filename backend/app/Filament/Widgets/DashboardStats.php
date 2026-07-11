<?php

namespace App\Filament\Widgets;

use App\Models\Pedido;
use App\Models\Usuario;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

class DashboardStats extends BaseWidget
{
    protected static ?int $sort = 1;

    protected function getStats(): array
    {
        $hoy = now()->startOfDay();
        $manana = now()->addDay()->startOfDay();

        $pendientes = Pedido::where('estado', 'pendiente_validacion')->count();
        $preparando = Pedido::where('estado', 'preparando')->count();
        $listos = Pedido::where('estado', 'listo_para_delivery')->count();
        $enCamino = Pedido::where('estado', 'en_camino')->count();
        $entregadosHoy = Pedido::where('estado', 'entregado')
            ->whereBetween('updated_at', [$hoy, $manana])
            ->count();

        $repartidoresActivos = Usuario::where('estado_repartidor', 'aprobado')->count();
        $repartidoresDisponibles = Usuario::where('estado_repartidor', 'aprobado')
            ->whereDoesntHave('pedidosComoRepartidor', function ($q) {
                $q->where('estado', 'en_camino');
            })
            ->count();

        return [
            Stat::make('⏳ Pendientes validación', $pendientes)
                ->description('Requieren revisión de comprobante')
                ->descriptionIcon('heroicon-m-clock')
                ->color('warning')
                ->chart([7, 3, 5, 2, 8, 4, 6])
                ->url(route('filament.admin.resources.caja-validacion.index')),

            Stat::make('📦 Armando pedidos', $preparando)
                ->description('Productos siendo reservados/separados')
                ->descriptionIcon('heroicon-m-cube-transparent')
                ->color('info')
                ->chart([2, 5, 3, 7, 4, 6, 3])
                ->url(route('filament.admin.resources.produccion.index')),

            Stat::make('📦 Listos para delivery', $listos)
                ->description('Esperando repartidor')
                ->descriptionIcon('heroicon-m-truck')
                ->color('primary')
                ->chart([1, 3, 2, 4, 2, 5, 3])
                ->url(route('filament.admin.resources.delivery.index')),

            Stat::make('🛵 En camino', $enCamino)
                ->description('Repartidor en ruta')
                ->descriptionIcon('heroicon-m-map-pin')
                ->color('success')
                ->chart([0, 2, 1, 3, 2, 4, 2])
                ->url(route('filament.admin.resources.delivery.index')),

            Stat::make('✅ Entregados hoy', $entregadosHoy)
                ->description('Completados en las últimas 24h')
                ->descriptionIcon('heroicon-m-check-circle')
                ->color('success')
                ->chart([5, 8, 6, 12, 9, 15, 10])
                ->url(route('filament.admin.resources.historial-pedidos.index')),

            Stat::make('👥 Repartidores', "{$repartidoresDisponibles}/{$repartidoresActivos} disponibles")
                ->description($repartidoresActivos . ' aprobados en total')
                ->descriptionIcon('heroicon-m-users')
                ->color($repartidoresDisponibles > 0 ? 'success' : 'danger')
                ->chart([3, 4, 3, 5, 4, 4, 5])
                ->url(route('filament.admin.resources.usuarios.index', ['tableFilters[estado_repartidor]' => 'aprobado'])),
        ];
    }
}
