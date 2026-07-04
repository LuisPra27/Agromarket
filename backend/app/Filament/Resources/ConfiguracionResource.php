<?php

namespace App\Filament\Resources;

use App\Filament\Resources\ConfiguracionResource\Pages;
use App\Models\Configuracion;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

class ConfiguracionResource extends Resource
{
    protected static ?string $navigationGroup = 'Configuración';
    protected static ?int $navigationSort = 1;

    protected static ?string $model = Configuracion::class;

    protected static ?string $navigationIcon = 'heroicon-o-cog-6-tooth';

    protected static ?string $navigationLabel = 'Configuración';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\TextInput::make('clave')
                    ->required()
                    ->maxLength(50)
                    ->disabled()
                    ->helperText('La clave no se puede modificar una vez creada.'),
                Forms\Components\TextInput::make('valor')
                    ->required()
                    ->maxLength(255)
                    ->helperText('Ejemplo: 0.25 para $0.25 por entrega.'),
                Forms\Components\Textarea::make('descripcion')
                    ->rows(2)
                    ->columnSpanFull(),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('clave')
                    ->searchable(),
                Tables\Columns\TextColumn::make('valor')
                    ->badge()
                    ->color('success'),
                Tables\Columns\TextColumn::make('descripcion')
                    ->limit(60)
                    ->placeholder('—'),
                Tables\Columns\TextColumn::make('updated_at')
                    ->label('Última modificación')
                    ->dateTime('d/m/Y H:i')
                    ->sortable(),
            ])
            ->filters([
                //
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
            ])
            ->bulkActions([
                //
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListConfiguracions::route('/'),
            'edit' => Pages\EditConfiguracion::route('/{record}/edit'),
        ];
    }
}
