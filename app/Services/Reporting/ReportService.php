<?php

namespace App\Services\Reporting;

use App\Enums\AppointmentStatus;
use App\Enums\BookingStatus;
use App\Models\Appointment;
use App\Models\Booking;
use App\Models\Event;
use App\Models\ScheduledSession;
use App\Models\Student;
use App\Models\StudentMembership;
use App\Models\Transaction;
use App\Support\Money;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;

/**
 * Read-only metrics for dashboards and reports. Pure aggregations, reusable by
 * Filament widgets and the future API. Money is returned as Money value objects.
 */
class ReportService
{
    /** Operational summary for a given day (defaults to today). */
    public function todaySummary(?Carbon $day = null): array
    {
        $day ??= Carbon::today();
        $date = $day->toDateString();

        $groupBookings = Booking::query()
            ->whereIn('status', [BookingStatus::Booked->value, BookingStatus::Attended->value])
            ->whereHas('session', fn ($q) => $q->whereDate('starts_at', $date))
            ->count();

        $individualToday = Appointment::query()
            ->whereIn('status', [AppointmentStatus::Booked->value, AppointmentStatus::Completed->value])
            ->whereDate('starts_at', $date)
            ->count();

        $income = (int) Transaction::query()->income()->notTransfer()->whereDate('occurred_on', $date)->sum('amount');
        $expense = (int) Transaction::query()->expense()->notTransfer()->whereDate('occurred_on', $date)->sum('amount');

        return [
            'scheduled_students' => $groupBookings + $individualToday,
            'group_sessions' => ScheduledSession::query()->whereDate('starts_at', $date)->count(),
            'individual_sessions' => $individualToday,
            'events' => Event::query()->whereDate('starts_at', $date)->count(),
            'income' => Money::ofMinor($income),
            'expense' => Money::ofMinor($expense),
            'balance' => Money::ofMinor($income - $expense),
        ];
    }

    /** Business state (income/expense/result/margin) for a month. */
    public function monthlyBusinessState(?Carbon $month = null): array
    {
        $month ??= Carbon::today();
        $from = $month->copy()->startOfMonth()->toDateString();
        $to = $month->copy()->endOfMonth()->toDateString();

        $income = (int) Transaction::query()->income()->notTransfer()->whereBetween('occurred_on', [$from, $to])->sum('amount');
        $expense = (int) Transaction::query()->expense()->notTransfer()->whereBetween('occurred_on', [$from, $to])->sum('amount');
        $result = $income - $expense;

        return [
            'income' => Money::ofMinor($income),
            'expense' => Money::ofMinor($expense),
            'result' => Money::ofMinor($result),
            'margin' => $income > 0 ? round($result / $income * 100, 1) : 0.0,
        ];
    }

    /** Community metrics (active students, memberships, new sign-ups). */
    public function communityStats(): array
    {
        $activeMemberships = StudentMembership::query()->active();

        return [
            'active_members' => (clone $activeMemberships)->distinct('student_id')->count('student_id'),
            'active_memberships' => (clone $activeMemberships)->count(),
            'new_students_this_month' => Student::query()
                ->whereBetween('created_at', [Carbon::today()->startOfMonth(), Carbon::today()->endOfMonth()])
                ->count(),
            'expiring_soon' => (clone $activeMemberships)
                ->whereDate('ends_at', '<=', Carbon::today()->addDays(7)->toDateString())
                ->count(),
        ];
    }

    /** Query of memberships expiring within the given number of days. */
    public function expiringMembershipsQuery(int $days = 7): Builder
    {
        return StudentMembership::query()
            ->active()
            ->whereDate('ends_at', '<=', Carbon::today()->addDays($days)->toDateString())
            ->orderBy('ends_at');
    }

    /** Memberships expiring within the given number of days. */
    public function expiringMemberships(int $days = 7): Collection
    {
        return $this->expiringMembershipsQuery($days)->with(['student', 'plan'])->get();
    }
}
