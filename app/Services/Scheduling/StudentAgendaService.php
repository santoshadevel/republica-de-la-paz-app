<?php

namespace App\Services\Scheduling;

use App\Models\Appointment;
use App\Models\Booking;
use App\Models\EventRegistration;
use App\Models\Student;
use Illuminate\Support\Carbon;

/**
 * A single student's agenda across the three participation types (group bookings,
 * individual appointments and event registrations), normalised into one timeline.
 *
 * Read-only coordination layer: the booking bot and the API read this to know
 * what a student is attending, without touching the three underlying tables.
 */
class StudentAgendaService
{
    /**
     * The student's agenda split into upcoming (soonest first) and past
     * (most recent first).
     *
     * @return array{upcoming: array<int, array<string, mixed>>, past: array<int, array<string, mixed>>}
     */
    public function for(Student $student): array
    {
        $entries = collect([
            ...$this->fromBookings($student),
            ...$this->fromAppointments($student),
            ...$this->fromRegistrations($student),
        ])->filter(fn (array $entry) => $entry['starts_at'] !== null);

        $now = Carbon::now();

        return [
            'upcoming' => $entries
                ->filter(fn (array $e) => $e['starts_at']->greaterThanOrEqualTo($now))
                ->sortBy(fn (array $e) => $e['starts_at'])
                ->values()
                ->all(),
            'past' => $entries
                ->filter(fn (array $e) => $e['starts_at']->lessThan($now))
                ->sortByDesc(fn (array $e) => $e['starts_at'])
                ->values()
                ->all(),
        ];
    }

    /** @return array<int, array<string, mixed>> */
    private function fromBookings(Student $student): array
    {
        return $student->bookings()
            ->with('session.activity')
            ->get()
            ->map(fn (Booking $booking) => [
                'type' => 'Sesión grupal',
                'title' => $booking->session?->activity?->name ?? 'Sesión grupal',
                'starts_at' => $booking->session?->starts_at,
                'ends_at' => $booking->session?->ends_at,
                'status' => $booking->status->label(),
            ])
            ->all();
    }

    /** @return array<int, array<string, mixed>> */
    private function fromAppointments(Student $student): array
    {
        return $student->appointments()
            ->with(['activity', 'practitioner'])
            ->get()
            ->map(fn (Appointment $appointment) => [
                'type' => 'Acompañamiento',
                'title' => $appointment->activity?->name
                    ?? $appointment->practitioner?->fullName()
                    ?? 'Acompañamiento',
                'starts_at' => $appointment->starts_at,
                'ends_at' => $appointment->ends_at,
                'status' => $appointment->status->label(),
            ])
            ->all();
    }

    /** @return array<int, array<string, mixed>> */
    private function fromRegistrations(Student $student): array
    {
        return $student->eventRegistrations()
            ->with('event')
            ->get()
            ->map(fn (EventRegistration $registration) => [
                'type' => 'Evento',
                'title' => $registration->event?->name ?? 'Evento',
                'starts_at' => $registration->event?->starts_at,
                'ends_at' => $registration->event?->ends_at,
                'status' => $registration->status->label(),
            ])
            ->all();
    }
}
