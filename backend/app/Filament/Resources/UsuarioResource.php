<?php

namespace App\Filament\Resources;

use App\Filament\Resources\UsuarioResource\Pages;
use App\Models\Usuario;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Notifications\Notification;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Auth;

class UsuarioResource extends Resource
{
    protected static ?string $navigationGroup = 'Usuarios';
    protected static ?int $navigationSort = 1;
    
    protected static ?string $model = Usuario::class;

    protected static ?string $navigationIcon = 'heroicon-o-users';

    protected static ?string $navigationLabel = 'Usuarios y Repartidores';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\TextInput::make('cedula')
                    ->required()
                    ->maxLength(15),
                Forms\Components\TextInput::make('nombre_completo')
                    ->required()
                    ->maxLength(100),
                Forms\Components\TextInput::make('correo')
                    ->email()
                    ->required()
                    ->maxLength(100),
                Forms\Components\TextInput::make('clave')
                    ->password()
                    ->required(fn (string $context): bool => $context === 'create')
                    ->dehydrated(fn ($state) => filled($state))
                    ->maxLength(255),
                Forms\Components\Select::make('rol')
                    ->options([
                        'cliente' => 'Cliente',
                        'administrador' => 'Administrador',
                    ])
                    ->required(),
                Forms\Components\Select::make('estado_repartidor')
                    ->label('Estado de repartidor')
                    ->options([
                        'no_postulado' => 'No postulado',
                        'pendiente' => 'Pendiente de revisión',
                        'aprobado' => 'Aprobado',
                        'rechazado' => 'Rechazado',
                    ])
                    ->required(),
                Forms\Components\TextInput::make('facultad')
                    ->maxLength(100),
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
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('cedula')
                    ->searchable(),
                Tables\Columns\TextColumn::make('correo')
                    ->searchable(),
                Tables\Columns\TextColumn::make('facultad')
                    ->placeholder('—'),
                Tables\Columns\TextColumn::make('rol')
                    ->badge(),
                Tables\Columns\TextColumn::make('estado_repartidor')
                    ->label('Repartidor')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'aprobado' => 'success',
                        'pendiente' => 'warning',
                        'rechazado' => 'danger',
                        default => 'gray',
                    }),
                Tables\Columns\TextColumn::make('balance')
                    ->money('USD'),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('estado_repartidor')
                    ->label('Estado de repartidor')
                    ->options([
                        'no_postulado' => 'No postulado',
                        'pendiente' => 'Pendiente de revisión',
                        'aprobado' => 'Aprobado',
                        'rechazado' => 'Rechazado',
                    ]),
            ])
            ->actions([
                Tables\Actions\Action::make('aprobar_repartidor')
                    ->label('Aprobar')
                    ->icon('heroicon-o-check-circle')
                    ->color('success')
                    ->visible(fn (Usuario $record): bool => $record->estado_repartidor === 'pendiente')
                    ->requiresConfirmation()
                    ->action(function (Usuario $record) {
                        $record->update(['estado_repartidor' => 'aprobado']);

                        Notification::make()
                            ->title('Repartidor aprobado')
                            ->success()
                            ->send();
                    }),
                Tables\Actions\Action::make('rechazar_repartidor')
                    ->label('Rechazar')
                    ->icon('heroicon-o-x-circle')
                    ->color('danger')
                    ->visible(fn (Usuario $record): bool => $record->estado_repartidor === 'pendiente')
                    ->requiresConfirmation()
                    ->action(function (Usuario $record) {
                        $record->update(['estado_repartidor' => 'rechazado']);

                        Notification::make()
                            ->title('Postulación rechazada')
                            ->warning()
                            ->send();
                    }),
                Tables\Actions\EditAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListUsuarios::route('/'),
            'create' => Pages\CreateUsuario::route('/create'),
            'edit' => Pages\EditUsuario::route('/{record}/edit'),
        ];
    }
    public static function canEdit(Model $record): bool
    {
        return $record->rol !== 'administrador';
    }

    public static function canDelete(Model $record): bool
    {
        return $record->rol !== 'administrador';
    }
}
