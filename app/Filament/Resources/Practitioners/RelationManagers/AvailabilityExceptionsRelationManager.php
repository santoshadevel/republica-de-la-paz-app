<?php

namespace App\Filament\Resources\Practitioners\RelationManagers;

use App\Models\PractitionerAvailabilityException;
use Filament\Actions\CreateAction;
use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\TimePicker;
use Filament\Forms\Components\Toggle;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

/** Date-specific overrides: closed days (holidays) or special hours. */
class AvailabilityExceptionsRelationManager extends RelationManager
{
    protected static string $relationship = 'availabilityExceptions';

    protected static ?string $title = 'Excepciones';

    public function form(Schema $schema): Schema
    {
        return $schema->components([
            DatePicker::make('date')
                ->label('Fecha')
                ->native(false)
                ->required(),
            Toggle::make('is_available')
                ->label('Disponible ese día')
                ->helperText('Apagado = cerrado (feriado). Encendido = horario especial.')
                ->live(),
            TimePicker::make('start_time')
                ->label('Desde')
                ->seconds(false)
                ->visible(fn (Get $get) => (bool) $get('is_available'))
                ->required(fn (Get $get) => (bool) $get('is_available')),
            TimePicker::make('end_time')
                ->label('Hasta')
                ->seconds(false)
                ->after('start_time')
                ->visible(fn (Get $get) => (bool) $get('is_available'))
                ->required(fn (Get $get) => (bool) $get('is_available')),
            TextInput::make('reason')
                ->label('Motivo')
                ->maxLength(255)
                ->columnSpanFull(),
        ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->defaultSort('date')
            ->columns([
                TextColumn::make('date')
                    ->label('Fecha')
                    ->date('d/m/Y'),
                IconColumn::make('is_available')
                    ->label('Disponible')
                    ->boolean(),
                TextColumn::make('range')
                    ->label('Horario')
                    ->state(fn (PractitionerAvailabilityException $record) => $record->range() ?? 'Cerrado'),
                TextColumn::make('reason')
                    ->label('Motivo')
                    ->placeholder('—'),
            ])
            ->headerActions([
                CreateAction::make()->label('Agregar excepción'),
            ])
            ->recordActions([
                EditAction::make(),
                DeleteAction::make(),
            ]);
    }
}
