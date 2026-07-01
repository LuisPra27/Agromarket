<?php

namespace App\Filament\Resources;

use App\Filament\Resources\LiquidacionResource\Pages;
use App\Models\Usuario;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Notifications\Notification;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class LiquidacionResource extends Resource
{
    protected static ?string $model = Usuario::class;

    protected static ?string $navigationIcon = 'heroicon-o-currency-dollar';

    protected static ?string $navigationLabel = 'Liquidación';

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()
            ->where('estado_repartidor', 'aprobado');
    }

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\TextInput::make('nombre_completo')
                    ->disabled(),
                Forms\Components\TextInput::make('balance')
                    ->numeric()
                    ->prefix('$')
                    ->disabled(),
            ]);
    }

    public static function table(Table $table): Table
{
    return $table
        ->columns([
            Tables\Columns\TextColumn::make('nombre_completo')
                ->label('Repartidor')
                ->searchable()
                ->sortable(),
            Tables\Columns\TextColumn::make('cedula'),
            Tables\Columns\TextColumn::make('facultad')
                ->placeholder('—'),
            Tables\Columns\TextColumn::make('balance')
                ->label('Balance acumulado')
                ->money('USD')
                ->sortable()
                ->badge()
                ->color(fn ($state): string => $state > 0 ? 'success' : 'gray'),
        ])
        ->filters([
            //
        ])
        ->actions([
            Tables\Actions\Action::make('pagar_balance')
                ->label('Resetear / Pagar')
                ->icon('heroicon-o-banknotes')
                ->color('warning')
                ->visible(fn (Usuario $record): bool => $record->balance > 0)
                ->requiresConfirmation()
                ->modalDescription('Esto reseteará el balance a $0.00, confirmando que ya se realizó el pago al repartidor fuera del sistema. ¿Confirmas?')
                ->action(function (Usuario $record) {
                    $montoLiquidado = $record->balance;

                    \App\Models\Liquidacion::create([
                        'usuario_id'   => $record->id,
                        'monto_pagado' => $montoLiquidado,
                    ]);

                    $record->update(['balance' => 0]);

                    Notification::make()
                        ->title('Balance liquidado')
                        ->body("Se reseteó el balance de {$record->nombre_completo} (\${$montoLiquidado}).")
                        ->success()
                        ->send();
                }),
            Tables\Actions\ViewAction::make(),
        ])
        ->bulkActions([
            //
        ]);
}

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListLiquidacions::route('/'),
        ];
    }
}
