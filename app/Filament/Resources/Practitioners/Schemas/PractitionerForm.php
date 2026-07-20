<?php

namespace App\Filament\Resources\Practitioners\Schemas;

use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Schema;

class PractitionerForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Select::make('user_id')
                    ->label('Cuenta de usuario')
                    ->relationship('user', 'name')
                    ->searchable()
                    ->preload()
                    ->helperText('Opcional. Asociá la cuenta con la que esta persona inicia sesión, para que a futuro pueda ver su propia agenda y honorarios. Dejalo vacío si el profesional no accede al sistema.'),
                TextInput::make('first_name')
                    ->label('Nombre')
                    ->required()
                    ->maxLength(255),
                TextInput::make('last_name')
                    ->label('Apellido')
                    ->required()
                    ->maxLength(255),
                TextInput::make('email')
                    ->label('Correo electrónico')
                    ->email()
                    ->required()
                    ->maxLength(255)
                    ->unique(ignoreRecord: true),
                TextInput::make('phone')
                    ->label('Teléfono')
                    ->tel()
                    ->maxLength(255),
                TextInput::make('identity_number')
                    ->label('Nº de identidad')
                    ->maxLength(255)
                    ->unique(ignoreRecord: true),
                Select::make('activities')
                    ->label('Especialidades')
                    ->relationship('activities', 'name')
                    ->multiple()
                    ->preload()
                    ->searchable()
                    ->helperText('Actividades que dicta este profesional (yoga, terapias, etc.).')
                    ->columnSpanFull(),
                Textarea::make('bio')
                    ->label('Biografía')
                    ->helperText('Se muestra en la landing pública, en "Referentes de la República".')
                    ->columnSpanFull(),
                FileUpload::make('avatar_path')
                    ->label('Foto')
                    ->image()
                    ->avatar()
                    ->imageEditor()
                    ->disk('public')
                    ->directory('practitioners')
                    ->maxSize(2048)
                    ->helperText('Retrato para la landing. Si no hay foto, se muestran las iniciales.')
                    ->columnSpanFull(),
                Toggle::make('is_active')
                    ->label('Activo')
                    ->default(true),
            ]);
    }
}
