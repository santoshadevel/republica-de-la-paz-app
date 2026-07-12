<?php

namespace App\Filament\Resources\Appointments\Schemas;

use App\Enums\ActivityType;
use App\Enums\AppointmentStatus;
use App\Models\Practitioner;
use App\Models\Student;
use App\Support\Money;
use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Schema;
use Illuminate\Database\Eloquent\Builder;

class AppointmentForm
{
    public static function configure(Schema $schema): Schema
    {
        $currency = config('currency.default');
        $symbol = config("currency.currencies.{$currency}.symbol");
        $digits = config("currency.currencies.{$currency}.digits");

        return $schema
            ->components([
                Select::make('practitioner_id')
                    ->label('Profesional')
                    ->relationship('practitioner', 'first_name')
                    ->getOptionLabelFromRecordUsing(fn (Practitioner $record) => $record->fullName())
                    ->searchable()
                    ->preload()
                    ->required(),
                Select::make('activity_id')
                    ->label('Especialidad')
                    ->relationship(
                        'activity',
                        'name',
                        fn (Builder $query) => $query->where('type', ActivityType::IndividualSession->value),
                    )
                    ->searchable()
                    ->preload(),
                Select::make('room_id')
                    ->label('Sala / consultorio')
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
                Select::make('status')
                    ->label('Estado')
                    ->options(AppointmentStatus::options())
                    ->default(AppointmentStatus::Available->value)
                    ->required(),
                Select::make('student_id')
                    ->label('Alumno')
                    ->options(fn () => Student::query()
                        ->where('is_active', true)
                        ->get()
                        ->mapWithKeys(fn (Student $s) => [$s->id => $s->fullName()]))
                    ->searchable()
                    ->helperText('Opcional: dejar vacío para un horario disponible.'),
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
                Textarea::make('notes')
                    ->label('Notas')
                    ->columnSpanFull(),
            ]);
    }
}
