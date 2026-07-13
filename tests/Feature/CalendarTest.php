<?php

namespace Tests\Feature;

use App\Actions\Bookings\BookSession;
use App\Actions\Memberships\SellMembership;
use App\Enums\ActivityType;
use App\Enums\BookingStatus;
use App\Enums\Role;
use App\Enums\SessionStatus;
use App\Filament\Pages\Calendar;
use App\Models\Activity;
use App\Models\Appointment;
use App\Models\Event;
use App\Models\MembershipPlan;
use App\Models\ScheduledSession;
use App\Models\Student;
use App\Models\User;
use App\Services\Scheduling\CalendarService;
use Database\Seeders\PlanSeeder;
use Filament\Facades\Filament;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Livewire\Livewire;
use Spatie\Permission\Models\Role as SpatieRole;
use Tests\TestCase;

class CalendarTest extends TestCase
{
    use RefreshDatabase;

    private function admin(): User
    {
        SpatieRole::findOrCreate(Role::Admin->value, 'web');
        $user = User::factory()->create();
        $user->assignRole(Role::Admin->value);

        return $user;
    }

    /** Populate one of each schedulable type inside the first week of August 2026. */
    private function seedWeek(): void
    {
        $activity = Activity::factory()->create(['type' => ActivityType::GroupClass, 'name' => 'Yoga']);

        ScheduledSession::factory()->create([
            'activity_id' => $activity->id,
            'starts_at' => '2026-08-04 09:00:00',
            'ends_at' => '2026-08-04 10:00:00',
            'capacity' => 10,
        ]);

        Appointment::factory()->booked()->create([
            'starts_at' => '2026-08-05 16:00:00',
            'ends_at' => '2026-08-05 17:00:00',
        ]);

        Event::factory()->create([
            'name' => 'Charla de bienestar',
            'starts_at' => '2026-08-06 19:00:00',
            'ends_at' => '2026-08-06 21:00:00',
        ]);
    }

    private function weekEvents(): array
    {
        Filament::setCurrentPanel(Filament::getPanel('admin'));

        return app(CalendarService::class)->eventsBetween(
            Carbon::parse('2026-08-03'),
            Carbon::parse('2026-08-10'),
        );
    }

    public function test_admin_can_open_the_calendar_page(): void
    {
        Livewire::actingAs($this->admin())->test(Calendar::class)->assertOk();
    }

    public function test_service_normalises_all_three_types(): void
    {
        $this->seedWeek();

        $ids = collect($this->weekEvents())->pluck('id');

        $this->assertCount(3, $ids);
        $this->assertTrue($ids->contains(fn ($id) => str_starts_with($id, 'session-')));
        $this->assertTrue($ids->contains(fn ($id) => str_starts_with($id, 'appointment-')));
        $this->assertTrue($ids->contains(fn ($id) => str_starts_with($id, 'event-')));
    }

    public function test_group_session_event_carries_occupancy_and_edit_url(): void
    {
        $this->seedWeek();

        $session = collect($this->weekEvents())->first(fn ($e) => str_starts_with($e['id'], 'session-'));

        $this->assertStringContainsString('Yoga (0/10)', $session['title']);
        $this->assertSame('Sesión grupal', $session['extendedProps']['type']);
        $this->assertStringContainsString('/admin/', $session['url']);
    }

    public function test_cancelled_sessions_are_excluded(): void
    {
        $this->seedWeek();

        ScheduledSession::factory()->create([
            'starts_at' => '2026-08-07 09:00:00',
            'ends_at' => '2026-08-07 10:00:00',
            'status' => SessionStatus::Cancelled,
        ]);

        $this->assertCount(3, $this->weekEvents()); // the cancelled one is not returned
    }

    private function studentWithPass(): Student
    {
        $student = Student::factory()->create();
        (new SellMembership)->execute($student, MembershipPlan::where('slug', 'citizen-pass')->firstOrFail());

        return $student;
    }

    public function test_manage_modal_reserves_a_new_student_and_cancels_removed_ones(): void
    {
        $this->seed(PlanSeeder::class);

        $activity = Activity::factory()->create(['type' => ActivityType::GroupClass]);
        $session = ScheduledSession::factory()->create([
            'activity_id' => $activity->id,
            'capacity' => 10,
            'starts_at' => now()->addDay()->setTime(9, 0),
            'ends_at' => now()->addDay()->setTime(10, 0),
        ]);

        $bookingA = app(BookSession::class)->execute($this->studentWithPass(), $session);
        app(BookSession::class)->execute($this->studentWithPass(), $session); // booking B
        $studentC = $this->studentWithPass();
        $this->assertSame(2, $session->fresh()->seatsTaken());

        $bookingB = $session->bookings()->where('id', '!=', $bookingA->id)->firstOrFail();

        // Mark B for cancellation and reserve C — simulating the edited modal state.
        Livewire::actingAs($this->admin())->test(Calendar::class)
            ->mountAction('manageSession', arguments: ['session' => $session->id])
            ->setActionData([
                'cancel_booking_ids' => [$bookingB->id],
                'student_id' => $studentC->id,
            ])
            ->callMountedAction()
            ->assertHasNoActionErrors();

        $this->assertSame(BookingStatus::Cancelled, $bookingB->fresh()->status);
        $this->assertSame(2, $session->fresh()->seatsTaken()); // A + C
    }

    public function test_events_outside_the_range_are_excluded(): void
    {
        $this->seedWeek();

        Event::factory()->create([
            'starts_at' => '2026-09-01 19:00:00',
            'ends_at' => '2026-09-01 21:00:00',
        ]);

        $this->assertCount(3, $this->weekEvents()); // September event is outside the queried week
    }
}
