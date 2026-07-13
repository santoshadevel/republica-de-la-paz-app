<?php

namespace Tests\Feature;

use App\Actions\Bookings\BookSession;
use App\Actions\Memberships\SellMembership;
use App\Enums\ActivityType;
use App\Enums\Role;
use App\Livewire\Portal\Schedule;
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

class PortalScheduleTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        foreach (Role::values() as $role) {
            SpatieRole::findOrCreate($role, 'web');
        }
        $this->seed(PlanSeeder::class);
    }

    private function studentUser(bool $withPass = true): array
    {
        $user = User::registerStudent('Zoe G', 'zoe@example.com', 'password123');
        $student = Student::registerFrom($user, 'Zoe G');

        if ($withPass) {
            (new SellMembership)->execute($student, MembershipPlan::where('slug', 'citizen-pass')->firstOrFail());
        }

        return [$user, $student];
    }

    private function futureSession(): ScheduledSession
    {
        $activity = Activity::factory()->create(['type' => ActivityType::GroupClass]);

        return ScheduledSession::factory()->create([
            'activity_id' => $activity->id,
            'starts_at' => now()->addDays(1)->setTime(9, 0),
            'ends_at' => now()->addDays(1)->setTime(10, 0),
        ]);
    }

    public function test_a_student_with_a_pass_can_book_from_the_portal(): void
    {
        [$user] = $this->studentUser();
        $session = $this->futureSession();

        Livewire::actingAs($user)->test(Schedule::class)
            ->call('book', $session->id)
            ->assertHasNoErrors();

        $this->assertSame(1, $session->fresh()->seatsTaken());
    }

    public function test_a_student_without_a_pass_cannot_book(): void
    {
        [$user] = $this->studentUser(withPass: false);
        $session = $this->futureSession();

        Livewire::actingAs($user)->test(Schedule::class)
            ->call('book', $session->id);

        $this->assertSame(0, $session->fresh()->seatsTaken()); // domain rejected the booking
    }

    public function test_fetch_events_marks_the_students_booking(): void
    {
        [$user, $student] = $this->studentUser();
        $session = $this->futureSession();
        app(BookSession::class)->execute($student, $session);

        $events = Livewire::actingAs($user)->test(Schedule::class)
            ->instance()
            ->fetchEvents(now()->startOfDay()->toIso8601String(), now()->addDays(3)->toIso8601String());

        $this->assertCount(1, $events);
        $this->assertSame($session->id, $events[0]['extendedProps']['sessionId']);
        $this->assertTrue($events[0]['extendedProps']['booked']);
    }

    public function test_a_student_can_cancel_from_the_portal(): void
    {
        [$user, $student] = $this->studentUser();
        $session = $this->futureSession();
        $booking = app(BookSession::class)->execute($student, $session);
        $this->assertSame(1, $session->fresh()->seatsTaken());

        Livewire::actingAs($user)->test(Schedule::class)
            ->call('cancel', $booking->id)
            ->assertHasNoErrors();

        $this->assertSame(0, $session->fresh()->seatsTaken());
    }
}
