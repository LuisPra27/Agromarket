<?php

namespace App\Filament\Widgets;

use App\Models\Pedido;
use Filament\Widgets\ChartWidget;
use Illuminate\Support\Facades\DB;

class PedidosDiariosChart extends ChartWidget
{
    protected static ?string $heading = 'Pedidos últimos 7 días';

    protected static ?int $sort = 2;

    protected static ?string $maxHeight = '300px';

    protected function getData(): array
    {
        $dias = collect(range(6, 0))->map(function ($i) {
            $fecha = now()->subDays($i)->startOfDay();
            return [
                'fecha' => $fecha->format('d/m'),
                'fecha_completa' => $fecha->toDateString(),
            ];
        });

        // Usar SQL con comillas simples para strings (PostgreSQL)
        $pedidosPorDia = Pedido::select(
            DB::raw('DATE(created_at) as fecha'),
            DB::raw('count(*) as total'),
            DB::raw("sum(case when estado = 'entregado' then 1 else 0 end) as entregados"),
            DB::raw("sum(case when estado in ('cancelado','rechazado') then 1 else 0 end) as cancelados")
        )
            ->where('created_at', '>=', now()->subDays(6)->startOfDay())
            ->groupBy(DB::raw('DATE(created_at)'))
            ->pluck('total', 'fecha')
            ->toArray();

        $entregadosPorDia = Pedido::select(
            DB::raw('DATE(created_at) as fecha'),
            DB::raw('count(*) as total')
        )
            ->where('estado', 'entregado')
            ->where('created_at', '>=', now()->subDays(6)->startOfDay())
            ->groupBy(DB::raw('DATE(created_at)'))
            ->pluck('total', 'fecha')
            ->toArray();

        $canceladosPorDia = Pedido::select(
            DB::raw('DATE(created_at) as fecha'),
            DB::raw('count(*) as total')
        )
            ->whereIn('estado', ['cancelado', 'rechazado'])
            ->where('created_at', '>=', now()->subDays(6)->startOfDay())
            ->groupBy(DB::raw('DATE(created_at)'))
            ->pluck('total', 'fecha')
            ->toArray();

        return [
            'datasets' => [
                [
                    'label' => 'Total pedidos',
                    'data' => $dias->map(fn ($d) => $pedidosPorDia[$d['fecha_completa']] ?? 0),
                    'borderColor' => '#3b82f6',
                    'backgroundColor' => 'rgba(59, 130, 246, 0.1)',
                    'fill' => true,
                    'tension' => 0.4,
                ],
                [
                    'label' => 'Entregados',
                    'data' => $dias->map(fn ($d) => $entregadosPorDia[$d['fecha_completa']] ?? 0),
                    'borderColor' => '#22c55e',
                    'backgroundColor' => 'rgba(34, 197, 94, 0.1)',
                    'fill' => true,
                    'tension' => 0.4,
                ],
                [
                    'label' => 'Cancelados/Rechazados',
                    'data' => $dias->map(fn ($d) => $canceladosPorDia[$d['fecha_completa']] ?? 0),
                    'borderColor' => '#ef4444',
                    'backgroundColor' => 'rgba(239, 68, 68, 0.1)',
                    'fill' => true,
                    'tension' => 0.4,
                ],
            ],
            'labels' => $dias->pluck('fecha')->toArray(),
        ];
    }

    protected function getType(): string
    {
        return 'line';
    }

    protected function getOptions(): array
    {
        return [
            'plugins' => [
                'legend' => [
                    'display' => true,
                    'position' => 'bottom',
                ],
            ],
            'scales' => [
                'y' => [
                    'beginAtZero' => true,
                    'ticks' => [
                        'stepSize' => 1,
                    ],
                ],
            ],
            'interaction' => [
                'intersect' => false,
                'mode' => 'index',
            ],
        ];
    }
}