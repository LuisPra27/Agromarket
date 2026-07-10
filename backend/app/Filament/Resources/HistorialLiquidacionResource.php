<?php

namespace App\Filament\Resources;

use App\Filament\Resources\HistorialLiquidacionResource\Pages;
use App\Models\Liquidacion;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class HistorialLiquidacionResource extends Resource
{
    protected static ?string $model = Liquidacion::class;

    protected static ?string $navigationGroup = 'Historial';
    protected static ?int $navigationSort = 2;
    protected static ?string $navigationIcon = 'heroicon-o-clipboard-document-list';
    protected static ?string $navigationLabel = 'Historial de pagos a repartidores';
    protected static ?string $modelLabel = 'pago';
    protected static ?string $pluralModelLabel = 'historial de pagos';

    // Solo lectura: los pagos ya liquidados no se editan ni se borran desde
    // aquí, es un registro histórico de lo que ya se le pagó a cada repartidor.
    public static function canCreate(): bool
    {
        return false;
    }

    public static function canEdit($record): bool
    {
        return false;
    }

    public static function canDelete($record): bool
    {
        return false;
    }

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()
            ->with('usuario')
            ->latest('created_at');
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('usuario.nombre_completo')
                    ->label('Repartidor')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('usuario.cedula')
                    ->label('Cédula'),
                Tables\Columns\TextColumn::make('monto_pagado')
                    ->label('Monto pagado')
                    ->money('USD')
                    ->badge()
                    ->color('success')
                    ->sortable(),
                Tables\Columns\TextColumn::make('observaciones')
                    ->limit(40)
                    ->placeholder('—'),
                Tables\Columns\TextColumn::make('created_at')
                    ->label('Fecha de pago')
                    ->dateTime('d/m/Y H:i')
                    ->sortable(),
            ])
            ->defaultSort('created_at', 'desc')
            ->filters([
                Tables\Filters\Filter::make('created_at')
                    ->form([
                        \Filament\Forms\Components\DatePicker::make('desde'),
                        \Filament\Forms\Components\DatePicker::make('hasta'),
                    ])
                    ->query(function (Builder $query, array $data): Builder {
                        return $query
                            ->when($data['desde'], fn ($q, $desde) => $q->whereDate('created_at', '>=', $desde))
                            ->when($data['hasta'], fn ($q, $hasta) => $q->whereDate('created_at', '<=', $hasta));
                    }),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListHistorialLiquidacions::route('/'),
        ];
    }
}
