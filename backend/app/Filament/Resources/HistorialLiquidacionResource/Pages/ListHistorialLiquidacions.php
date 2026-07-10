<?php

namespace App\Filament\Resources\HistorialLiquidacionResource\Pages;

use App\Filament\Resources\HistorialLiquidacionResource;
use Filament\Resources\Pages\ListRecords;

class ListHistorialLiquidacions extends ListRecords
{
    protected static string $resource = HistorialLiquidacionResource::class;

    protected function getHeaderActions(): array
    {
        return [];
    }
}
