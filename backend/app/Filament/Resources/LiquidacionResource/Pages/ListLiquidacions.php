<?php

namespace App\Filament\Resources\LiquidacionResource\Pages;

use App\Filament\Resources\LiquidacionResource;
use Filament\Resources\Pages\ListRecords;

class ListLiquidacions extends ListRecords
{
    protected static string $resource = LiquidacionResource::class;

    // Refresca solo cada 5s para que el balance en $0 se refleje pronto
    // después de pagar, sin que el admin tenga que recargar la página.
    protected ?string $pollingInterval = '5s';

    protected function getHeaderActions(): array
    {
        // Esta vista es solo para pagar balances de repartidores existentes;
        // "crear un usuario" no tiene sentido desde aquí (y no hay página
        // de creación registrada en el resource, así que el botón llevaba
        // a un enlace roto).
        return [];
    }
}
