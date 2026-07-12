<?php

namespace App\Filament\Resources\ScheduledSessions\RelationManagers;

use App\Actions\Bookings\BookSession;
use App\Actions\Bookings\CancelBooking;
use App\Actions\Bookings\MarkAttendance;
use App\Enums\BookingStatus;
use App\Exceptions\BookingException;
use App\Models\Booking;
use App\Models\Student;
use Filament\Actions\Action;
use Filament\Forms\Components\Select;
use Filament\Notifications\Notification;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

/**
 * Roster of a session: enrol students (on their behalf), cancel and record
 * attendance. All logic lives in the booking Actions; this only orchestrates
 * and surfaces domain errors as notifications.
 */
class BookingsRelationManager extends RelationManager
{
    protected static string $relationship = 'bookings';

    protected static ?string $title = 'Reservas';

    public function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('student.first_name')
                    ->label('Alumno')
                    ->formatStateUsing(fn (Booking $record) => $record->student?->fullName())
                    ->searchable(),
                TextColumn::make('status')
                    ->label('Estado')
                    ->badge(),
                TextColumn::make('booked_at')
                    ->label('Reservada')
                    ->dateTime('d/m/Y H:i')
                    ->placeholder('—'),
            ])
            ->headerActions([
                Action::make('book')
                    ->label('Reservar para un alumno')
                    ->icon('heroicon-o-plus')
                    ->schema([
                        Select::make('student_id')
                            ->label('Alumno')
                            ->options(fn () => Student::query()
                                ->where('is_active', true)
                                ->get()
                                ->mapWithKeys(fn (Student $s) => [$s->id => $s->fullName()]))
                            ->searchable()
                            ->required(),
                    ])
                    ->action(function (array $data): void {
                        $student = Student::findOrFail($data['student_id']);

                        try {
                            app(BookSession::class)->execute($student, $this->getOwnerRecord());
                        } catch (BookingException $e) {
                            Notification::make()->danger()->title($e->getMessage())->send();
                        }
                    }),
            ])
            ->recordActions([
                Action::make('attended')
                    ->label('Asistió')
                    ->icon('heroicon-o-check')
                    ->color('success')
                    ->visible(fn (Booking $record) => $record->status !== BookingStatus::Cancelled)
                    ->action(fn (Booking $record) => app(MarkAttendance::class)->execute($record, attended: true)),
                Action::make('no_show')
                    ->label('No asistió')
                    ->icon('heroicon-o-x-mark')
                    ->color('warning')
                    ->visible(fn (Booking $record) => $record->status !== BookingStatus::Cancelled)
                    ->action(fn (Booking $record) => app(MarkAttendance::class)->execute($record, attended: false)),
                Action::make('cancel')
                    ->label('Cancelar')
                    ->icon('heroicon-o-trash')
                    ->color('danger')
                    ->requiresConfirmation()
                    ->visible(fn (Booking $record) => $record->status === BookingStatus::Booked)
                    ->action(function (Booking $record): void {
                        try {
                            app(CancelBooking::class)->execute($record);
                        } catch (BookingException $e) {
                            Notification::make()->danger()->title($e->getMessage())->send();
                        }
                    }),
            ]);
    }
}
