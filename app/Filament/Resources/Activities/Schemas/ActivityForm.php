<?php

namespace App\Filament\Resources\Activities\Schemas;

use App\Enums\ActivityType;
use Filament\Forms\Components\ColorPicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Schema;

class ActivityForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('name')
                    ->label('Nombre')
                    ->required()
                    ->maxLength(255),
                Select::make('type')
                    ->label('Tipo')
                    ->options(ActivityType::options())
                    ->default(ActivityType::GroupClass->value)
                    ->required(),
                Textarea::make('description')
                    ->label('Descripción')
                    ->columnSpanFull(),
                TextInput::make('default_duration_minutes')
                    ->label('Duración (min)')
                    ->numeric()
                    ->minValue(1),
                ColorPicker::make('color')
                    ->label('Color (calendario)'),
                Select::make('default_room_id')
                    ->label('Sala por defecto')
                    ->relationship('defaultRoom', 'name')
                    ->searchable()
                    ->preload(),
                Toggle::make('is_active')
                    ->label('Activa')
                    ->default(true),
            ]);
    }
}
