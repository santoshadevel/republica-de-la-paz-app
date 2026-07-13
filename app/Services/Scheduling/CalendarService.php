<?php

namespace App\Services\Scheduling;

use App\Enums\AppointmentStatus;
use App\Enums\EventStatus;
use App\Enums\SessionStatus;
use App\Filament\Resources\Appointments\AppointmentResource;
use App\Filament\Resources\Events\EventResource;
use App\Filament\Resources\ScheduledSessions\ScheduledSessionResource;
use App\Models\Appointment;
use App\Models\Event;
use App\Models\ScheduledSession;
use Illuminate\Support\Carbon;

/**
 * Normalises the agenda across the three schedulable types (group sessions,
 * individual appointments and events) into a single list of FullCalendar event
 * objects for a date range.
 *
 * Read-only coordination layer: it never merges the underlying tables (each keeps
 * its own domain rules) — it is the single source of truth that feeds the
 * calendar UI today and the coordination bot / REST API tomorrow.
 */
class CalendarService
{
    /** Fallback colours per type (group sessions prefer the activity colour). */
    public const COLOR_GROUP = '#0ea5e9';

    public const COLOR_INDIVIDUAL = '#8b5cf6';

    public const COLOR_EVENT = '#f59e0b';

    /**
     * All agenda entries whose start falls within [$start, $end], shaped for
     * FullCalendar.
     *
     * @return array<int, array<string, mixed>>
     */
    public function eventsBetween(Carbon $start, Carbon $end): array
    {
        return [
            ...$this->groupSessions($start, $end),
            ...$this->appointments($start, $end),
            ...$this->events($start, $end),
        ];
    }

    /** @return array<int, array<string, mixed>> */
    private function groupSessions(Carbon $start, Carbon $end): array
    {
        return ScheduledSession::query()
            ->with(['activity', 'room', 'practitioner'])
            ->withCount('activeBookings')
            ->where('status', '!=', SessionStatus::Cancelled->value)
            ->whereBetween('starts_at', [$start, $end])
            ->get()
            ->map(fn (ScheduledSession $session) => [
                'id' => 'session-'.$session->getKey(),
                'title' => ($session->activity?->name ?? 'Sesión')." ({$session->active_bookings_count}/{$session->capacity})",
                'start' => $session->starts_at->toIso8601String(),
                'end' => $session->ends_at?->toIso8601String(),
                'url' => ScheduledSessionResource::getUrl('edit', ['record' => $session->getKey()]),
                'backgroundColor' => $session->activity?->color ?: self::COLOR_GROUP,
                'borderColor' => $session->activity?->color ?: self::COLOR_GROUP,
                'extendedProps' => [
                    'type' => 'Sesión grupal',
                    'sessionId' => $session->getKey(), // enables in-calendar booking
                    'practitioner' => $session->practitioner?->fullName(),
                    'room' => $session->room?->name,
                    'occupancy' => "{$session->active_bookings_count}/{$session->capacity} cupos",
                ],
            ])
            ->all();
    }

    /** @return array<int, array<string, mixed>> */
    private function appointments(Carbon $start, Carbon $end): array
    {
        return Appointment::query()
            ->with(['activity', 'room', 'practitioner', 'student'])
            ->where('status', '!=', AppointmentStatus::Cancelled->value)
            ->whereBetween('starts_at', [$start, $end])
            ->get()
            ->map(function (Appointment $appointment) {
                $who = $appointment->student?->fullName() ?? 'Disponible';
                $what = $appointment->activity?->name;

                return [
                    'id' => 'appointment-'.$appointment->getKey(),
                    'title' => $what ? "{$what} · {$who}" : $who,
                    'start' => $appointment->starts_at->toIso8601String(),
                    'end' => $appointment->ends_at?->toIso8601String(),
                    'url' => AppointmentResource::getUrl('edit', ['record' => $appointment->getKey()]),
                    'backgroundColor' => self::COLOR_INDIVIDUAL,
                    'borderColor' => self::COLOR_INDIVIDUAL,
                    'extendedProps' => [
                        'type' => 'Acompañamiento',
                        'practitioner' => $appointment->practitioner?->fullName(),
                        'room' => $appointment->room?->name,
                        'student' => $appointment->student?->fullName(),
                    ],
                ];
            })
            ->all();
    }

    /** @return array<int, array<string, mixed>> */
    private function events(Carbon $start, Carbon $end): array
    {
        return Event::query()
            ->where('status', '!=', EventStatus::Cancelled->value)
            ->whereBetween('starts_at', [$start, $end])
            ->get()
            ->map(fn (Event $event) => [
                'id' => 'event-'.$event->getKey(),
                'title' => $event->name,
                'start' => $event->starts_at->toIso8601String(),
                'end' => $event->ends_at?->toIso8601String(),
                'url' => EventResource::getUrl('edit', ['record' => $event->getKey()]),
                'backgroundColor' => self::COLOR_EVENT,
                'borderColor' => self::COLOR_EVENT,
                'extendedProps' => [
                    'type' => 'Evento',
                    'location' => $event->location,
                ],
            ])
            ->all();
    }
}
