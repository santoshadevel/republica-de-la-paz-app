<?php

namespace App\Filament\Resources\Students\Schemas;

use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Schema;

class StudentForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
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
                    // Ficha única por email (constraint también en DB).
                    ->unique(ignoreRecord: true),
                TextInput::make('phone')
                    ->label('Teléfono')
                    ->tel()
                    ->maxLength(255),
                TextInput::make('identity_number')
                    ->label('Nº de identidad')
                    ->maxLength(255)
                    // Documento genérico opcional; unicidad validada en código
                    // (no hay constraint duro en DB para soportar white-label).
                    ->unique(ignoreRecord: true),
                TextInput::make('tax_id')
                    ->label('RUC / Nº fiscal')
                    ->maxLength(255),
                DatePicker::make('birth_date')
                    ->label('Fecha de nacimiento')
                    ->native(false)
                    ->maxDate(now()),
                Select::make('acquisition_source')
                    ->label('¿Cómo nos conoció?')
                    // Canales genéricos; el admin puede escribir uno nuevo.
                    ->options([
                        'instagram' => 'Instagram',
                        'facebook' => 'Facebook',
                        'google' => 'Google / Búsqueda web',
                        'referral' => 'Recomendación',
                        'event' => 'Evento',
                        'walk_in' => 'Pasó por el local',
                        'other' => 'Otro',
                    ])
                    ->searchable()
                    ->allowHtml(false),
                Textarea::make('goals')
                    ->label('Objetivos')
                    ->columnSpanFull(),
                Textarea::make('notes')
                    ->label('Observaciones')
                    ->columnSpanFull(),
                Toggle::make('is_active')
                    ->label('Activo')
                    ->default(true),
            ]);
    }
}
