<?php

namespace App\Filament\Resources;

use App\Filament\Resources\DeliveryResource\Pages;
use App\Models\Pedido;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Filament\Forms;
use Filament\Notifications\Notification;

class DeliveryResource extends Resource
{
    protected static ?string $navigationGroup = 'Operaciones del día';
    protected static ?int $navigationSort = 3;

    protected static ?string $model = Pedido::class;

    protected static ?string $navigationIcon = 'heroicon-o-truck';

    protected static ?string $navigationLabel = 'Delivery';

    protected static ?string $pluralModelLabel = 'Pedidos en delivery';

    protected static ?string $slug = 'delivery';

    public static function getNavigationLabel(): string
    {
        return 'Delivery';
    }

    public static function getPluralModelLabel(): string
    {
        return 'Pedidos en delivery';
    }

    public static function getNavigationBadge(): ?string
    {
        $count = static::getModel()::where('metodo_entrega', 'delivery')
            ->whereIn('estado', ['listo_para_delivery', 'en_camino'])
            ->count();
        return $count > 0 ? (string) $count : null;
    }

    public static function getNavigationBadgeColor(): ?string
    {
        return 'warning';
    }

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()
            ->where('metodo_entrega', 'delivery')
            ->whereIn('estado', ['listo_para_delivery', 'en_camino'])
            ->with(['cliente', 'repartidor', 'detalles.producto']);
    }

    public static function form(Form $form): Form
    {
        return $form->schema([]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->poll('10s')
            ->columns([
                Tables\Columns\TextColumn::make('id')
                    ->label('# Pedido')
                    ->sortable()
                    ->toggleable(),
                Tables\Columns\TextColumn::make('estado')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'listo_para_delivery' => 'warning',
                        'en_camino'           => 'info',
                        'entregado'           => 'success',
                        default               => 'gray',
                    })
                    ->toggleable(),
                Tables\Columns\TextColumn::make('cliente.nombre_completo')
                    ->label('Cliente')
                    ->searchable()
                    ->toggleable(),
                Tables\Columns\TextColumn::make('punto_encuentro')
                    ->label('📍 Destino')
                    ->placeholder('No especificado')
                    ->limit(50)
                    ->toggleable(),
                Tables\Columns\TextColumn::make('repartidor.nombre_completo')
                    ->label('🛵 Repartidor')
                    ->placeholder('Esperando repartidor...')
                    ->color(fn ($state) => $state ? 'success' : 'gray')
                    ->toggleable(),
                Tables\Columns\TextColumn::make('total')
                    ->money('USD')
                    ->toggleable(),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('estado')
                    ->options([
                        'listo_para_delivery' => '📦 Esperando repartidor',
                        'en_camino'           => '🛵 En camino',
                        'entregado'           => '✅ Entregado',
                    ]),
            ])
            ->actions([
                Tables\Actions\Action::make('asignar_repartidor')
                    ->label('Asignar repartidor')
                    ->icon('heroicon-o-user-plus')
                    ->color('warning')
                    ->visible(fn (Pedido $record): bool => is_null($record->repartidor_id))
                    ->form([
                        Forms\Components\Select::make('repartidor_id')
                            ->label('Repartidor')
                            ->options(function () {
                                return \App\Models\Usuario::where('estado_repartidor', 'aprobado')
                                    ->pluck('nombre_completo', 'id');
                            })
                            ->required()
                            ->searchable(),
                    ])
                    ->action(function (Pedido $record, array $data) {
                        $record->update([
                            'repartidor_id' => $data['repartidor_id'],
                            'estado'        => 'en_camino',
                        ]);

                        Notification::make()
                            ->title('Repartidor asignado')
                            ->success()
                            ->send();
                    }),
                Tables\Actions\Action::make('ver_detalle')
                    ->label('Ver detalle')
                    ->icon('heroicon-o-eye')
                    ->color('gray')
                    ->modalContent(fn (Pedido $record) => view(
                        'filament.modals.detalle-pedido',
                        ['pedido' => $record->load(['cliente', 'repartidor', 'detalles.producto'])]
                    ))
                    ->modalSubmitAction(false)
                    ->modalCancelActionLabel('Cerrar'),
            ])
            ->recordAction('ver_detalle')
            ->bulkActions([])
            ->defaultSort('updated_at', 'desc');
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListDeliveries::route('/'),
        ];
    }

    public static function canCreate(): bool
    {
        return false;
    }
}
