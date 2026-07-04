<?php

namespace App\Filament\Resources\ProduccionResource\Pages;

use App\Filament\Resources\ProduccionResource;
use Filament\Resources\Pages\ListRecords;

class ListProduccion extends ListRecords
{
    protected static string $resource = ProduccionResource::class;

    protected ?string $pollingInterval = '5s';

    protected function getHeaderActions(): array
    {
        return [];
    }
}
