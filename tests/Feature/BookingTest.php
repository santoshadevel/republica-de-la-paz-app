<?php

namespace Tests\Feature;

use App\Actions\Bookings\BookSession;
use App\Actions\Bookings\CancelBooking;
use App\Actions\Bookings\MarkAttendance;
use App\Actions\Memberships\SellMembership;
use App\Enums\ActivityType;
use App\Enums\BookingStatus;
use App\Enums\Role;
use App\Exceptions\BookingException;
use App\Filament\Resources\ScheduledSessions\Pages\CreateScheduledSession;
use App\Filament\Resources\ScheduledSessions\Pages\EditScheduledSession;
use App\Filament\Resources\ScheduledSessions\Pages\ListScheduledSessions;
use App\Filament\Resources\ScheduledSessions\RelationManagers\BookingsRelationManager;
use App\Models\Activity;
use App\Models\MembershipPlan;
use App\Models\ScheduledSession;
use App\Models\Student;
use App\Models\User;
use Database\Seeders\PlanSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Spatie\Permission\Models\Role as SpatieRole;
use Tests\TestCase;

class BookingTest extends TestCase
{
    use RefreshDatabase;

    private function plan(string $slug): MembershipPlan
    {
        $this->seed(PlanSeeder::class);

        return MembershipPlan::where('slug', $slug)->firstOrFail();
    }

    private function admin(): User
    {
        SpatieRole::findOrCreate(Role::Admin->value, 'web');
        $user = User::factory()->create();
        $user->assignRole(Role::Admin->value);

        return $user;
    }

    /** A student with an active pass and a scheduled group session on the same activity. */
    private function studentWithPass(string $slug = 'citizen-pass'): array
    {
        $student = Student::factory()->create();
        $plan = $this->plan($slug);
        (new SellMembership)->execute($student, $plan);

        $activity = Activity::factory()->create(['type' => ActivityType::GroupClass]);
        $session = ScheduledSession::factory()->create(['activity_id' => $activity->id]);

        return [$student, $session];
    }

    public function test_booking_consumes_one_credit_and_takes_a_seat(): void
    {
        [$student, $session] = $this->studentWithPass(); // 4 créditos

        $booking = app(BookSession::class)->execute($student, $session);

        $this->assertSame(BookingStatus::Booked, $booking->status);
        $this->assertSame(3, $student->currentMembership()->creditsRemaining());
        $this->assertSame(1, $session->fresh()->seatsTaken());
    }

    public function test_cannot_book_without_an_active_membership(): void
    {
        $student = Student::factory()->create();
        $activity = Activity::factory()->create(['type' => ActivityType::GroupClass]);
        $session = ScheduledSession::factory()->create(['activity_id' => $activity->id]);

        $this->expectException(BookingException::class);
        $this->expectExceptionMessage('No tienes un pase vigente.');

        app(BookSession::class)->execute($student, $session);
    }

    public function test_cannot_book_when_no_credit_left(): void
    {
        $student = Student::factory()->create();
        (new SellMembership)->execute($student, $this->plan('free-trial')); // 1 crédito
        $activity = Activity::factory()->create(['type' => ActivityType::GroupClass]);

        $first = ScheduledSession::factory()->create(['activity_id' => $activity->id]);
        $second = ScheduledSession::factory()->create(['activity_id' => $activity->id]);

        app(BookSession::class)->execute($student, $first);

        $this->expectException(BookingException::class);
        $this->expectExceptionMessage('No tienes prácticas disponibles');
        app(BookSession::class)->execute($student, $second);
    }

    public function test_unlimited_membership_books_without_consuming(): void
    {
        [$student, $session] = $this->studentWithPass('republic-membership');

        app(BookSession::class)->execute($student, $session);

        $this->assertNull($student->currentMembership()->creditsRemaining()); // infinito
        $this->assertSame(1, $session->fresh()->seatsTaken());
    }

    public function test_cannot_exceed_capacity(): void
    {
        $plan = $this->plan('community-pass');
        $activity = Activity::factory()->create(['type' => ActivityType::GroupClass]);
        $session = ScheduledSession::factory()->withCapacity(1)->create(['activity_id' => $activity->id]);

        $a = Student::factory()->create();
        $b = Student::factory()->create();
        (new SellMembership)->execute($a, $plan);
        (new SellMembership)->execute($b, $plan);

        app(BookSession::class)->execute($a, $session);

        $this->expectException(BookingException::class);
        $this->expectExceptionMessage('completa');
        app(BookSession::class)->execute($b, $session);
    }

    public function test_cannot_double_book_the_same_session(): void
    {
        [$student, $session] = $this->studentWithPass('community-pass');

        app(BookSession::class)->execute($student, $session);

        $this->expectException(BookingException::class);
        $this->expectExceptionMessage('ya tiene una reserva');
        app(BookSession::class)->execute($student, $session);
    }

    public function test_early_cancellation_refunds_the_credit_and_frees_the_seat(): void
    {
        [$student, $session] = $this->studentWithPass('citizen-pass'); // 4, sesión mañana
        $booking = app(BookSession::class)->execute($student, $session);
        $this->assertSame(3, $student->currentMembership()->creditsRemaining());

        app(CancelBooking::class)->execute($booking);

        $this->assertSame(BookingStatus::Cancelled, $booking->fresh()->status);
        $this->assertSame(4, $student->currentMembership()->creditsRemaining()); // reintegrado
        $this->assertSame(0, $session->fresh()->seatsTaken());
    }

    public function test_late_cancellation_consumes_the_credit(): void
    {
        $student = Student::factory()->create();
        (new SellMembership)->execute($student, $this->plan('citizen-pass')); // 4
        $activity = Activity::factory()->create(['type' => ActivityType::GroupClass]);
        // Session starts in 30 min → inside the 1h window → no refund.
        $session = ScheduledSession::factory()->startingSoon()->create(['activity_id' => $activity->id]);

        $booking = app(BookSession::class)->execute($student, $session);
        $this->assertSame(3, $student->currentMembership()->creditsRemaining());

        app(CancelBooking::class)->execute($booking);

        $this->assertSame(3, $student->currentMembership()->creditsRemaining()); // NO reintegra
    }

    public function test_no_show_keeps_the_credit_consumed(): void
    {
        [$student, $session] = $this->studentWithPass('citizen-pass');
        $booking = app(BookSession::class)->execute($student, $session);

        app(MarkAttendance::class)->execute($booking, attended: false);

        $this->assertSame(BookingStatus::NoShow, $booking->fresh()->status);
        $this->assertSame(3, $student->currentMembership()->creditsRemaining());
    }

    public function test_admin_can_list_and_create_scheduled_sessions(): void
    {
        ScheduledSession::factory()->count(2)->create();
        $admin = $this->admin();

        Livewire::actingAs($admin)->test(ListScheduledSessions::class)->assertOk();
        Livewire::actingAs($admin)->test(CreateScheduledSession::class)->assertOk();
    }

    public function test_roster_can_enrol_a_student_from_the_session(): void
    {
        [$student, $session] = $this->studentWithPass('citizen-pass');

        Livewire::actingAs($this->admin())->test(BookingsRelationManager::class, [
            'ownerRecord' => $session,
            'pageClass' => EditScheduledSession::class,
        ])
            ->callTableAction('book', data: ['student_id' => $student->getKey()])
            ->assertHasNoTableActionErrors();

        $this->assertSame(1, $session->fresh()->seatsTaken());
    }
}
