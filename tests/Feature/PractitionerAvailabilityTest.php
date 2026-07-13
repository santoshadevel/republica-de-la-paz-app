<?php

namespace Tests\Feature;

use App\Enums\ActivityType;
use App\Enums\Role;
use App\Enums\Weekday;
use App\Filament\Resources\Practitioners\Pages\EditPractitioner;
use App\Filament\Resources\Practitioners\RelationManagers\AvailabilityRelationManager;
use App\Models\Activity;
use App\Models\Practitioner;
use App\Models\PractitionerAvailability;
use App\Models\PractitionerAvailabilityException;
use App\Models\User;
use App\Services\Scheduling\SchedulingService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Livewire\Livewire;
use Spatie\Permission\Models\Role as SpatieRole;
use Tests\TestCase;

class PractitionerAvailabilityTest extends TestCase
{
    use RefreshDatabase;

    private function at(string $datetime): Carbon
    {
        return Carbon::parse($datetime);
    }

    private function admin(): User
    {
        SpatieRole::findOrCreate(Role::Admin->value, 'web');
        $user = User::factory()->create();
        $user->assignRole(Role::Admin->value);

        return $user;
    }

    public function test_admin_can_add_a_weekly_availability_block(): void
    {
        $practitioner = Practitioner::factory()->create();

        Livewire::actingAs($this->admin())->test(AvailabilityRelationManager::class, [
            'ownerRecord' => $practitioner,
            'pageClass' => EditPractitioner::class,
        ])
            ->callTableAction('create', data: [
                'day_of_week' => Weekday::Monday->value,
                'start_time' => '09:00',
                'end_time' => '13:00',
            ])
            ->assertHasNoTableActionErrors();

        $this->assertDatabaseHas('practitioner_availabilities', [
            'practitioner_id' => $practitioner->id,
            'day_of_week' => Weekday::Monday->value,
        ]);
    }

    public function test_practitioner_without_schedule_is_unconstrained(): void
    {
        $practitioner = Practitioner::factory()->create();

        $this->assertTrue($practitioner->isAvailableAt($this->at('2026-08-03 10:00'), $this->at('2026-08-03 11:00')));
    }

    public function test_weekly_block_defines_availability(): void
    {
        $practitioner = Practitioner::factory()->create();
        PractitionerAvailability::factory()->for($practitioner)->create([
            'day_of_week' => Weekday::Monday,
            'start_time' => '09:00',
            'end_time' => '13:00',
        ]);

        // 2026-08-03 is a Monday.
        $this->assertTrue($practitioner->isAvailableAt($this->at('2026-08-03 10:00'), $this->at('2026-08-03 11:00')));
        // Outside the block.
        $this->assertFalse($practitioner->isAvailableAt($this->at('2026-08-03 14:00'), $this->at('2026-08-03 15:00')));
        // Wrong weekday (Tuesday).
        $this->assertFalse($practitioner->isAvailableAt($this->at('2026-08-04 10:00'), $this->at('2026-08-04 11:00')));
        // Range spilling past the closing time (ends 14:00 > 13:00).
        $this->assertFalse($practitioner->isAvailableAt($this->at('2026-08-03 12:00'), $this->at('2026-08-03 14:00')));
    }

    public function test_closed_exception_overrides_the_weekly_block(): void
    {
        $practitioner = Practitioner::factory()->create();
        PractitionerAvailability::factory()->for($practitioner)->create([
            'day_of_week' => Weekday::Monday,
            'start_time' => '09:00',
            'end_time' => '13:00',
        ]);
        PractitionerAvailabilityException::factory()->for($practitioner)->create([
            'date' => '2026-08-03', // that Monday, closed
            'is_available' => false,
        ]);

        $this->assertFalse($practitioner->isAvailableAt($this->at('2026-08-03 10:00'), $this->at('2026-08-03 11:00')));
    }

    public function test_special_hours_exception_opens_an_otherwise_closed_day(): void
    {
        $practitioner = Practitioner::factory()->create();
        // No weekly block on Wednesday; a special-hours exception opens it.
        PractitionerAvailabilityException::factory()->for($practitioner)->specialHours('10:00', '14:00')->create([
            'date' => '2026-08-05', // Wednesday
        ]);

        $this->assertTrue($practitioner->isAvailableAt($this->at('2026-08-05 11:00'), $this->at('2026-08-05 12:00')));
        $this->assertFalse($practitioner->isAvailableAt($this->at('2026-08-05 15:00'), $this->at('2026-08-05 16:00')));
    }

    public function test_recurring_generation_skips_slots_outside_availability(): void
    {
        $practitioner = Practitioner::factory()->create();
        PractitionerAvailability::factory()->for($practitioner)->create([
            'day_of_week' => Weekday::Monday, // available Mondays only
            'start_time' => '09:00',
            'end_time' => '13:00',
        ]);
        $activity = Activity::factory()->create(['type' => ActivityType::GroupClass]);

        // Mon+Wed over 2026-08-03..12 → Mondays 03,10 (available) + Wednesdays 05,12 (not).
        $result = app(SchedulingService::class)->generateRecurringSessions([
            'activity_id' => $activity->id,
            'practitioner_id' => $practitioner->id,
            'weekdays' => [1, 3],
            'start_time' => '09:00',
            'end_time' => '10:00',
            'from' => '2026-08-03',
            'to' => '2026-08-12',
            'capacity' => 10,
            'skip_past' => false,
        ]);

        $this->assertSame(2, $result['created']); // only the Mondays
        $this->assertCount(2, $result['conflicts']);
        $this->assertStringContainsString('no está disponible', $result['conflicts'][0]['reason']);
    }
}
