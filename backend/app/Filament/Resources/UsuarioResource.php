<?php

namespace App\Filament\Resources;

use App\Filament\Resources\UsuarioResource\Pages;
use App\Models\Usuario;
use App\Services\ExpoPushService;
use App\Events\RepartidorAprobado;
use App\Events\RepartidorRechazado;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Notifications\Notification;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
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
                Forms\Components\Select::make('facultad')
                    ->options([
                        'Facultad Ciencias de la Salud' => 'Facultad Ciencias de la Salud',
                        'Facultad Ciencias Administrativas, Contables y Comercio' => 'Facultad Ciencias Administrativas, Contables y Comercio',
                        'Facultad de Educación, Turismo, Artes y Humanidades' => 'Facultad de Educación, Turismo, Artes y Humanidades',
                        'Facultad Ingeniería, Industria y Construcción' => 'Facultad Ingeniería, Industria y Construcción',
                        'Facultad Ciencias de la Vida y Tecnologías' => 'Facultad Ciencias de la Vida y Tecnologías',
                        'Facultad Ciencias Sociales, Derecho y Bienestar' => 'Facultad Ciencias Sociales, Derecho y Bienestar',
                    ])
                    ->searchable(),
                Forms\Components\TextInput::make('telefono')
                    ->label('Teléfono')
                    ->maxLength(20)
                    ->tel(),
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
                Tables\Columns\TextColumn::make('telefono')
                    ->label('Teléfono')
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

                        // WebSocket: notificar al usuario en tiempo real si la app está abierta
                        broadcast(new RepartidorAprobado($record->fresh()))->toOthers();

                        // Push notification: avisar aunque la app esté cerrada
                        if ($record->expo_push_token) {
                            ExpoPushService::enviar(
                                [$record->expo_push_token],
                                '¡Tu solicitud fue aprobada! 🎉',
                                'Ya puedes entrar al modo repartidor y recibir pedidos.',
                                ['tipo' => 'repartidor_aprobado', 'usuario_id' => $record->id]
                            );
                        }

                        Notification::make()
                            ->title('Repartidor aprobado correctamente')
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

                        // WebSocket: notificar al usuario en tiempo real si la app está abierta
                        broadcast(new RepartidorRechazado($record->fresh()))->toOthers();

                        // Push notification: avisar aunque la app esté cerrada
                        if ($record->expo_push_token) {
                            ExpoPushService::enviar(
                                [$record->expo_push_token],
                                'Tu solicitud fue rechazada',
                                'Puedes volver a postular desde tu perfil cuando quieras.',
                                ['tipo' => 'repartidor_rechazado', 'usuario_id' => $record->id]
                            );
                        }

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
        // Siempre se puede editar, incluso registros con rol=administrador:
        // bloquear la edición fue lo que causó que un cambio de rol por
        // error (cliente -> administrador) no se pudiera revertir desde
        // el propio panel.
        return true;
    }

    public static function canDelete(Model $record): bool
    {
        // Solo se bloquea eliminar si es EL ÚLTIMO administrador (evita
        // quedarse sin ningún admin con acceso al panel). Cualquier otro
        // registro, incluidos otros admins, se puede eliminar normalmente.
        if ($record->rol !== 'administrador') {
            return true;
        }

        return \App\Models\Usuario::where('rol', 'administrador')->count() > 1;
    }
}
