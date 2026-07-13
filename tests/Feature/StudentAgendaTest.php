<?php

namespace Tests\Feature;

use App\Actions\Memberships\SellMembership;
use App\Enums\ActivityType;
use App\Enums\AppointmentStatus;
use App\Enums\BookingStatus;
use App\Enums\EventRegistrationStatus;
use App\Enums\Role;
use App\Filament\Resources\Students\Pages\EditStudent;
use App\Filament\Resources\Students\RelationManagers\AppointmentsRelationManager;
use App\Filament\Resources\Students\RelationManagers\BookingsRelationManager;
use App\Filament\Resources\Students\RelationManagers\EventRegistrationsRelationManager;
use App\Models\Activity;
use App\Models\Appointment;
use App\Models\Booking;
use App\Models\Event;
use App\Models\EventRegistration;
use App\Models\MembershipPlan;
use App\Models\ScheduledSession;
use App\Models\Student;
use App\Models\User;
use App\Services\Scheduling\StudentAgendaService;
use Database\Seeders\PlanSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Spatie\Permission\Models\Role as SpatieRole;
use Tests\TestCase;

class StudentAgendaTest extends TestCase
{
    use RefreshDatabase;

    private function admin(): User
    {
        SpatieRole::findOrCreate(Role::Admin->value, 'web');
        $user = User::factory()->create();
        $user->assignRole(Role::Admin->value);

        return $user;
    }

    public function test_agenda_splits_upcoming_and_past(): void
    {
        $student = Student::factory()->create();

        $futureSession = ScheduledSession::factory()->create([
            'starts_at' => now()->addDays(3)->setTime(9, 0),
            'ends_at' => now()->addDays(3)->setTime(10, 0),
        ]);
        Booking::factory()->create([
            'student_id' => $student->id,
            'scheduled_session_id' => $futureSession->id,
            'status' => BookingStatus::Booked,
        ]);

        Appointment::factory()->create([
            'student_id' => $student->id,
            'starts_at' => now()->subDays(5)->setTime(16, 0),
            'ends_at' => now()->subDays(5)->setTime(17, 0),
            'status' => AppointmentStatus::Completed,
        ]);

        $futureEvent = Event::factory()->create(['starts_at' => now()->addDays(10)]);
        EventRegistration::factory()->create([
            'student_id' => $student->id,
            'event_id' => $futureEvent->id,
            'status' => EventRegistrationStatus::Registered,
        ]);

        $agenda = app(StudentAgendaService::class)->for($student);

        $this->assertCount(2, $agenda['upcoming']); // future session + future event
        $this->assertCount(1, $agenda['past']);     // completed appointment
        $this->assertSame('Acompañamiento', $agenda['past'][0]['type']);
        // Upcoming is sorted soonest-first: the session (in 3 days) precedes the event (in 10).
        $this->assertSame('Sesión grupal', $agenda['upcoming'][0]['type']);
    }

    public function test_can_book_a_session_from_the_student_profile(): void
    {
        $this->seed(PlanSeeder::class);
        $student = Student::factory()->create();
        (new SellMembership)->execute($student, MembershipPlan::where('slug', 'citizen-pass')->firstOrFail());

        $activity = Activity::factory()->create(['type' => ActivityType::GroupClass]);
        $session = ScheduledSession::factory()->create([
            'activity_id' => $activity->id,
            'starts_at' => now()->addDay()->setTime(9, 0),
            'ends_at' => now()->addDay()->setTime(10, 0),
        ]);

        Livewire::actingAs($this->admin())->test(BookingsRelationManager::class, [
            'ownerRecord' => $student,
            'pageClass' => EditStudent::class,
        ])
            ->callTableAction('book', data: ['scheduled_session_id' => $session->id])
            ->assertHasNoTableActionErrors();

        $this->assertSame(1, $session->fresh()->seatsTaken());
        $this->assertSame(3, $student->currentMembership()->creditsRemaining());
    }

    public function test_can_book_an_appointment_from_the_student_profile(): void
    {
        $student = Student::factory()->create();
        $appointment = Appointment::factory()->create([
            'student_id' => null,
            'status' => AppointmentStatus::Available,
            'starts_at' => now()->addDay()->setTime(16, 0),
            'ends_at' => now()->addDay()->setTime(17, 0),
        ]);

        Livewire::actingAs($this->admin())->test(AppointmentsRelationManager::class, [
            'ownerRecord' => $student,
            'pageClass' => EditStudent::class,
        ])
            ->callTableAction('book', data: ['appointment_id' => $appointment->id])
            ->assertHasNoTableActionErrors();

        $appointment->refresh();
        $this->assertSame($student->id, $appointment->student_id);
        $this->assertSame(AppointmentStatus::Booked, $appointment->status);
    }

    public function test_can_register_for_an_event_from_the_student_profile(): void
    {
        $student = Student::factory()->create();
        $event = Event::factory()->create(['starts_at' => now()->addDays(7)]);

        Livewire::actingAs($this->admin())->test(EventRegistrationsRelationManager::class, [
            'ownerRecord' => $student,
            'pageClass' => EditStudent::class,
        ])
            ->callTableAction('register', data: ['event_id' => $event->id])
            ->assertHasNoTableActionErrors();

        $this->assertDatabaseHas('event_registrations', [
            'student_id' => $student->id,
            'event_id' => $event->id,
            'status' => EventRegistrationStatus::Registered->value,
        ]);
    }
}
