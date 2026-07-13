<?php

namespace Tests\Feature;

use App\Enums\ActivityType;
use App\Enums\Role;
use App\Enums\SessionStatus;
use App\Filament\Resources\ScheduledSessions\Pages\ListScheduledSessions;
use App\Models\Activity;
use App\Models\Practitioner;
use App\Models\Room;
use App\Models\ScheduledSession;
use App\Models\User;
use App\Services\Scheduling\SchedulingService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use InvalidArgumentException;
use Livewire\Livewire;
use Spatie\Permission\Models\Role as SpatieRole;
use Tests\TestCase;

class RecurringSessionTest extends TestCase
{
    use RefreshDatabase;

    private function service(): SchedulingService
    {
        return app(SchedulingService::class);
    }

    private function admin(): User
    {
        SpatieRole::findOrCreate(Role::Admin->value, 'web');
        $user = User::factory()->create();
        $user->assignRole(Role::Admin->value);

        return $user;
    }

    /**
     * Base intent: Mon+Wed, 09:00–10:00, 2026-08-03 (Mon) → 2026-08-16.
     * That range holds Mondays 03 & 10 and Wednesdays 05 & 12 → 4 occurrences.
     *
     * @param  array<string, mixed>  $overrides
     * @return array<string, mixed>
     */
    private function intent(array $overrides = []): array
    {
        $activity = Activity::factory()->create(['type' => ActivityType::GroupClass]);

        return array_merge([
            'activity_id' => $activity->id,
            'practitioner_id' => null,
            'room_id' => null,
            'weekdays' => [1, 3],
            'start_time' => '09:00',
            'end_time' => '10:00',
            'from' => '2026-08-03',
            'to' => '2026-08-16',
            'capacity' => 10,
            'status' => SessionStatus::Scheduled->value,
            'skip_past' => false,
        ], $overrides);
    }

    public function test_generates_one_session_per_matching_weekday(): void
    {
        $result = $this->service()->generateRecurringSessions($this->intent());

        $this->assertSame(4, $result['created']);
        $this->assertSame(0, $result['skipped']);
        $this->assertSame(4, ScheduledSession::count());

        $session = ScheduledSession::orderBy('starts_at')->first();
        $this->assertSame('2026-08-03 09:00:00', $session->starts_at->toDateTimeString());
        $this->assertSame('2026-08-03 10:00:00', $session->ends_at->toDateTimeString());
    }

    public function test_only_the_selected_weekdays_are_created(): void
    {
        $result = $this->service()->generateRecurringSessions($this->intent(['weekdays' => [1]]));

        $this->assertSame(2, $result['created']); // Mondays 03 & 10 only

        $weekdays = ScheduledSession::pluck('starts_at')->map->dayOfWeekIso->unique()->values();
        $this->assertEquals([1], $weekdays->all()); // every occurrence falls on a Monday
    }

    public function test_re_running_the_same_range_is_idempotent(): void
    {
        $intent = $this->intent();
        $this->service()->generateRecurringSessions($intent);

        $second = $this->service()->generateRecurringSessions($intent);

        $this->assertSame(0, $second['created']);
        $this->assertSame(4, $second['skipped']);
        $this->assertSame(4, ScheduledSession::count());
    }

    public function test_skips_and_reports_a_room_conflict(): void
    {
        $room = Room::factory()->create();
        $activity = Activity::factory()->create(['type' => ActivityType::GroupClass]);

        // Existing session occupying the room on Mon 2026-08-03 09:30–10:30.
        ScheduledSession::factory()->create([
            'room_id' => $room->id,
            'starts_at' => '2026-08-03 09:30:00',
            'ends_at' => '2026-08-03 10:30:00',
        ]);

        $result = $this->service()->generateRecurringSessions($this->intent([
            'activity_id' => $activity->id,
            'room_id' => $room->id,
        ]));

        $this->assertSame(3, $result['created']); // 4 slots − 1 clash
        $this->assertCount(1, $result['conflicts']);
        $this->assertStringContainsString('sala', $result['conflicts'][0]['reason']);
    }

    public function test_skips_and_reports_a_practitioner_conflict(): void
    {
        $practitioner = Practitioner::factory()->create();
        $activity = Activity::factory()->create(['type' => ActivityType::GroupClass]);

        ScheduledSession::factory()->create([
            'practitioner_id' => $practitioner->id,
            'room_id' => null,
            'starts_at' => '2026-08-05 09:30:00', // Wed
            'ends_at' => '2026-08-05 10:30:00',
        ]);

        $result = $this->service()->generateRecurringSessions($this->intent([
            'activity_id' => $activity->id,
            'practitioner_id' => $practitioner->id,
        ]));

        $this->assertSame(3, $result['created']);
        $this->assertStringContainsString('profesional', $result['conflicts'][0]['reason']);
    }

    public function test_skip_past_omits_dates_before_now(): void
    {
        // 2026-01 is in the past relative to the app clock; nothing should be created.
        $withSkip = $this->service()->generateRecurringSessions($this->intent([
            'from' => '2026-01-05', // Monday
            'to' => '2026-01-05',
            'weekdays' => [1],
            'skip_past' => true,
        ]));

        $this->assertSame(0, $withSkip['created']);
        $this->assertSame(1, $withSkip['skipped']);

        $withoutSkip = $this->service()->generateRecurringSessions($this->intent([
            'from' => '2026-01-05',
            'to' => '2026-01-05',
            'weekdays' => [1],
            'skip_past' => false,
        ]));

        $this->assertSame(1, $withoutSkip['created']);
    }

    public function test_end_time_before_start_time_throws(): void
    {
        $this->expectException(InvalidArgumentException::class);

        $this->service()->generateRecurringSessions($this->intent([
            'start_time' => '10:00',
            'end_time' => '09:00',
        ]));
    }

    public function test_admin_can_generate_recurring_sessions_from_the_list_page(): void
    {
        $activity = Activity::factory()->create(['type' => ActivityType::GroupClass]);

        Livewire::actingAs($this->admin())->test(ListScheduledSessions::class)
            ->callAction('generateRecurring', data: [
                'activity_id' => $activity->id,
                'weekdays' => [1, 3],
                'start_time' => '09:00',
                'end_time' => '10:00',
                'from' => '2026-08-03',
                'to' => '2026-08-16',
                'capacity' => 10,
                'status' => SessionStatus::Scheduled->value,
                'skip_past' => false,
            ])
            ->assertHasNoActionErrors();

        $this->assertSame(4, ScheduledSession::count());
    }
}
