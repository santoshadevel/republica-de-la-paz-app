<?php

namespace Tests\Feature;

use App\Enums\ActivityType;
use App\Enums\AppointmentStatus;
use App\Enums\FeeType;
use App\Enums\Role;
use App\Filament\Pages\HonorariumLiquidation;
use App\Models\Activity;
use App\Models\Appointment;
use App\Models\FeeScheme;
use App\Models\Practitioner;
use App\Models\ScheduledSession;
use App\Models\User;
use App\Services\Reporting\HonorariumService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Spatie\Permission\Models\Role as SpatieRole;
use Tests\TestCase;

class HonorariumTest extends TestCase
{
    use RefreshDatabase;

    private function admin(): User
    {
        SpatieRole::findOrCreate(Role::Admin->value, 'web');
        $user = User::factory()->create();
        $user->assignRole(Role::Admin->value);

        return $user;
    }

    public function test_liquidation_uses_fixed_and_percentage_schemes(): void
    {
        $practitioner = Practitioner::factory()->create();
        $yoga = Activity::factory()->create(['type' => ActivityType::GroupClass, 'name' => 'Hatha']);
        $tarot = Activity::factory()->create(['type' => ActivityType::IndividualSession, 'name' => 'Tarot']);

        // Default: 80.000 fijo por sesión; Tarot: 80% del precio.
        FeeScheme::create([
            'practitioner_id' => $practitioner->id,
            'activity_id' => null,
            'type' => FeeType::FixedPerSession,
            'fixed_amount' => 80_000,
        ]);
        FeeScheme::create([
            'practitioner_id' => $practitioner->id,
            'activity_id' => $tarot->id,
            'type' => FeeType::Percentage,
            'percentage' => 80,
        ]);

        // 2 group classes this month → 2 × 80.000 = 160.000 (fijo por defecto).
        ScheduledSession::factory()->count(2)->create([
            'practitioner_id' => $practitioner->id,
            'activity_id' => $yoga->id,
            'starts_at' => now()->startOfMonth()->addDays(2),
            'ends_at' => now()->startOfMonth()->addDays(2)->addHour(),
        ]);

        // 1 tarot session, price 200.000 → 80% = 160.000.
        Appointment::factory()->create([
            'practitioner_id' => $practitioner->id,
            'activity_id' => $tarot->id,
            'status' => AppointmentStatus::Completed,
            'price' => 200_000,
            'starts_at' => now()->startOfMonth()->addDays(3),
            'ends_at' => now()->startOfMonth()->addDays(3)->addHour(),
        ]);

        $result = app(HonorariumService::class)->liquidate($practitioner);

        $this->assertSame(2, $result['group_sessions']);
        $this->assertSame(1, $result['individual_sessions']);
        $this->assertSame(200_000, $result['income_generated']->minorAmount);
        // 160.000 (clases) + 160.000 (tarot 80%) = 320.000
        $this->assertSame(320_000, $result['fee_total']->minorAmount);
    }

    public function test_practitioner_without_schemes_is_excluded(): void
    {
        Practitioner::factory()->create(); // sin esquemas

        $this->assertCount(0, app(HonorariumService::class)->liquidateAll());
    }

    public function test_liquidation_page_renders(): void
    {
        Livewire::actingAs($this->admin())->test(HonorariumLiquidation::class)->assertOk();
    }
}
