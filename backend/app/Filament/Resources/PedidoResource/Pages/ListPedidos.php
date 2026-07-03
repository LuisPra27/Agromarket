<?php

namespace App\Filament\Resources\PedidoResource\Pages;

use App\Filament\Resources\PedidoResource;
use App\Models\Pedido;
use Filament\Resources\Pages\ListRecords;

class ListPedidos extends ListRecords
{
    protected static string $resource = PedidoResource::class;

    protected function getTablePollingInterval(): ?string
    {
        return '10s';
    }

    protected function getPendingCount(): int
    {
        return Pedido::where('estado', 'pendiente_validacion')->count();
    }

    protected function getHeaderActions(): array
    {
        return [];
    }

    public function getTitle(): string
    {
        $pendientes = $this->getPendingCount();

        return $pendientes > 0
            ? "Caja / Validación ({$pendientes} pendiente" . ($pendientes > 1 ? 's' : '') . ")"
            : 'Caja / Validación';
    }
}
