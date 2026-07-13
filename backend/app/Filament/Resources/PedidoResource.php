<?php

namespace App\Filament\Resources;

use App\Filament\Resources\PedidoResource\Pages;
use App\Models\Pedido;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Notifications\Notification;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use App\Services\ExpoPushService;
use App\Events\PedidoAprobado;
use App\Events\PedidoRechazado;

class PedidoResource extends Resource
{
    protected static ?string $navigationGroup = 'Operaciones del día';
    protected static ?int $navigationSort = 1;

    protected static ?string $model = Pedido::class;

    protected static ?string $slug = 'caja-validacion';

    protected static ?string $navigationIcon = 'heroicon-o-banknotes';

    protected static ?string $navigationLabel = 'Caja / Validación';

    protected static ?string $pluralModelLabel = 'Pedidos en caja';

    public static function getNavigationLabel(): string
    {
        return 'Caja / Validación';
    }

    public static function getPluralModelLabel(): string
    {
        return 'Pedidos en caja';
    }

    public static function getNavigationBadge(): ?string
    {
        $pendientes = static::getModel()::query()
            ->where('estado', 'pendiente_validacion')
            ->count();

        return $pendientes > 0 ? (string) $pendientes : null;
    }

    public static function getNavigationBadgeColor(): ?string
    {
        return 'warning';
    }

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()
            ->where('estado', 'pendiente_validacion');
    }

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Select::make('cliente_id')
                    ->relationship('cliente', 'nombre_completo')
                    ->disabled(),
                Forms\Components\TextInput::make('total')
                    ->prefix('$')
                    ->disabled(),
                Forms\Components\Select::make('metodo_entrega')
                    ->options([
                        'retiro' => 'Retiro',
                        'delivery' => 'Delivery',
                    ])
                    ->disabled(),
                Forms\Components\Textarea::make('punto_encuentro')
                    ->disabled()
                    ->columnSpanFull(),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->poll('10s')
            ->modifyQueryUsing(fn (Builder $query) => $query->with(['cliente', 'detalles.producto']))
            ->columns([
                Tables\Columns\TextColumn::make('id')
                    ->label('# Pedido')
                    ->sortable()
                    ->toggleable(),
                Tables\Columns\TextColumn::make('cliente.nombre_completo')
                    ->label('Cliente')
                    ->searchable()
                    ->toggleable(),
                Tables\Columns\TextColumn::make('productos_solicitados')
                    ->label('Productos solicitados')
                    ->getStateUsing(fn (Pedido $record): array => $record->detalles
                        ->map(function ($detalle): string {
                            $cantidad = (int) $detalle->cantidad;
                            $nombre = $detalle->producto?->nombre ?? 'Producto no disponible';

                            return "{$cantidad}x {$nombre}";
                        })
                        ->all())
                    ->listWithLineBreaks()
                    ->bulleted()
                    ->searchable(false)
                    ->toggleable(),
                Tables\Columns\TextColumn::make('total')
                    ->money('USD')
                    ->sortable()
                    ->toggleable(),
                Tables\Columns\TextColumn::make('metodo_entrega')
                    ->badge()
                    ->toggleable(),
                Tables\Columns\ImageColumn::make('comprobante_pago_url')
                    ->label('Comprobante')
                    ->disk('public')
                    ->size(80)
                    ->toggleable(),
                Tables\Columns\TextColumn::make('created_at')
                    ->label('Fecha')
                    ->dateTime('d/m/Y H:i')
                    ->sortable()
                    ->toggleable(),
            ])
            ->filters([
                //
            ])
            ->actions([
                Tables\Actions\Action::make('aprobar')
                    ->label('Aprobar')
                    ->icon('heroicon-o-check-circle')
                    ->color('success')
                    ->requiresConfirmation()
                    ->modalDescription('Esta acción generará el código QR y reducirá el stock. ¿Confirmas?')
                    ->visible(fn (Pedido $record): bool => $record->estado === 'pendiente_validacion')
                    ->action(function (Pedido $record) {
                        \App\Services\PedidoAprobacionService::aprobar($record);

                        Notification::make()->title('Pedido aprobado')->success()->send();
                    }),

                Tables\Actions\Action::make('rechazar')
                    ->label('Rechazar')
                    ->icon('heroicon-o-x-circle')
                    ->color('danger')
                    ->requiresConfirmation()
                    ->visible(fn (Pedido $record): bool => $record->estado === 'pendiente_validacion')
                    ->action(function (Pedido $record) {
                        $record->update(['estado' => 'rechazado']);

                        // Disparar evento para badges admin en tiempo real
                        event(new PedidoRechazado($record->fresh()));

                        Notification::make()->title('Pedido rechazado')->warning()->send();
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
            ->bulkActions([
                //
            ])
            ->defaultSort('created_at', 'desc');
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListPedidos::route('/'),
            'edit' => Pages\EditPedido::route('/{record}/edit'),
        ];
    }
}
