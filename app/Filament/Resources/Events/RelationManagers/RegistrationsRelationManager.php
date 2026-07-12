<?php

namespace App\Filament\Resources\Events\RelationManagers;

use App\Actions\Events\CancelEventRegistration;
use App\Actions\Events\MarkEventAttendance;
use App\Actions\Events\RegisterForEvent;
use App\Enums\EventRegistrationStatus;
use App\Exceptions\EventException;
use App\Models\EventRegistration;
use App\Models\Student;
use Filament\Actions\Action;
use Filament\Forms\Components\Select;
use Filament\Notifications\Notification;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

/** Registrations for an event: enrol students, cancel and record attendance. */
class RegistrationsRelationManager extends RelationManager
{
    protected static string $relationship = 'registrations';

    protected static ?string $title = 'Inscripciones';

    public function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('student.first_name')
                    ->label('Alumno')
                    ->formatStateUsing(fn (EventRegistration $record) => $record->student?->fullName())
                    ->searchable(),
                TextColumn::make('status')
                    ->label('Estado')
                    ->badge(),
                TextColumn::make('registered_at')
                    ->label('Inscripción')
                    ->dateTime('d/m/Y H:i')
                    ->placeholder('—'),
            ])
            ->headerActions([
                Action::make('register')
                    ->label('Inscribir alumno')
                    ->icon('heroicon-o-user-plus')
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
                            app(RegisterForEvent::class)->execute($student, $this->getOwnerRecord());
                        } catch (EventException $e) {
                            Notification::make()->danger()->title($e->getMessage())->send();
                        }
                    }),
            ])
            ->recordActions([
                Action::make('attended')
                    ->label('Asistió')
                    ->icon('heroicon-o-check')
                    ->color('success')
                    ->visible(fn (EventRegistration $record) => $record->status !== EventRegistrationStatus::Cancelled)
                    ->action(fn (EventRegistration $record) => app(MarkEventAttendance::class)->execute($record, attended: true)),
                Action::make('no_show')
                    ->label('No asistió')
                    ->icon('heroicon-o-x-mark')
                    ->color('warning')
                    ->visible(fn (EventRegistration $record) => $record->status !== EventRegistrationStatus::Cancelled)
                    ->action(fn (EventRegistration $record) => app(MarkEventAttendance::class)->execute($record, attended: false)),
                Action::make('cancel')
                    ->label('Cancelar')
                    ->icon('heroicon-o-trash')
                    ->color('danger')
                    ->requiresConfirmation()
                    ->visible(fn (EventRegistration $record) => $record->status === EventRegistrationStatus::Registered)
                    ->action(function (EventRegistration $record): void {
                        try {
                            app(CancelEventRegistration::class)->execute($record);
                        } catch (EventException $e) {
                            Notification::make()->danger()->title($e->getMessage())->send();
                        }
                    }),
            ]);
    }
}
