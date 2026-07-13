<?php

namespace Tests\Feature;

use App\Actions\Accounting\RecordTransaction;
use App\Actions\Bookings\BookSession;
use App\Actions\Memberships\SellMembership;
use App\Enums\ActivityType;
use App\Enums\Role;
use App\Enums\TransactionType;
use App\Filament\Widgets\BusinessState;
use App\Filament\Widgets\CommunityStats;
use App\Filament\Widgets\ExpiringMemberships;
use App\Filament\Widgets\TodayOverview;
use App\Models\Activity;
use App\Models\MembershipPlan;
use App\Models\PaymentMethod;
use App\Models\ScheduledSession;
use App\Models\Student;
use App\Models\StudentMembership;
use App\Models\User;
use App\Services\Reporting\ReportService;
use App\Support\Money;
use Database\Seeders\AccountingCatalogSeeder;
use Database\Seeders\PlanSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Spatie\Permission\Models\Role as SpatieRole;
use Tests\TestCase;

class DashboardTest extends TestCase
{
    use RefreshDatabase;

    private function admin(): User
    {
        SpatieRole::findOrCreate(Role::Admin->value, 'web');
        $user = User::factory()->create();
        $user->assignRole(Role::Admin->value);

        return $user;
    }

    public function test_today_summary_counts_sessions_bookings_and_income(): void
    {
        $this->seed(PlanSeeder::class);
        $this->seed(AccountingCatalogSeeder::class);

        $student = Student::factory()->create();
        $plan = MembershipPlan::where('slug', 'community-pass')->firstOrFail(); // 400.000
        $method = PaymentMethod::where('name', 'Efectivo')->firstOrFail();
        app(SellMembership::class)->execute($student, $plan, null, $method); // income today

        $activity = Activity::factory()->create(['type' => ActivityType::GroupClass]);
        $session = ScheduledSession::factory()->create([
            'activity_id' => $activity->id,
            'starts_at' => now()->addHours(2),
            'ends_at' => now()->addHours(3),
        ]);
        app(BookSession::class)->execute($student, $session);

        $summary = app(ReportService::class)->todaySummary();

        $this->assertSame(1, $summary['group_sessions']);
        $this->assertSame(1, $summary['scheduled_students']);
        $this->assertSame(400000, $summary['income']->minorAmount);
        $this->assertSame(400000, $summary['balance']->minorAmount);
    }

    public function test_monthly_business_state_computes_result_and_margin(): void
    {
        $record = app(RecordTransaction::class);
        $record->execute(TransactionType::Income, Money::ofMinor(1_000_000));
        $record->execute(TransactionType::Expense, Money::ofMinor(400_000));

        $state = app(ReportService::class)->monthlyBusinessState();

        $this->assertSame(1_000_000, $state['income']->minorAmount);
        $this->assertSame(400_000, $state['expense']->minorAmount);
        $this->assertSame(600_000, $state['result']->minorAmount);
        $this->assertSame(60.0, $state['margin']);
    }

    public function test_expiring_memberships_are_detected(): void
    {
        $student = Student::factory()->create();
        StudentMembership::factory()->for($student)->create([
            'starts_at' => now()->subDays(27),
            'ends_at' => now()->addDays(3), // vence en 3 días
        ]);
        StudentMembership::factory()->create([
            'ends_at' => now()->addDays(20), // lejos
        ]);

        $this->assertSame(1, app(ReportService::class)->expiringMemberships()->count());
        $this->assertSame(1, app(ReportService::class)->communityStats()['expiring_soon']);
    }

    public function test_dashboard_widgets_render(): void
    {
        $admin = $this->admin();

        Livewire::actingAs($admin)->test(TodayOverview::class)->assertOk();
        Livewire::actingAs($admin)->test(BusinessState::class)->assertOk();
        Livewire::actingAs($admin)->test(CommunityStats::class)->assertOk();
        Livewire::actingAs($admin)->test(ExpiringMemberships::class)->assertOk();
    }
}
