<?php

namespace App\Filament\Resources;

use App\Filament\Resources\ProduccionResource\Pages;
use App\Models\Pedido;
use App\Models\Usuario;
use App\Services\ExpoPushService;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Notifications\Notification;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class ProduccionResource extends Resource
{
    protected static ?string $model = Pedido::class;

    protected static ?string $navigationIcon = 'heroicon-o-cube-transparent';

    protected static ?string $navigationLabel = 'Producción';

    protected static ?string $navigationGroup = 'Operaciones';

    protected static ?int $navigationSort = 2;

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Select::make('estado')
                    ->options([
                        'pendiente_validacion' => 'Pendiente validación',
                        'preparando' => 'Preparando',
                        'listo_para_delivery' => 'Listo para delivery',
                        'en_camino' => 'En camino',
                        'entregado' => 'Entregado',
                        'cancelado' => 'Cancelado',
                        'rechazado' => 'Rechazado',
                    ])
                    ->required(),
                Forms\Components\TextInput::make('total')
                    ->numeric()
                    ->prefix('$'),
                Forms\Components\Select::make('metodo_entrega')
                    ->options([
                        'retiro' => '🏪 Retiro',
                        'delivery' => '🛵 Delivery',
                    ])
                    ->required(),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('numero_orden_cliente')
                    ->label('Orden #')
                    ->sortable()
                    ->searchable()
                    ->toggleable(),
                Tables\Columns\TextColumn::make('cliente.nombre_completo')
                    ->label('Cliente')
                    ->sortable()
                    ->searchable()
                    ->toggleable(),
                Tables\Columns\TextColumn::make('total')
                    ->label('Total')
                    ->money('USD')
                    ->sortable()
                    ->toggleable(),
                Tables\Columns\TextColumn::make('metodo_entrega')
                    ->label('Método')
                    ->badge()
                    ->formatStateUsing(fn (string $state): string => match ($state) {
                        'retiro' => '🏪 Retiro',
                        'delivery' => '🛵 Delivery',
                        default => $state,
                    })
                    ->colors([
                        'info' => 'retiro',
                        'success' => 'delivery',
                    ])
                    ->toggleable(),
                Tables\Columns\TextColumn::make('estado')
                    ->label('Estado')
                    ->badge()
                    ->formatStateUsing(fn (string $state): string => match ($state) {
                        'pendiente_validacion' => '⏳ Pendiente validación',
                        'preparando' => '📦 Preparando',
                        'listo_para_delivery' => '✅ Listo para delivery',
                        'en_camino' => '🛵 En camino',
                        'entregado' => '✅ Entregado',
                        'cancelado' => '❌ Cancelado',
                        'rechazado' => '🚫 Rechazado',
                        default => $state,
                    })
                    ->colors([
                        'warning' => 'pendiente_validacion',
                        'info' => 'preparando',
                        'success' => 'listo_para_delivery',
                        'primary' => 'en_camino',
                        'success' => 'entregado',
                        'danger' => 'cancelado',
                        'danger' => 'rechazado',
                    ])
                    ->sortable()
                    ->toggleable(),
                Tables\Columns\TextColumn::make('created_at')
                    ->label('Creado')
                    ->dateTime('d/m/Y H:i')
                    ->sortable()
                    ->toggleable(),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('metodo_entrega')
                    ->label('Modalidad')
                    ->options([
                        'retiro'   => '🏪 Retiro',
                        'delivery' => '🛵 Delivery',
                    ]),
                Tables\Filters\SelectFilter::make('estado')
                    ->options([
                        'pendiente_validacion' => '⏳ Pendiente validación',
                        'preparando' => '📦 Preparando',
                        'listo_para_delivery' => '✅ Listo para delivery',
                        'en_camino' => '🛵 En camino',
                        'entregado' => '✅ Entregado',
                        'cancelado' => '❌ Cancelado',
                        'rechazado' => '🚫 Rechazado',
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
                        try {
                            broadcast(new \App\Events\PedidoListoParaDelivery($record))->toOthers();
                        } catch (\Throwable $e) {
                            \Illuminate\Support\Facades\Log::warning('No se pudo emitir el evento pedido.listo: '.$e->getMessage());
                        }

                        // Notificar por push solo si es delivery (retiro no necesita repartidor)
                        if ($record->metodo_entrega === 'delivery') {
                            $tokens = Usuario::where('estado_repartidor', 'aprobado')
                                ->whereNotNull('expo_push_token')
                                ->pluck('expo_push_token')
                                ->toArray();

                            ExpoPushService::enviar(
                                $tokens,
                                'Nuevo pedido disponible 🛵',
                                'Nuevo pedido listo para entregar',
                                ['tipo' => 'nuevo_pedido', 'pedido_id' => $record->id]
                            );
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
            ->recordAction('ver_detalle')
            ->bulkActions([])
            ->defaultSort('created_at', 'asc');
    }

    public static function getRelations(): array
    {
        return [];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListProduccion::route('/'),
        ];
    }

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()
            ->whereIn('estado', ['pendiente_validacion', 'preparando', 'listo_para_delivery'])
            ->with(['cliente', 'detalles.producto']);
    }
}