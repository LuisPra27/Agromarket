<?php

namespace App\Filament\Resources\ProduccionResource\Pages;

use App\Filament\Resources\ProduccionResource;
use App\Models\Pedido;
use Filament\Resources\Pages\ListRecords;

class ListProduccion extends ListRecords
{
    protected static string $resource = ProduccionResource::class;

    protected ?string $pollingInterval = '5s';

    protected function getHeaderActions(): array
    {
        return [];
    }

    public function getTitle(): string
    {
        $preparando = Pedido::whereIn('estado', ['pendiente_validacion', 'preparando'])->count();

        return $preparando > 0
            ? "Producción ({$preparando} pendiente" . ($preparando > 1 ? 's' : '') . ")"
            : 'Producción';
    }
}
