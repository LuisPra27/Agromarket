<?php

namespace App\Filament\Resources;

use App\Filament\Resources\HistorialPedidoResource\Pages;
use App\Models\Pedido;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Notifications\Notification;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\DB;

class HistorialPedidoResource extends Resource
{
    protected static ?string $navigationGroup = 'Historial';
    protected static ?int $navigationSort = 1;

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
                        'cancelado'           => 'danger',
                        default               => 'gray',
                    }),
                Tables\Columns\TextColumn::make('motivo_cancelacion')
                    ->label('Motivo de cancelación')
                    ->limit(40)
                    ->toggleable(isToggledHiddenByDefault: true)
                    ->placeholder('—'),
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
                        'cancelado'           => 'Cancelado',
                    ]),
                Tables\Filters\SelectFilter::make('metodo_entrega')
                    ->label('Modalidad')
                    ->options([
                        'retiro'   => 'Retiro',
                        'delivery' => 'Delivery',
                    ]),
            ])
            ->actions([
                Tables\Actions\Action::make('confirmar_entrega')
                    ->label('Confirmar entrega')
                    ->icon('heroicon-o-check-circle')
                    ->color('success')
                    ->requiresConfirmation()
                    ->modalHeading('Confirmar entrega')
                    ->modalDescription('¿Está seguro de confirmar que este pedido fue entregado al cliente? Esta acción no se puede deshacer.')
                    ->modalSubmitActionLabel('Sí, confirmar entrega')
                    ->visible(fn (Pedido $record): bool => in_array($record->estado, ['listo_para_delivery', 'en_camino']))
                    ->action(function (Pedido $record) {
                        DB::transaction(function () use ($record) {
                            $record->update(['estado' => 'entregado']);

                            // Si había un repartidor asignado, se le acredita el
                            // incentivo igual que si hubiera escaneado el QR: esta
                            // acción es un respaldo manual para cuando el escaneo
                            // falla o el admin necesita cerrar el pedido a mano.
                            if ($record->repartidor_id) {
                                $incentivo = (float) \App\Models\Configuracion::get('incentivo_repartidor', 0.25);
                                $record->repartidor?->increment('balance', $incentivo);
                            }
                        });
                        Notification::make()->title('Entrega confirmada')->success()->send();
                    }),

                Tables\Actions\Action::make('cancelar')
                    ->label('Cancelar')
                    ->icon('heroicon-o-x-circle')
                    ->color('danger')
                    ->requiresConfirmation()
                    ->modalHeading('Cancelar pedido')
                    ->modalDescription('¿Está seguro de cancelar este pedido? Se devolverá el stock reservado y el cliente verá el motivo que indiques a continuación.')
                    ->modalSubmitActionLabel('Sí, cancelar pedido')
                    ->visible(fn (Pedido $record): bool => in_array($record->estado, ['preparando', 'listo_para_delivery', 'en_camino']))
                    ->form([
                        Forms\Components\Select::make('motivo_generico')
                            ->label('Motivo')
                            ->options([
                                'cliente_no_disponible'    => 'El cliente no se presentó / no respondió',
                                'repartidor_no_disponible' => 'No hay repartidor disponible',
                                'producto_no_disponible'   => 'Producto ya no disponible',
                                'error_pedido'             => 'Error en el pedido',
                                'otro'                     => 'Otro motivo',
                            ])
                            ->required(),
                        Forms\Components\Textarea::make('motivo_detalle')
                            ->label('Detalle adicional (opcional)')
                            ->placeholder('Agrega más contexto si lo necesitas, el cliente lo verá también.')
                            ->columnSpanFull(),
                    ])
                    ->action(function (Pedido $record, array $data) {
                        DB::transaction(function () use ($record, $data) {
                            // Devolver el stock que se descontó al aprobar el pedido.
                            foreach ($record->detalles as $detalle) {
                                $detalle->producto?->increment('stock', $detalle->cantidad);
                            }

                            $etiquetas = [
                                'cliente_no_disponible'    => 'El cliente no se presentó / no respondió',
                                'repartidor_no_disponible' => 'No hay repartidor disponible',
                                'producto_no_disponible'   => 'Producto ya no disponible',
                                'error_pedido'             => 'Error en el pedido',
                                'otro'                     => 'Otro motivo',
                            ];

                            $motivo = $etiquetas[$data['motivo_generico']] ?? $data['motivo_generico'];
                            if (!empty($data['motivo_detalle'])) {
                                $motivo .= ': ' . $data['motivo_detalle'];
                            }

                            $record->update([
                                'estado'             => 'cancelado',
                                'motivo_cancelacion' => $motivo,
                            ]);
                        });
                        Notification::make()->title('Pedido cancelado')->warning()->send();
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
