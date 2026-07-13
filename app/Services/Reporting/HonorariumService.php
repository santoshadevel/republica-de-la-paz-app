<?php

namespace App\Services\Reporting;

use App\Enums\AppointmentStatus;
use App\Enums\EventRegistrationStatus;
use App\Enums\SessionStatus;
use App\Models\Appointment;
use App\Models\Event;
use App\Models\FeeScheme;
use App\Models\Practitioner;
use App\Models\ScheduledSession;
use App\Support\Money;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;

/**
 * Monthly honorarium liquidation per practitioner: counts services rendered,
 * the income they generated and the fee owed, based on each practitioner's fee
 * schemes. See docs/REQUISITOS.md (4.9).
 */
class HonorariumService
{
    /**
     * @return array{
     *   group_sessions:int, individual_sessions:int, events:int,
     *   income_generated:Money, fee_total:Money
     * }
     */
    public function liquidate(Practitioner $practitioner, ?Carbon $month = null): array
    {
        $month ??= Carbon::today();
        $from = $month->copy()->startOfMonth();
        $to = $month->copy()->endOfMonth();

        $schemes = $practitioner->feeSchemes()->get();
        $feeMinor = 0;
        $incomeMinor = 0;

        // Group classes led (not cancelled): fee is typically a fixed amount.
        $sessions = ScheduledSession::query()
            ->where('practitioner_id', $practitioner->getKey())
            ->whereBetween('starts_at', [$from, $to])
            ->where('status', '!=', SessionStatus::Cancelled->value)
            ->get();

        foreach ($sessions as $session) {
            $feeMinor += $this->fee($schemes, $session->activity_id, Money::ofMinor(0))->minorAmount;
        }

        // Individual sessions (booked/completed): income = the session price.
        $appointments = Appointment::query()
            ->where('practitioner_id', $practitioner->getKey())
            ->whereBetween('starts_at', [$from, $to])
            ->whereIn('status', [AppointmentStatus::Booked->value, AppointmentStatus::Completed->value])
            ->get();

        foreach ($appointments as $appointment) {
            $price = $appointment->price ?? Money::ofMinor(0);
            $incomeMinor += $price->minorAmount;
            $feeMinor += $this->fee($schemes, $appointment->activity_id, $price)->minorAmount;
        }

        // Events facilitated (not cancelled): income = price × seats taken.
        $events = $practitioner->events()
            ->whereBetween('starts_at', [$from, $to])
            ->where('status', '!=', 'cancelled')
            ->get();

        foreach ($events as $event) {
            $eventIncome = Money::ofMinor(($event->price?->minorAmount ?? 0) * $this->attendeeCount($event));
            $incomeMinor += $eventIncome->minorAmount;
            // Events have no activity; use the practitioner's default scheme.
            $feeMinor += $this->fee($schemes, null, $eventIncome)->minorAmount;
        }

        return [
            'group_sessions' => $sessions->count(),
            'individual_sessions' => $appointments->count(),
            'events' => $events->count(),
            'income_generated' => Money::ofMinor($incomeMinor),
            'fee_total' => Money::ofMinor($feeMinor),
        ];
    }

    /** Liquidation for every practitioner that has a fee scheme configured. */
    public function liquidateAll(?Carbon $month = null): Collection
    {
        return Practitioner::query()
            ->whereHas('feeSchemes')
            ->get()
            ->map(fn (Practitioner $p) => [
                'practitioner' => $p,
                ...$this->liquidate($p, $month),
            ]);
    }

    /** Resolve the fee for a service: scheme for the activity, else the default. */
    private function fee(Collection $schemes, ?int $activityId, Money $income): Money
    {
        $scheme = $schemes->firstWhere('activity_id', $activityId)
            ?? $schemes->firstWhere('activity_id', null);

        return $scheme instanceof FeeScheme ? $scheme->feeFor($income) : Money::ofMinor(0);
    }

    private function attendeeCount(Event $event): int
    {
        return $event->registrations()
            ->whereIn('status', [
                EventRegistrationStatus::Registered->value,
                EventRegistrationStatus::Attended->value,
            ])
            ->count();
    }
}
