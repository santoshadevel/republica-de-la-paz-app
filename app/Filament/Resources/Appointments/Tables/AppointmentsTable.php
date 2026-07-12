<?php

namespace App\Filament\Resources\Appointments\Tables;

use App\Actions\Appointments\BookAppointment;
use App\Actions\Appointments\CancelAppointment;
use App\Actions\Appointments\CompleteAppointment;
use App\Enums\AppointmentStatus;
use App\Exceptions\AppointmentException;
use App\Models\Appointment;
use App\Models\Student;
use Filament\Actions\Action;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Actions\ForceDeleteBulkAction;
use Filament\Actions\RestoreBulkAction;
use Filament\Forms\Components\Select;
use Filament\Notifications\Notification;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Filters\TrashedFilter;
use Filament\Tables\Table;

class AppointmentsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->defaultSort('starts_at', 'desc')
            ->columns([
                TextColumn::make('starts_at')
                    ->label('Inicio')
                    ->dateTime('d/m/Y H:i')
                    ->sortable(),
                TextColumn::make('activity.name')
                    ->label('Especialidad')
                    ->placeholder('—'),
                TextColumn::make('practitioner.first_name')
                    ->label('Profesional')
                    ->formatStateUsing(fn (Appointment $record) => $record->practitioner?->fullName()),
                TextColumn::make('student.first_name')
                    ->label('Alumno')
                    ->formatStateUsing(fn (Appointment $record) => $record->student?->fullName())
                    ->placeholder('—'),
                TextColumn::make('status')
                    ->label('Estado')
                    ->badge(),
                TextColumn::make('price')
                    ->label('Precio')
                    ->placeholder('—'),
            ])
            ->filters([
                SelectFilter::make('status')
                    ->label('Estado')
                    ->options(AppointmentStatus::options()),
                SelectFilter::make('practitioner_id')
                    ->label('Profesional')
                    ->relationship('practitioner', 'first_name'),
                TrashedFilter::make(),
            ])
            ->recordActions([
                Action::make('book')
                    ->label('Reservar')
                    ->icon('heroicon-o-user-plus')
                    ->visible(fn (Appointment $record) => $record->status === AppointmentStatus::Available)
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
                    ->action(function (array $data, Appointment $record): void {
                        $student = Student::findOrFail($data['student_id']);

                        try {
                            app(BookAppointment::class)->execute($student, $record);
                        } catch (AppointmentException $e) {
                            Notification::make()->danger()->title($e->getMessage())->send();
                        }
                    }),
                Action::make('complete')
                    ->label('Completar')
                    ->icon('heroicon-o-check')
                    ->color('success')
                    ->visible(fn (Appointment $record) => $record->status === AppointmentStatus::Booked)
                    ->action(function (Appointment $record): void {
                        try {
                            app(CompleteAppointment::class)->execute($record);
                        } catch (AppointmentException $e) {
                            Notification::make()->danger()->title($e->getMessage())->send();
                        }
                    }),
                Action::make('cancel')
                    ->label('Cancelar')
                    ->icon('heroicon-o-x-mark')
                    ->color('danger')
                    ->requiresConfirmation()
                    ->visible(fn (Appointment $record) => in_array($record->status, [AppointmentStatus::Available, AppointmentStatus::Booked], true))
                    ->action(function (Appointment $record): void {
                        try {
                            app(CancelAppointment::class)->execute($record);
                        } catch (AppointmentException $e) {
                            Notification::make()->danger()->title($e->getMessage())->send();
                        }
                    }),
                EditAction::make(),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                    ForceDeleteBulkAction::make(),
                    RestoreBulkAction::make(),
                ]),
            ]);
    }
}
