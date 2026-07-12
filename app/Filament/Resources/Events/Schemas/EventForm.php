<?php

namespace App\Filament\Resources\Events\Schemas;

use App\Enums\EventStatus;
use App\Models\Practitioner;
use App\Support\Money;
use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Schema;

class EventForm
{
    public static function configure(Schema $schema): Schema
    {
        $currency = config('currency.default');
        $symbol = config("currency.currencies.{$currency}.symbol");
        $digits = config("currency.currencies.{$currency}.digits");

        return $schema
            ->components([
                TextInput::make('name')
                    ->label('Nombre')
                    ->required()
                    ->maxLength(255),
                Textarea::make('description')
                    ->label('Descripción')
                    ->columnSpanFull(),
                FileUpload::make('image')
                    ->label('Imagen')
                    ->image()
                    ->directory('events'),
                Select::make('facilitators')
                    ->label('Facilitadores')
                    ->relationship('facilitators', 'first_name')
                    ->getOptionLabelFromRecordUsing(fn (Practitioner $record) => $record->fullName())
                    ->multiple()
                    ->searchable()
                    ->preload(),
                TextInput::make('location')
                    ->label('Lugar')
                    ->maxLength(255),
                DateTimePicker::make('starts_at')
                    ->label('Inicio')
                    ->seconds(false)
                    ->native(false)
                    ->required(),
                DateTimePicker::make('ends_at')
                    ->label('Fin')
                    ->seconds(false)
                    ->native(false)
                    ->after('starts_at'),
                TextInput::make('price')
                    ->label('Precio')
                    ->rules(['numeric', 'min:0'])
                    ->inputMode('decimal')
                    ->prefix($symbol)
                    ->step($digits > 0 ? (10 ** -$digits) : 1)
                    ->formatStateUsing(fn ($state) => $state instanceof Money ? $state->toMajor() : $state)
                    ->dehydrateStateUsing(fn ($state) => $state === null || $state === ''
                        ? null
                        : Money::ofMajor((float) $state)->minorAmount),
                TextInput::make('capacity')
                    ->label('Cupo')
                    ->numeric()
                    ->minValue(1)
                    ->helperText('Vacío para cupo ilimitado.'),
                Select::make('status')
                    ->label('Estado')
                    ->options(EventStatus::options())
                    ->default(EventStatus::Scheduled->value)
                    ->required(),
            ]);
    }
}
