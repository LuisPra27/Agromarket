<?php

namespace App\Filament\Resources;

use App\Filament\Resources\DeliveryResource\Pages;
use App\Models\Pedido;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class DeliveryResource extends Resource
{
    protected static ?string $model = Pedido::class;

    protected static ?string $navigationIcon = 'heroicon-o-truck';

    protected static ?string $navigationLabel = 'Delivery';

    protected static ?string $slug = 'delivery';

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
            ->whereIn('estado', ['listo_para_delivery', 'en_camino', 'entregado'])
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
            ->poll('10s')
            ->columns([
                Tables\Columns\TextColumn::make('id')
                    ->label('# Pedido')
                    ->sortable(),
                Tables\Columns\TextColumn::make('estado')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'listo_para_delivery' => 'warning',
                        'en_camino'           => 'info',
                        'entregado'           => 'success',
                        default               => 'gray',
                    }),
                Tables\Columns\TextColumn::make('cliente.nombre_completo')
                    ->label('Cliente')
                    ->searchable(),
                Tables\Columns\TextColumn::make('punto_encuentro')
                    ->label('📍 Destino')
                    ->placeholder('No especificado')
                    ->limit(50),
                Tables\Columns\TextColumn::make('repartidor.nombre_completo')
                    ->label('🛵 Repartidor')
                    ->placeholder('Esperando repartidor...')
                    ->color(fn ($state) => $state ? 'success' : 'gray'),
                Tables\Columns\TextColumn::make('repartidor.facultad')
                    ->label('Facultad repartidor')
                    ->placeholder('—'),
                Tables\Columns\TextColumn::make('total')
                    ->money('USD'),
                Tables\Columns\TextColumn::make('updated_at')
                    ->label('Última actualización')
                    ->dateTime('H:i — d/m/Y')
                    ->sortable(),
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
                Tables\Actions\ViewAction::make(),
            ])
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
