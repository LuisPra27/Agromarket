<?php

namespace App\Filament\Resources;

use App\Filament\Resources\HistorialPedidoResource\Pages;
use App\Models\Pedido;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class HistorialPedidoResource extends Resource
{
    protected static ?string $model = Pedido::class;

    protected static ?string $navigationIcon = 'heroicon-o-clipboard-document-list';

    protected static ?string $navigationLabel = 'Historial de Pedidos';

    protected static ?string $slug = 'historial-pedidos';

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()
            ->whereNotIn('estado', ['pendiente_validacion'])
            ->with(['cliente', 'repartidor', 'detalles.producto'])
            ->orderBy('updated_at', 'desc');
    }

    public static function form(Form $form): Form
    {
        return $form->schema([]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('id')
                    ->label('# Pedido')
                    ->sortable(),
                Tables\Columns\TextColumn::make('cliente.nombre_completo')
                    ->label('Cliente')
                    ->searchable(),
                Tables\Columns\TextColumn::make('metodo_entrega')
                    ->label('Modalidad')
                    ->badge()
                    ->color(fn (string $state): string => $state === 'delivery' ? 'warning' : 'info'),
                Tables\Columns\TextColumn::make('estado')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'preparando'          => 'info',
                        'listo_para_delivery' => 'warning',
                        'en_camino'           => 'warning',
                        'entregado'           => 'success',
                        'rechazado'           => 'danger',
                        default               => 'gray',
                    }),
                Tables\Columns\TextColumn::make('total')
                    ->money('USD')
                    ->sortable(),
                Tables\Columns\TextColumn::make('repartidor.nombre_completo')
                    ->label('Repartidor')
                    ->placeholder('Sin asignar'),
                Tables\Columns\TextColumn::make('updated_at')
                    ->label('Última actualización')
                    ->dateTime('d/m/Y H:i')
                    ->sortable(),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('estado')
                    ->options([
                        'preparando'          => 'Preparando',
                        'listo_para_delivery' => 'Listo para entrega',
                        'en_camino'           => 'En camino',
                        'entregado'           => 'Entregado',
                        'rechazado'           => 'Rechazado',
                    ]),
                Tables\Filters\SelectFilter::make('metodo_entrega')
                    ->label('Modalidad')
                    ->options([
                        'retiro'   => 'Retiro',
                        'delivery' => 'Delivery',
                    ]),
            ])
            ->actions([
                Tables\Actions\ViewAction::make(),
            ])
            ->bulkActions([])
            ->defaultSort('updated_at', 'desc');
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListHistorialPedidos::route('/'),
        ];
    }

    public static function canCreate(): bool
    {
        return false;
    }
}
