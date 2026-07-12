<?php

namespace App\Filament\Resources\ScheduledSessions\Schemas;

use App\Enums\ActivityType;
use App\Enums\SessionStatus;
use App\Models\Practitioner;
use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Schema;
use Illuminate\Database\Eloquent\Builder;

class ScheduledSessionForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Select::make('activity_id')
                    ->label('Actividad')
                    ->relationship(
                        'activity',
                        'name',
                        fn (Builder $query) => $query->where('type', ActivityType::GroupClass->value),
                    )
                    ->searchable()
                    ->preload()
                    ->required(),
                Select::make('practitioner_id')
                    ->label('Facilitador')
                    ->relationship('practitioner', 'first_name')
                    ->getOptionLabelFromRecordUsing(fn (Practitioner $record) => $record->fullName())
                    ->searchable()
                    ->preload(),
                Select::make('room_id')
                    ->label('Sala')
                    ->relationship('room', 'name')
                    ->searchable()
                    ->preload(),
                DateTimePicker::make('starts_at')
                    ->label('Inicio')
                    ->seconds(false)
                    ->native(false)
                    ->required(),
                DateTimePicker::make('ends_at')
                    ->label('Fin')
                    ->seconds(false)
                    ->native(false)
                    ->required()
                    ->after('starts_at'),
                TextInput::make('capacity')
                    ->label('Cupo')
                    ->numeric()
                    ->minValue(1)
                    ->required(),
                Select::make('status')
                    ->label('Estado')
                    ->options(SessionStatus::options())
                    ->default(SessionStatus::Scheduled->value)
                    ->required(),
                Textarea::make('notes')
                    ->label('Notas')
                    ->columnSpanFull(),
            ]);
    }
}
