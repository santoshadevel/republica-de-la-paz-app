<?php

namespace Tests\Feature;

use App\Actions\Appointments\BookAppointment;
use App\Actions\Appointments\CancelAppointment;
use App\Actions\Appointments\CompleteAppointment;
use App\Enums\AppointmentStatus;
use App\Enums\Role;
use App\Exceptions\AppointmentException;
use App\Filament\Resources\Appointments\Pages\CreateAppointment;
use App\Filament\Resources\Appointments\Pages\ListAppointments;
use App\Models\Appointment;
use App\Models\Student;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Spatie\Permission\Models\Role as SpatieRole;
use Tests\TestCase;

class AppointmentTest extends TestCase
{
    use RefreshDatabase;

    private function admin(): User
    {
        SpatieRole::findOrCreate(Role::Admin->value, 'web');
        $user = User::factory()->create();
        $user->assignRole(Role::Admin->value);

        return $user;
    }

    public function test_booking_an_available_slot_assigns_the_student(): void
    {
        $student = Student::factory()->create();
        $slot = Appointment::factory()->create();

        $booked = app(BookAppointment::class)->execute($student, $slot);

        $this->assertSame(AppointmentStatus::Booked, $booked->status);
        $this->assertTrue($booked->student->is($student));
    }

    public function test_cannot_book_a_slot_that_is_not_available(): void
    {
        $student = Student::factory()->create();
        $slot = Appointment::factory()->booked()->create();

        $this->expectException(AppointmentException::class);
        app(BookAppointment::class)->execute($student, $slot);
    }

    public function test_early_cancellation_charges_no_fee(): void
    {
        // Booked slot two days out → cancelling now is on time.
        $slot = Appointment::factory()->booked()->create(['price' => 150000]);

        app(CancelAppointment::class)->execute($slot);

        $slot->refresh();
        $this->assertSame(AppointmentStatus::Cancelled, $slot->status);
        $this->assertNull($slot->cancellation_fee);
    }

    public function test_late_cancellation_charges_fifty_percent(): void
    {
        // Booked slot in 2 hours → inside the 24h window → 50% fee.
        $slot = Appointment::factory()->booked()->startingSoon()->create(['price' => 150000]);

        app(CancelAppointment::class)->execute($slot);

        $slot->refresh();
        $this->assertSame(AppointmentStatus::Cancelled, $slot->status);
        $this->assertSame(75000, $slot->cancellation_fee?->minorAmount);
    }

    public function test_completing_a_booked_appointment(): void
    {
        $slot = Appointment::factory()->booked()->create();

        app(CompleteAppointment::class)->execute($slot);

        $this->assertSame(AppointmentStatus::Completed, $slot->fresh()->status);
    }

    public function test_admin_can_list_and_create_appointments(): void
    {
        Appointment::factory()->count(2)->create();
        $admin = $this->admin();

        Livewire::actingAs($admin)->test(ListAppointments::class)->assertOk();
        Livewire::actingAs($admin)->test(CreateAppointment::class)->assertOk();
    }
}
