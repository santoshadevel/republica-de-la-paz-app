<?php

namespace App\Livewire\Portal;

use App\Actions\Bookings\BookSession;
use App\Actions\Bookings\CancelBooking;
use App\Enums\BookingStatus;
use App\Enums\SessionStatus;
use App\Exceptions\BookingException;
use App\Models\Booking;
use App\Models\ScheduledSession;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Layout;
use Livewire\Component;

/** Calendar of upcoming group classes the student can book or cancel. */
#[Layout('components.layouts.app')]
class Schedule extends Component
{
    /**
     * Event feed for the portal calendar (called by FullCalendar via $wire).
     * Shows scheduled group classes, highlighting the ones the student booked.
     *
     * @return array<int, array<string, mixed>>
     */
    public function fetchEvents(string $start, string $end): array
    {
        $student = Auth::user()->student;

        $sessions = ScheduledSession::query()
            ->with(['activity', 'practitioner', 'room'])
            ->withCount('activeBookings')
            ->where('status', SessionStatus::Scheduled->value)
            ->whereBetween('starts_at', [Carbon::parse($start), Carbon::parse($end)])
            ->get();

        $myBookings = $student !== null
            ? Booking::query()
                ->where('student_id', $student->id)
                ->whereIn('scheduled_session_id', $sessions->pluck('id'))
                ->whereIn('status', [BookingStatus::Booked->value, BookingStatus::Attended->value])
                ->get()
                ->keyBy('scheduled_session_id')
            : collect();

        $canBook = (bool) $student?->currentMembership()?->hasAvailableCredit();

        return $sessions->map(function (ScheduledSession $session) use ($myBookings, $canBook) {
            $booking = $myBookings->get($session->id);
            $booked = $booking !== null;
            $free = max(0, $session->capacity - $session->active_bookings_count);
            $color = $booked ? '#059669' : ($free > 0 ? '#0ea5e9' : '#a8a29e');

            return [
                'id' => 'session-'.$session->id,
                'title' => ($session->activity?->name ?? 'Sesión').($booked ? ' ✓' : " · {$free}"),
                'start' => $session->starts_at->toIso8601String(),
                'end' => $session->ends_at?->toIso8601String(),
                'backgroundColor' => $color,
                'borderColor' => $color,
                'extendedProps' => [
                    'sessionId' => $session->id,
                    'bookingId' => $booking?->id,
                    'booked' => $booked,
                    'free' => $free,
                    'canBook' => $canBook,
                    // Warns the student before they lose the credit; CancelBooking
                    // re-decides at cancel time, this is only the heads-up.
                    'refunds' => $session->refundsIfCancelledNow(),
                    'activity' => $session->activity?->name ?? 'Sesión',
                    'when' => $session->starts_at->isoFormat('ddd D/MM HH:mm').'–'.$session->ends_at?->format('H:i'),
                    'practitioner' => $session->practitioner?->fullName(),
                    'room' => $session->room?->name,
                ],
            ];
        })->all();
    }

    public function book(int $sessionId): void
    {
        $student = Auth::user()->student;
        $session = ScheduledSession::find($sessionId);

        if ($student === null || $session === null) {
            return;
        }

        try {
            app(BookSession::class)->execute($student, $session);
            session()->flash('status', 'Reserva confirmada.');
        } catch (BookingException $e) {
            session()->flash('error', $e->getMessage());
        }

        $this->dispatch('calendar-refresh');
    }

    public function cancel(int $bookingId): void
    {
        $student = Auth::user()->student;
        $booking = Booking::where('student_id', $student?->id)->find($bookingId);

        if ($booking === null) {
            return;
        }

        try {
            app(CancelBooking::class)->execute($booking);
            session()->flash('status', 'Reserva cancelada.');
        } catch (BookingException $e) {
            session()->flash('error', $e->getMessage());
        }

        $this->dispatch('calendar-refresh');
    }

    public function render()
    {
        $membership = Auth::user()->student?->currentMembership();

        return view('livewire.portal.schedule', [
            'membership' => $membership,
            'canBook' => (bool) $membership?->hasAvailableCredit(),
            'refundWindowHours' => (int) config('booking.group_cancellation_hours', 1),
        ]);
    }
}
