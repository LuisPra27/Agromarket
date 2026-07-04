<?php

namespace App\Filament\Resources;

use App\Filament\Resources\ProductoResource\Pages;
use App\Filament\Resources\ProductoResource\RelationManagers;
use App\Models\Producto;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class ProductoResource extends Resource
{
    protected static ?string $navigationGroup = 'Inventario';
    protected static ?int $navigationSort = 2;

    protected static ?string $model = Producto::class;

    protected static ?string $navigationIcon = 'heroicon-o-rectangle-stack';

    public static function form(Form $form): Form
    {
    return $form
        ->schema([
            Forms\Components\Select::make('categoria_id')
                ->relationship('categoria', 'nombre')
                ->required()
                ->searchable()
                ->preload(),
            Forms\Components\TextInput::make('nombre')
                ->required()
                ->maxLength(100),
            Forms\Components\TextInput::make('precio')
                ->required()
                ->numeric()
                ->minValue(0.01)
                ->prefix('$'),
            Forms\Components\TextInput::make('stock')
                ->required()
                ->numeric()
                ->minValue(0)
                ->default(0),
            Forms\Components\FileUpload::make('imagen_url')
                ->label('Imagen')
                ->image()
                ->directory('productos')
                ->disk('public')
                ->maxSize(5120)
                ->columnSpanFull(),
        ]);
    }

    public static function table(Table $table): Table
    {
    return $table
        ->columns([
            Tables\Columns\ImageColumn::make('imagen_url')
                ->label('Foto')
                ->disk('public'),
            Tables\Columns\TextColumn::make('nombre')
                ->searchable()
                ->sortable(),
            Tables\Columns\TextColumn::make('categoria.nombre')
                ->label('Categoría')
                ->sortable(),
            Tables\Columns\TextColumn::make('precio')
                ->money('USD')
                ->sortable(),
            Tables\Columns\TextColumn::make('stock')
                ->sortable()
                ->badge()
                ->color(fn (int $state): string => $state > 0 ? 'success' : 'danger'),
        ])
        ->filters([
            //
        ])
        ->actions([
            Tables\Actions\EditAction::make(),
        ])
        ->bulkActions([
            Tables\Actions\BulkActionGroup::make([
                Tables\Actions\DeleteBulkAction::make(),
            ]),
        ]);
    }

    public static function getRelations(): array
    {
        return [
            //
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListProductos::route('/'),
            'create' => Pages\CreateProducto::route('/create'),
            'edit' => Pages\EditProducto::route('/{record}/edit'),
        ];
    }
}
