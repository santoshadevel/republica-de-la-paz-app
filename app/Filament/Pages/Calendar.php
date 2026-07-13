<?php

namespace App\Filament\Pages;

use App\Actions\Bookings\BookSession;
use App\Actions\Bookings\CancelBooking;
use App\Exceptions\BookingException;
use App\Models\Booking;
use App\Models\ScheduledSession;
use App\Models\Student;
use App\Services\Scheduling\CalendarService;
use BackedEnum;
use Filament\Actions\Action;
use Filament\Actions\Concerns\InteractsWithActions;
use Filament\Actions\Contracts\HasActions;
use Filament\Forms\Components\CheckboxList;
use Filament\Forms\Components\Hidden;
use Filament\Forms\Components\Select;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Filament\Schemas\Components\Tabs;
use Filament\Schemas\Components\Tabs\Tab;
use Filament\Support\Icons\Heroicon;
use Illuminate\Support\Carbon;

/**
 * Unified agenda calendar: group sessions, individual appointments and events on
 * one FullCalendar view. Data comes from CalendarService (the shared coordination
 * layer); clicking a group session opens its management modal (roster + booking).
 */
class Calendar extends Page implements HasActions
{
    use InteractsWithActions;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedCalendarDays;

    protected static string|\UnitEnum|null $navigationGroup = 'Agenda';

    protected static ?string $navigationLabel = 'Calendario';

    protected static ?int $navigationSort = -1;

    protected static ?string $title = 'Calendario';

    protected string $view = 'filament.pages.calendar';

    /**
     * Event feed called by FullCalendar (through $wire) whenever the visible
     * range changes. Dates arrive as ISO strings from the browser.
     *
     * @return array<int, array<string, mixed>>
     */
    public function fetchEvents(string $start, string $end): array
    {
        return app(CalendarService::class)->eventsBetween(
            Carbon::parse($start),
            Carbon::parse($end),
        );
    }

    /**
     * Manage a group session from a calendar click: see who is enrolled (cancel
     * per student) and reserve a new one. Mounted with the session id as an
     * argument; all business rules live in the booking Actions.
     */
    public function manageSessionAction(): Action
    {
        return Action::make('manageSession')
            ->label('Gestionar sesión')
            ->modalHeading(fn (array $arguments): string => $this->sessionHeading($arguments['session'] ?? null))
            ->modalSubmitActionLabel('Guardar cambios')
            ->fillForm(fn (array $arguments): array => [
                'session_id' => $arguments['session'] ?? null,
                'cancel_booking_ids' => [],
                'student_id' => null,
            ])
            ->schema([
                Hidden::make('session_id'),
                Tabs::make()->tabs([
                    Tab::make('Inscriptos')
                        ->icon('heroicon-o-user-group')
                        ->schema([
                            CheckboxList::make('cancel_booking_ids')
                                ->label('Inscriptos')
                                ->helperText('Marcá los alumnos que querés dar de baja al guardar.')
                                ->options(fn ($get): array => $this->rosterOptions($get('session_id')))
                                ->noSearchResultsMessage('Sin inscriptos.')
                                ->bulkToggleable(),
                        ]),
                    Tab::make('Reservar alumno')
                        ->icon('heroicon-o-user-plus')
                        ->schema([
                            Select::make('student_id')
                                ->label('Alumno')
                                ->options(fn () => Student::query()
                                    ->where('is_active', true)
                                    ->get()
                                    ->mapWithKeys(fn (Student $s) => [$s->id => $s->fullName()]))
                                ->searchable()
                                ->helperText('Descuenta del pase del alumno según su membresía.'),
                        ]),
                ]),
            ])
            ->action(function (array $data, array $arguments): void {
                $session = ScheduledSession::with('activeBookings')->find($arguments['session'] ?? null);

                if ($session === null) {
                    Notification::make()->danger()->title('Sesión no encontrada.')->send();

                    return;
                }

                $cancelIds = collect($data['cancel_booking_ids'] ?? [])
                    ->map(fn ($id) => (int) $id)
                    ->all();

                $cancelled = 0;
                foreach ($session->activeBookings as $booking) {
                    if (! in_array($booking->getKey(), $cancelIds, true)) {
                        continue;
                    }

                    try {
                        app(CancelBooking::class)->execute($booking);
                        $cancelled++;
                    } catch (BookingException $e) {
                        Notification::make()->danger()->title($e->getMessage())->send();
                    }
                }

                $booked = false;
                if (filled($data['student_id'] ?? null) && ($student = Student::find($data['student_id'])) !== null) {
                    try {
                        app(BookSession::class)->execute($student, $session);
                        $booked = true;
                    } catch (BookingException $e) {
                        Notification::make()->danger()->title($e->getMessage())->send();
                    }
                }

                if ($cancelled > 0 || $booked) {
                    Notification::make()
                        ->success()
                        ->title('Sesión actualizada')
                        ->body(trim(($booked ? 'Reserva creada. ' : '').($cancelled > 0 ? "Reservas canceladas: {$cancelled}." : '')))
                        ->send();

                    $this->dispatch('calendar-refresh');
                }
            });
    }

    /**
     * Active bookings of a session as checkbox options (id => name · status),
     * doubling as the roster display.
     *
     * @return array<int, string>
     */
    private function rosterOptions(?int $sessionId): array
    {
        $session = ScheduledSession::with('activeBookings.student')->find($sessionId);

        if ($session === null) {
            return [];
        }

        return $session->activeBookings
            ->mapWithKeys(fn (Booking $booking) => [
                $booking->getKey() => ($booking->student?->fullName() ?? 'Alumno').' · '.$booking->status->label(),
            ])
            ->all();
    }

    private function sessionHeading(?int $sessionId): string
    {
        $session = ScheduledSession::with('activity')->find($sessionId);

        if ($session === null) {
            return 'Sesión';
        }

        return ($session->activity?->name ?? 'Sesión').' · '.$session->starts_at->format('d/m/Y H:i');
    }
}
