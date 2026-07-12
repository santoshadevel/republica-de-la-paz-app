<?php

namespace App\Filament\Resources\Rooms\Schemas;

use App\Enums\RoomType;
use Filament\Forms\Components\ColorPicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Schema;

class RoomForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('name')
                    ->label('Nombre')
                    ->required()
                    ->maxLength(255)
                    ->unique(ignoreRecord: true),
                Select::make('type')
                    ->label('Tipo')
                    ->options(RoomType::options())
                    ->default(RoomType::Physical->value)
                    ->required()
                    ->live(),
                TextInput::make('capacity')
                    ->label('Capacidad')
                    ->helperText('Dejar vacío para cupo ilimitado.')
                    ->numeric()
                    ->minValue(1),
                TextInput::make('meeting_url')
                    ->label('Link de reunión')
                    ->url()
                    ->maxLength(255)
                    // Solo aplica a salas virtuales.
                    ->visible(fn ($get) => $get('type') === RoomType::Virtual->value)
                    ->requiredIf('type', RoomType::Virtual->value),
                ColorPicker::make('color')
                    ->label('Color (calendario)'),
                Textarea::make('description')
                    ->label('Descripción')
                    ->columnSpanFull(),
                Toggle::make('is_active')
                    ->label('Activa')
                    ->default(true),
            ]);
    }
}
