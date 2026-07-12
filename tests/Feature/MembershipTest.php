<?php

namespace Tests\Feature;

use App\Actions\Memberships\AdjustMembershipCredits;
use App\Actions\Memberships\SellMembership;
use App\Enums\CreditMovementType;
use App\Enums\MembershipStatus;
use App\Enums\Role;
use App\Filament\Resources\Students\Pages\EditStudent;
use App\Filament\Resources\Students\RelationManagers\MembershipsRelationManager;
use App\Models\MembershipPlan;
use App\Models\Student;
use App\Models\StudentMembership;
use App\Models\User;
use Database\Seeders\PlanSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use InvalidArgumentException;
use Livewire\Livewire;
use Spatie\Permission\Models\Role as SpatieRole;
use Tests\TestCase;

class MembershipTest extends TestCase
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

    public function test_selling_a_pass_snapshots_the_plan_and_seeds_credits(): void
    {
        $student = Student::factory()->create();
        $plan = $this->plan('community-pass'); // 12 créditos, 30 días, 400.000

        $membership = (new SellMembership)->execute($student, $plan);

        $this->assertSame(12, $membership->credits_total);
        $this->assertFalse($membership->is_unlimited);
        $this->assertSame(400000, $membership->getRawOriginal('price_paid'));
        $this->assertSame('PYG', $membership->currency_code);
        $this->assertSame(MembershipStatus::Active, $membership->status);
        $this->assertEquals(
            $membership->starts_at->copy()->addDays(30)->toDateString(),
            $membership->ends_at->toDateString(),
        );

        // Ledger seeded with a single sale movement = full balance.
        $this->assertSame(12, $membership->creditsRemaining());
        $this->assertSame(1, $membership->movements()->count());
        $this->assertSame(
            CreditMovementType::Sale,
            $membership->movements()->first()->type,
        );
        $this->assertTrue($membership->hasAvailableCredit());
    }

    public function test_unlimited_membership_grants_no_credit_movements(): void
    {
        $student = Student::factory()->create();
        $plan = $this->plan('republic-membership'); // ilimitada

        $membership = (new SellMembership)->execute($student, $plan);

        $this->assertTrue($membership->is_unlimited);
        $this->assertNull($membership->credits_total);
        $this->assertNull($membership->creditsRemaining()); // infinito
        $this->assertSame(0, $membership->movements()->count());
        $this->assertTrue($membership->hasAvailableCredit());
    }

    public function test_manual_adjustment_records_a_ledger_movement(): void
    {
        $student = Student::factory()->create();
        $membership = (new SellMembership)->execute($student, $this->plan('citizen-pass')); // 4

        (new AdjustMembershipCredits)->execute($membership, 2, 'Cortesía por inconveniente');

        $this->assertSame(6, $membership->fresh()->creditsRemaining());

        (new AdjustMembershipCredits)->execute($membership, -1, 'Corrección de carga');
        $this->assertSame(5, $membership->fresh()->creditsRemaining());
    }

    public function test_manual_adjustment_rejects_zero_and_empty_reason(): void
    {
        $student = Student::factory()->create();
        $membership = (new SellMembership)->execute($student, $this->plan('citizen-pass'));

        $this->expectException(InvalidArgumentException::class);
        (new AdjustMembershipCredits)->execute($membership, 0, 'nope');
    }

    public function test_current_membership_ignores_expired_ones(): void
    {
        $student = Student::factory()->create();
        StudentMembership::factory()->expired()->for($student)->create();

        $this->assertNull($student->currentMembership());

        $active = StudentMembership::factory()->for($student)->create();
        $this->assertTrue($student->currentMembership()?->is($active));
    }

    public function test_expire_command_marks_past_memberships(): void
    {
        $student = Student::factory()->create();
        // active status but past end date → should flip to expired.
        StudentMembership::factory()->expired()->for($student)->create([
            'status' => MembershipStatus::Active,
        ]);
        $stillValid = StudentMembership::factory()->for($student)->create();

        $this->artisan('memberships:expire')->assertSuccessful();

        $this->assertSame(MembershipStatus::Expired, $student->memberships()->whereKeyNot($stillValid)->first()->status);
        $this->assertSame(MembershipStatus::Active, $stillValid->fresh()->status);
    }

    public function test_membership_relation_manager_renders_with_balance(): void
    {
        $student = Student::factory()->create();
        StudentMembership::factory()->for($student)->create();

        Livewire::actingAs($this->admin())->test(MembershipsRelationManager::class, [
            'ownerRecord' => $student,
            'pageClass' => EditStudent::class,
        ])->assertOk();
    }

    public function test_selling_a_pass_from_the_relation_manager(): void
    {
        $student = Student::factory()->create();
        $plan = $this->plan('citizen-pass'); // 4 créditos

        Livewire::actingAs($this->admin())->test(MembershipsRelationManager::class, [
            'ownerRecord' => $student,
            'pageClass' => EditStudent::class,
        ])
            ->callTableAction('sell', data: [
                'membership_plan_id' => $plan->getKey(),
                'starts_at' => now()->toDateString(),
            ])
            ->assertHasNoTableActionErrors();

        $this->assertSame(4, $student->currentMembership()?->creditsRemaining());
    }
}
