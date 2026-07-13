<?php

namespace App\Filament\Resources\Students\RelationManagers;

use App\Actions\Bookings\BookSession;
use App\Actions\Bookings\CancelBooking;
use App\Enums\BookingStatus;
use App\Enums\SessionStatus;
use App\Exceptions\BookingException;
use App\Models\Booking;
use App\Models\ScheduledSession;
use Filament\Actions\Action;
use Filament\Forms\Components\Select;
use Filament\Notifications\Notification;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

/**
 * A student's group-session reservations, from their profile. Booking/cancelling
 * runs through the domain Actions (credit, capacity, cancellation window); this
 * only orchestrates and surfaces errors.
 */
class BookingsRelationManager extends RelationManager
{
    protected static string $relationship = 'bookings';

    protected static ?string $title = 'Reservas de prácticas';

    public function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('session.activity.name')
                    ->label('Actividad')
                    ->placeholder('—'),
                TextColumn::make('session.starts_at')
                    ->label('Cuándo')
                    ->dateTime('d/m/Y H:i')
                    ->placeholder('—'),
                TextColumn::make('status')
                    ->label('Estado')
                    ->badge(),
            ])
            ->defaultSort('booked_at', 'desc')
            ->headerActions([
                Action::make('book')
                    ->label('Reservar en sesión')
                    ->icon('heroicon-o-plus')
                    ->schema([
                        Select::make('scheduled_session_id')
                            ->label('Sesión')
                            ->options(fn () => ScheduledSession::query()
                                ->where('status', SessionStatus::Scheduled->value)
                                ->where('starts_at', '>', now())
                                ->with('activity')
                                ->orderBy('starts_at')
                                ->get()
                                ->mapWithKeys(fn (ScheduledSession $s) => [
                                    $s->id => ($s->activity?->name ?? 'Sesión')
                                        .' — '.$s->starts_at->format('d/m H:i')
                                        ." ({$s->seatsAvailable()} libres)",
                                ]))
                            ->searchable()
                            ->required(),
                    ])
                    ->action(function (array $data): void {
                        $session = ScheduledSession::findOrFail($data['scheduled_session_id']);

                        try {
                            app(BookSession::class)->execute($this->getOwnerRecord(), $session);
                        } catch (BookingException $e) {
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
