<?php

namespace App\Filament\Resources\HistorialPedidoResource\Pages;

use App\Filament\Resources\HistorialPedidoResource;
use Filament\Resources\Pages\ListRecords;

class ListHistorialPedidos extends ListRecords
{
    protected static string $resource = HistorialPedidoResource::class;

    protected ?string $pollingInterval = '15s';

    protected function getHeaderActions(): array
    {
        return [];
    }
}
