<?php

namespace App\Filament\Resources;

use App\Filament\Resources\ProduccionResource\Pages;
use App\Models\Pedido;
use Filament\Forms\Form;
use Filament\Notifications\Notification;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use App\Events\PedidoListoParaDelivery;

class ProduccionResource extends Resource
{
    protected static ?string $navigationGroup = 'Operaciones del día';
    protected static ?int $navigationSort = 2;

    protected static ?string $model = Pedido::class;

    protected static ?string $navigationIcon = 'heroicon-o-fire';

    protected static ?string $navigationLabel = 'Producción';

    protected static ?string $slug = 'produccion';

    public static function getNavigationBadge(): ?string
    {
        $count = static::getModel()::where('estado', 'preparando')->count();
        return $count > 0 ? (string) $count : null;
    }

    public static function getNavigationBadgeColor(): ?string
    {
        return 'info';
    }

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()
            ->where('estado', 'preparando')
            ->with(['cliente', 'detalles.producto'])
            ->orderBy('created_at');
    }

    public static function form(Form $form): Form
    {
        return $form->schema([]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->poll('15s')
            ->columns([
                Tables\Columns\TextColumn::make('id')
                    ->label('# Pedido')
                    ->sortable(),
                Tables\Columns\TextColumn::make('metodo_entrega')
                    ->label('Modalidad')
                    ->badge()
                    ->color(fn (string $state): string => $state === 'delivery' ? 'warning' : 'info'),
                Tables\Columns\TextColumn::make('cliente.nombre_completo')
                    ->label('Cliente')
                    ->searchable(),
                Tables\Columns\TextColumn::make('productos_solicitados')
                    ->label('Productos')
                    ->getStateUsing(fn (Pedido $record): array => $record->detalles
                        ->map(fn ($d): string => "{$d->cantidad}x {$d->producto?->nombre}")
                        ->all())
                    ->listWithLineBreaks()
                    ->bulleted(),
                Tables\Columns\TextColumn::make('punto_encuentro')
                    ->label('Punto de entrega')
                    ->placeholder('Retiro en local')
                    ->limit(40),
                Tables\Columns\TextColumn::make('total')
                    ->money('USD'),
                Tables\Columns\TextColumn::make('created_at')
                    ->label('Hora del pedido')
                    ->dateTime('H:i — d/m/Y')
                    ->sortable(),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('metodo_entrega')
                    ->label('Modalidad')
                    ->options([
                        'retiro'   => '🏪 Retiro',
                        'delivery' => '🛵 Delivery',
                    ]),
            ])
            ->actions([
                Tables\Actions\Action::make('listo')
                    ->label('Listo para entrega')
                    ->icon('heroicon-o-check-badge')
                    ->color('success')
                    ->requiresConfirmation()
                    ->modalDescription('¿El pedido está físicamente preparado y listo para ser retirado o entregado?')

                    ->action(function (Pedido $record) {
                        $record->update(['estado' => 'listo_para_delivery']);

                        // Disparar evento WebSocket a todos los repartidores
                        // Si el servidor de WebSockets no está disponible, no debe romper el panel
                        try {
                            broadcast(new \App\Events\PedidoListoParaDelivery($record))->toOthers();
                        } catch (\Throwable $e) {
                            \Illuminate\Support\Facades\Log::warning('No se pudo emitir el evento pedido.listo: '.$e->getMessage());
                        }

                        Notification::make()
                            ->title('Pedido listo para entrega')
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
            ->bulkActions([])
            ->defaultSort('created_at', 'asc');
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListProduccion::route('/'),
        ];
    }

    public static function canCreate(): bool
    {
        return false;
    }
}
