<?php

namespace Tests\Feature;

use App\Actions\Events\CancelEventRegistration;
use App\Actions\Events\MarkEventAttendance;
use App\Actions\Events\RegisterForEvent;
use App\Enums\EventRegistrationStatus;
use App\Enums\Role;
use App\Exceptions\EventException;
use App\Filament\Resources\Events\Pages\CreateEvent;
use App\Filament\Resources\Events\Pages\EditEvent;
use App\Filament\Resources\Events\Pages\ListEvents;
use App\Filament\Resources\Events\RelationManagers\RegistrationsRelationManager;
use App\Models\Event;
use App\Models\Student;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Spatie\Permission\Models\Role as SpatieRole;
use Tests\TestCase;

class EventTest extends TestCase
{
    use RefreshDatabase;

    private function admin(): User
    {
        SpatieRole::findOrCreate(Role::Admin->value, 'web');
        $user = User::factory()->create();
        $user->assignRole(Role::Admin->value);

        return $user;
    }

    public function test_registering_takes_a_seat(): void
    {
        $student = Student::factory()->create();
        $event = Event::factory()->create();

        $registration = app(RegisterForEvent::class)->execute($student, $event);

        $this->assertSame(EventRegistrationStatus::Registered, $registration->status);
        $this->assertSame(1, $event->fresh()->seatsTaken());
    }

    public function test_cannot_register_twice(): void
    {
        $student = Student::factory()->create();
        $event = Event::factory()->create();

        app(RegisterForEvent::class)->execute($student, $event);

        $this->expectException(EventException::class);
        $this->expectExceptionMessage('ya está inscripto');
        app(RegisterForEvent::class)->execute($student, $event);
    }

    public function test_cannot_exceed_capacity(): void
    {
        $event = Event::factory()->withCapacity(1)->create();
        $a = Student::factory()->create();
        $b = Student::factory()->create();

        app(RegisterForEvent::class)->execute($a, $event);

        $this->expectException(EventException::class);
        $this->expectExceptionMessage('completo');
        app(RegisterForEvent::class)->execute($b, $event);
    }

    public function test_unlimited_event_never_fills(): void
    {
        $event = Event::factory()->unlimited()->create();

        foreach (range(1, 5) as $i) {
            app(RegisterForEvent::class)->execute(Student::factory()->create(), $event);
        }

        $this->assertFalse($event->fresh()->isFull());
        $this->assertSame(5, $event->fresh()->seatsTaken());
    }

    public function test_cancelling_frees_the_seat(): void
    {
        $student = Student::factory()->create();
        $event = Event::factory()->create();
        $registration = app(RegisterForEvent::class)->execute($student, $event);

        app(CancelEventRegistration::class)->execute($registration);

        $this->assertSame(EventRegistrationStatus::Cancelled, $registration->fresh()->status);
        $this->assertSame(0, $event->fresh()->seatsTaken());
    }

    public function test_marking_attendance(): void
    {
        $student = Student::factory()->create();
        $event = Event::factory()->create();
        $registration = app(RegisterForEvent::class)->execute($student, $event);

        app(MarkEventAttendance::class)->execute($registration, attended: true);

        $this->assertSame(EventRegistrationStatus::Attended, $registration->fresh()->status);
    }

    public function test_admin_can_manage_events_and_roster(): void
    {
        $admin = $this->admin();
        $event = Event::factory()->create();
        $student = Student::factory()->create();

        Livewire::actingAs($admin)->test(ListEvents::class)->assertOk();
        Livewire::actingAs($admin)->test(CreateEvent::class)->assertOk();

        Livewire::actingAs($admin)->test(RegistrationsRelationManager::class, [
            'ownerRecord' => $event,
            'pageClass' => EditEvent::class,
        ])
            ->callTableAction('register', data: ['student_id' => $student->getKey()])
            ->assertHasNoTableActionErrors();

        $this->assertSame(1, $event->fresh()->seatsTaken());
    }
}
