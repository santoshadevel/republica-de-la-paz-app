<?php

namespace App\Filament\Resources\Practitioners\RelationManagers;

use App\Enums\Weekday;
use Filament\Actions\CreateAction;
use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TimePicker;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

/** Recurring weekly availability blocks (when the practitioner is willing to work). */
class AvailabilityRelationManager extends RelationManager
{
    protected static string $relationship = 'availabilities';

    protected static ?string $title = 'Disponibilidad semanal';

    public function form(Schema $schema): Schema
    {
        return $schema->components([
            Select::make('day_of_week')
                ->label('Día')
                ->options(Weekday::options())
                ->required(),
            TimePicker::make('start_time')
                ->label('Desde')
                ->seconds(false)
                ->required(),
            TimePicker::make('end_time')
                ->label('Hasta')
                ->seconds(false)
                ->required()
                ->after('start_time'),
        ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->defaultSort('day_of_week')
            ->columns([
                TextColumn::make('day_of_week')
                    ->label('Día')
                    ->badge(),
                TextColumn::make('start_time')
                    ->label('Desde')
                    ->formatStateUsing(fn ($state) => substr((string) $state, 0, 5)),
                TextColumn::make('end_time')
                    ->label('Hasta')
                    ->formatStateUsing(fn ($state) => substr((string) $state, 0, 5)),
            ])
            ->headerActions([
                CreateAction::make()->label('Agregar bloque'),
            ])
            ->recordActions([
                EditAction::make(),
                DeleteAction::make(),
            ]);
    }
}
