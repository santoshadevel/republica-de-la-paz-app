<?php

namespace Tests\Feature;

use App\Actions\Memberships\ApproveMembershipOrder;
use App\Actions\Memberships\RejectMembershipOrder;
use App\Enums\MembershipOrderStatus;
use App\Enums\Role;
use App\Filament\Resources\MembershipOrders\Pages\ListMembershipOrders;
use App\Livewire\Portal\Plans;
use App\Models\MembershipOrder;
use App\Models\MembershipPlan;
use App\Models\Student;
use App\Models\User;
use Database\Seeders\PlanSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Spatie\Permission\Models\Role as SpatieRole;
use Tests\TestCase;

class MembershipOrderTest extends TestCase
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

    private function admin(): User
    {
        $user = User::factory()->create();
        $user->assignRole(Role::Admin->value);

        return $user;
    }

    private function studentUser(): array
    {
        $user = User::registerStudent('Zoe G', 'zoe@example.com', 'password123');
        $student = Student::registerFrom($user, 'Zoe G');

        return [$user, $student];
    }

    private function plan(string $slug = 'citizen-pass'): MembershipPlan
    {
        return MembershipPlan::where('slug', $slug)->firstOrFail();
    }

    public function test_student_can_request_a_plan_from_the_portal(): void
    {
        [$user, $student] = $this->studentUser();

        Livewire::actingAs($user)->test(Plans::class)
            ->call('requestPlan', $this->plan()->id);

        $this->assertDatabaseHas('membership_orders', [
            'student_id' => $student->id,
            'membership_plan_id' => $this->plan()->id,
            'status' => MembershipOrderStatus::Pending->value,
        ]);
    }

    public function test_duplicate_pending_request_is_prevented(): void
    {
        [$user, $student] = $this->studentUser();

        Livewire::actingAs($user)->test(Plans::class)
            ->call('requestPlan', $this->plan()->id)
            ->call('requestPlan', $this->plan()->id);

        $this->assertSame(1, MembershipOrder::where('student_id', $student->id)->count());
    }

    public function test_the_catalog_marks_the_passes_already_awaiting_review(): void
    {
        [$user] = $this->studentUser();

        // Nothing requested yet: every pass is offered.
        Livewire::actingAs($user)->test(Plans::class)
            ->assertSee('Solicitar este pase')
            ->assertDontSee('Ya tenés una solicitud pendiente para este pase');

        Livewire::actingAs($user)->test(Plans::class)
            ->call('requestPlan', $this->plan()->id)
            ->assertSee('Ya tenés una solicitud pendiente para este pase');
    }

    public function test_approving_an_order_sells_the_membership(): void
    {
        [, $student] = $this->studentUser();
        $order = MembershipOrder::place($student, $this->plan()); // citizen-pass = 4 credits

        app(ApproveMembershipOrder::class)->execute($order, $this->admin());

        $order->refresh();
        $this->assertSame(MembershipOrderStatus::Approved, $order->status);
        $this->assertNotNull($order->student_membership_id);
        $this->assertSame(4, $student->fresh()->currentMembership()->creditsRemaining());
    }

    public function test_rejecting_an_order_leaves_no_membership(): void
    {
        [, $student] = $this->studentUser();
        $order = MembershipOrder::place($student, $this->plan());

        app(RejectMembershipOrder::class)->execute($order, $this->admin());

        $this->assertSame(MembershipOrderStatus::Rejected, $order->fresh()->status);
        $this->assertNull($student->fresh()->currentMembership());
    }

    public function test_admin_can_approve_from_the_filament_table(): void
    {
        [, $student] = $this->studentUser();
        $order = MembershipOrder::place($student, $this->plan());

        Livewire::actingAs($this->admin())->test(ListMembershipOrders::class)
            ->callTableAction('approve', $order, data: [])
            ->assertHasNoTableActionErrors();

        $this->assertSame(MembershipOrderStatus::Approved, $order->fresh()->status);
        $this->assertNotNull($student->fresh()->currentMembership());
    }

    public function test_approving_twice_is_rejected(): void
    {
        [, $student] = $this->studentUser();
        $order = MembershipOrder::place($student, $this->plan());
        app(ApproveMembershipOrder::class)->execute($order, $this->admin());

        $this->expectException(\RuntimeException::class);
        app(ApproveMembershipOrder::class)->execute($order->fresh(), $this->admin());
    }

    public function test_student_can_withdraw_their_own_pending_request(): void
    {
        [$user, $student] = $this->studentUser();
        $order = MembershipOrder::place($student, $this->plan());

        Livewire::actingAs($user)->test(Plans::class)
            ->call('cancelOrder', $order->id);

        $this->assertSame(MembershipOrderStatus::Cancelled, $order->fresh()->status);

        // ...and the plan is requestable again afterwards.
        Livewire::actingAs($user)->test(Plans::class)
            ->call('requestPlan', $this->plan()->id);

        $this->assertSame(2, MembershipOrder::where('student_id', $student->id)->count());
    }

    public function test_an_already_approved_request_cannot_be_withdrawn(): void
    {
        [$user, $student] = $this->studentUser();
        $order = MembershipOrder::place($student, $this->plan());
        app(ApproveMembershipOrder::class)->execute($order, $this->admin());

        Livewire::actingAs($user)->test(Plans::class)
            ->call('cancelOrder', $order->id);

        $this->assertSame(MembershipOrderStatus::Approved, $order->fresh()->status);
        $this->assertNotNull($student->fresh()->currentMembership());
    }

    public function test_a_student_cannot_withdraw_someone_elses_request(): void
    {
        [$user] = $this->studentUser();

        $otherUser = User::registerStudent('Ana P', 'ana@example.com', 'password123');
        $other = Student::registerFrom($otherUser, 'Ana P');
        $order = MembershipOrder::place($other, $this->plan());

        Livewire::actingAs($user)->test(Plans::class)
            ->call('cancelOrder', $order->id);

        $this->assertSame(MembershipOrderStatus::Pending, $order->fresh()->status);
    }
}
