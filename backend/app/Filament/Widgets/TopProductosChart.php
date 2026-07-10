<?php

namespace App\Filament\Widgets;

use App\Models\DetallePedido;
use App\Models\Pedido;
use Filament\Widgets\ChartWidget;
use Illuminate\Support\Facades\DB;

class TopProductosChart extends ChartWidget
{
    protected static ?string $heading = 'Top 5 productos más vendidos';
    protected static ?int $sort = 3;
    protected static ?string $maxHeight = '300px';

    protected function getData(): array
    {
        $topProductos = DetallePedido::select(
            'productos.nombre',
            DB::raw('sum(detalle_pedido.cantidad) as total_vendido'),
            DB::raw('sum(detalle_pedido.subtotal) as total_ingresos')
        )
            ->join('pedidos', 'detalle_pedido.pedido_id', '=', 'pedidos.id')
            ->join('productos', 'detalle_pedido.producto_id', '=', 'productos.id')
            ->where('pedidos.estado', 'entregado')
            ->where('pedidos.created_at', '>=', now()->subDays(30)->startOfDay())
            ->groupBy('productos.id', 'productos.nombre')
            ->orderByDesc('total_vendido')
            ->limit(5)
            ->get();

        $colores = [
            'rgba(59, 130, 246, 0.8)',
            'rgba(34, 197, 94, 0.8)',
            'rgba(249, 115, 22, 0.8)',
            'rgba(168, 85, 247, 0.8)',
            'rgba(236, 72, 153, 0.8)',
        ];

        return [
            'datasets' => [
                [
                    'label' => 'Unidades vendidas (30 días)',
                    'data' => $topProductos->pluck('total_vendido')->toArray(),
                    'backgroundColor' => array_slice($colores, 0, $topProductos->count()),
                    'borderWidth' => 0,
                ],
            ],
            'labels' => $topProductos->pluck('nombre')->toArray(),
        ];
    }

    protected function getType(): string
    {
        return 'bar';
    }

    protected function getOptions(): array
    {
        return [
            'indexAxis' => 'y',
            'plugins' => [
                'legend' => [
                    'display' => false,
                ],
            ],
            'scales' => [
                'x' => [
                    'beginAtZero' => true,
                    'ticks' => [
                        'stepSize' => 1,
                    ],
                ],
            ],
        ];
    }
}