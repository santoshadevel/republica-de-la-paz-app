<?php

namespace App\Filament\Resources\Students\RelationManagers;

use App\Actions\Events\CancelEventRegistration;
use App\Actions\Events\RegisterForEvent;
use App\Enums\EventRegistrationStatus;
use App\Enums\EventStatus;
use App\Exceptions\EventException;
use App\Models\Event;
use App\Models\EventRegistration;
use Filament\Actions\Action;
use Filament\Forms\Components\Select;
use Filament\Notifications\Notification;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

/**
 * A student's event registrations, from their profile. Registering/cancelling
 * runs through the domain Actions (capacity, timing); this only orchestrates.
 */
class EventRegistrationsRelationManager extends RelationManager
{
    protected static string $relationship = 'eventRegistrations';

    protected static ?string $title = 'Inscripciones a eventos';

    public function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('event.name')
                    ->label('Evento')
                    ->placeholder('—'),
                TextColumn::make('event.starts_at')
                    ->label('Cuándo')
                    ->dateTime('d/m/Y H:i')
                    ->placeholder('—'),
                TextColumn::make('status')
                    ->label('Estado')
                    ->badge(),
            ])
            ->defaultSort('registered_at', 'desc')
            ->headerActions([
                Action::make('register')
                    ->label('Inscribir en evento')
                    ->icon('heroicon-o-plus')
                    ->schema([
                        Select::make('event_id')
                            ->label('Evento')
                            ->options(fn () => Event::query()
                                ->where('status', EventStatus::Scheduled->value)
                                ->where('starts_at', '>', now())
                                ->orderBy('starts_at')
                                ->get()
                                ->mapWithKeys(fn (Event $e) => [
                                    $e->id => $e->name.' — '.$e->starts_at->format('d/m H:i'),
                                ]))
                            ->searchable()
                            ->required(),
                    ])
                    ->action(function (array $data): void {
                        $event = Event::findOrFail($data['event_id']);

                        try {
                            app(RegisterForEvent::class)->execute($this->getOwnerRecord(), $event);
                        } catch (EventException $e) {
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
