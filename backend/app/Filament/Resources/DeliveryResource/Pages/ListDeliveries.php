<?php

namespace App\Filament\Resources\DeliveryResource\Pages;

use App\Filament\Resources\DeliveryResource;
use App\Models\Pedido;
use Filament\Resources\Pages\ListRecords;

class ListDeliveries extends ListRecords
{
    protected static string $resource = DeliveryResource::class;

    protected ?string $pollingInterval = '5s';

    protected function getHeaderActions(): array
    {
        return [];
    }

    public function getTitle(): string
    {
        $entregando = Pedido::where('metodo_entrega', 'delivery')
            ->whereIn('estado', ['listo_para_delivery', 'en_camino'])
            ->count();

        return $entregando > 0
            ? "Delivery ({$entregando} en proceso" . ($entregando > 1 ? 's' : '') . ")"
            : 'Delivery';
    }
}
