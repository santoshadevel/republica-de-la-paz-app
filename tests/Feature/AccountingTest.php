<?php

namespace Tests\Feature;

use App\Actions\Accounting\RecordTransaction;
use App\Actions\Memberships\SellMembership;
use App\Enums\Role;
use App\Enums\TransactionType;
use App\Filament\Resources\Transactions\Pages\CreateTransaction;
use App\Filament\Resources\Transactions\Pages\ListTransactions;
use App\Models\Category;
use App\Models\MembershipPlan;
use App\Models\PaymentMethod;
use App\Models\Student;
use App\Models\StudentMembership;
use App\Models\Transaction;
use App\Models\User;
use App\Support\Money;
use Database\Seeders\AccountingCatalogSeeder;
use Database\Seeders\PlanSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Spatie\Permission\Models\Role as SpatieRole;
use Tests\TestCase;

class AccountingTest extends TestCase
{
    use RefreshDatabase;

    private function admin(): User
    {
        SpatieRole::findOrCreate(Role::Admin->value, 'web');
        $user = User::factory()->create();
        $user->assignRole(Role::Admin->value);

        return $user;
    }

    public function test_record_transaction_creates_a_movement(): void
    {
        $expense = app(RecordTransaction::class)->execute(
            type: TransactionType::Expense,
            amount: Money::ofMinor(2_500_000),
            attributes: ['description' => 'Alquiler del local'],
        );

        $this->assertSame(TransactionType::Expense, $expense->type);
        $this->assertSame(2_500_000, $expense->getRawOriginal('amount'));
        $this->assertSame('Alquiler del local', $expense->description);
    }

    public function test_accounting_catalog_seeds_income_and_expense_trees(): void
    {
        $this->seed(AccountingCatalogSeeder::class);

        $honorarios = Category::expense()->whereNull('parent_id')->where('name', 'Honorarios')->firstOrFail();
        $this->assertGreaterThanOrEqual(5, $honorarios->children()->count());

        $sub = $honorarios->children()->where('name', 'Profesores')->firstOrFail();
        $this->assertSame('Honorarios › Profesores', $sub->fullName());
    }

    public function test_selling_a_membership_with_a_payment_method_records_income(): void
    {
        $this->seed(PlanSeeder::class);
        $this->seed(AccountingCatalogSeeder::class);

        $student = Student::factory()->create();
        $plan = MembershipPlan::where('slug', 'community-pass')->firstOrFail(); // 400.000
        $method = PaymentMethod::where('name', 'Efectivo')->firstOrFail();

        app(SellMembership::class)->execute($student, $plan, null, $method);

        $this->assertSame(1, Transaction::income()->count());
        $transaction = Transaction::income()->first();
        $this->assertSame(400000, $transaction->getRawOriginal('amount'));
        $this->assertTrue($transaction->source instanceof StudentMembership);
        $this->assertSame('Membresías', $transaction->category?->parent?->name);
    }

    public function test_selling_without_a_payment_method_records_no_income(): void
    {
        $this->seed(PlanSeeder::class);
        $student = Student::factory()->create();
        $plan = MembershipPlan::where('slug', 'community-pass')->firstOrFail();

        app(SellMembership::class)->execute($student, $plan);

        $this->assertSame(0, Transaction::count());
    }

    public function test_admin_can_list_and_create_transactions(): void
    {
        $this->seed(AccountingCatalogSeeder::class);
        Transaction::factory()->count(2)->create();
        $admin = $this->admin();

        Livewire::actingAs($admin)->test(ListTransactions::class)->assertOk();
        Livewire::actingAs($admin)->test(CreateTransaction::class)->assertOk();
    }
}
