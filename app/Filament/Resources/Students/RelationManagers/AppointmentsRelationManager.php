<?php

namespace App\Filament\Resources\Students\RelationManagers;

use App\Actions\Appointments\BookAppointment;
use App\Actions\Appointments\CancelAppointment;
use App\Enums\AppointmentStatus;
use App\Exceptions\AppointmentException;
use App\Models\Appointment;
use Filament\Actions\Action;
use Filament\Forms\Components\Select;
use Filament\Notifications\Notification;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

/**
 * A student's individual sessions (acompañamientos), from their profile. Booking
 * an available slot / cancelling runs through the domain Actions (late-cancel fee
 * lives there); this only orchestrates.
 */
class AppointmentsRelationManager extends RelationManager
{
    protected static string $relationship = 'appointments';

    protected static ?string $title = 'Acompañamientos';

    public function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('activity.name')
                    ->label('Especialidad')
                    ->placeholder('—'),
                TextColumn::make('practitioner.first_name')
                    ->label('Profesional')
                    ->formatStateUsing(fn (Appointment $record) => $record->practitioner?->fullName())
                    ->placeholder('—'),
                TextColumn::make('starts_at')
                    ->label('Cuándo')
                    ->dateTime('d/m/Y H:i'),
                TextColumn::make('status')
                    ->label('Estado')
                    ->badge(),
            ])
            ->defaultSort('starts_at', 'desc')
            ->headerActions([
                Action::make('book')
                    ->label('Agendar acompañamiento')
                    ->icon('heroicon-o-plus')
                    ->schema([
                        Select::make('appointment_id')
                            ->label('Horario disponible')
                            ->options(fn () => Appointment::query()
                                ->where('status', AppointmentStatus::Available->value)
                                ->where('starts_at', '>', now())
                                ->with(['activity', 'practitioner'])
                                ->orderBy('starts_at')
                                ->get()
                                ->mapWithKeys(fn (Appointment $a) => [
                                    $a->id => ($a->activity?->name ?? 'Acompañamiento')
                                        .' · '.($a->practitioner?->fullName() ?? '—')
                                        .' — '.$a->starts_at->format('d/m H:i'),
                                ]))
                            ->searchable()
                            ->required()
                            ->helperText('Se listan los horarios libres creados por los profesionales.'),
                    ])
                    ->action(function (array $data): void {
                        $appointment = Appointment::findOrFail($data['appointment_id']);

                        try {
                            app(BookAppointment::class)->execute($this->getOwnerRecord(), $appointment);
                        } catch (AppointmentException $e) {
                            Notification::make()->danger()->title($e->getMessage())->send();
                        }
                    }),
            ])
            ->recordActions([
                Action::make('cancel')
                    ->label('Cancelar')
                    ->icon('heroicon-o-trash')
                    ->color('danger')
                    ->requiresConfirmation()
                    ->visible(fn (Appointment $record) => in_array(
                        $record->status,
                        [AppointmentStatus::Available, AppointmentStatus::Booked],
                        true,
                    ))
                    ->action(function (Appointment $record): void {
                        try {
                            app(CancelAppointment::class)->execute($record);
                        } catch (AppointmentException $e) {
                            Notification::make()->danger()->title($e->getMessage())->send();
                        }
                    }),
            ]);
    }
}
