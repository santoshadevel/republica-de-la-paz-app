<?php

namespace Tests\Feature;

use App\Actions\Accounting\RecordTransaction;
use App\Actions\Accounting\RecordTransfer;
use App\Enums\Role;
use App\Enums\TransactionType;
use App\Filament\Resources\Accounts\Pages\ManageAccounts;
use App\Filament\Resources\Transfers\Pages\ManageTransfers;
use App\Models\Account;
use App\Models\PaymentMethod;
use App\Models\Transaction;
use App\Models\User;
use App\Support\Money;
use Illuminate\Foundation\Testing\RefreshDatabase;
use InvalidArgumentException;
use Livewire\Livewire;
use Spatie\Permission\Models\Role as SpatieRole;
use Tests\TestCase;

class AccountTest extends TestCase
{
    use RefreshDatabase;

    private function admin(): User
    {
        SpatieRole::findOrCreate(Role::Admin->value, 'web');
        $user = User::factory()->create();
        $user->assignRole(Role::Admin->value);

        return $user;
    }

    public function test_balance_reflects_income_expense_and_transfers(): void
    {
        $account = Account::factory()->create(['opening_balance' => 1_000_000]);
        $other = Account::factory()->create();
        $record = app(RecordTransaction::class);

        $record->execute(TransactionType::Income, Money::ofMinor(500_000), account: $account);
        $record->execute(TransactionType::Expense, Money::ofMinor(200_000), account: $account);
        app(RecordTransfer::class)->execute($account, $other, Money::ofMinor(300_000));

        // 1.000.000 + 500.000 - 200.000 - 300.000 (out) = 1.000.000
        $this->assertSame(1_000_000, $account->balance()->minorAmount);
        // other only receives the transfer in.
        $this->assertSame(300_000, $other->balance()->minorAmount);
    }

    public function test_transfer_generates_two_ledger_transactions(): void
    {
        $from = Account::factory()->create();
        $to = Account::factory()->create();

        $transfer = app(RecordTransfer::class)->execute($from, $to, Money::ofMinor(300_000));

        // Two transactions, linked to the transfer, one expense + one income.
        $this->assertSame(2, Transaction::count());
        $this->assertSame(2, $transfer->transactions()->count());
        $this->assertSame(1, $from->transactions()->expense()->count());
        $this->assertSame(1, $to->transactions()->income()->count());

        // They are excluded from real income/expense (P&L).
        $this->assertSame(0, Transaction::query()->notTransfer()->count());
    }

    public function test_deleting_a_transfer_removes_its_transactions(): void
    {
        $from = Account::factory()->create(['opening_balance' => 500_000]);
        $to = Account::factory()->create();

        $transfer = app(RecordTransfer::class)->execute($from, $to, Money::ofMinor(200_000));
        $this->assertSame(300_000, $from->balance()->minorAmount);

        $transfer->delete();

        // Ledger entries gone → balances restored.
        $this->assertSame(500_000, $from->balance()->minorAmount);
        $this->assertSame(0, $to->balance()->minorAmount);
    }

    public function test_payment_method_routes_transaction_to_its_default_account(): void
    {
        $cashBox = Account::factory()->cash()->create();
        $method = PaymentMethod::create(['name' => 'Efectivo', 'default_account_id' => $cashBox->id]);

        $transaction = app(RecordTransaction::class)->execute(
            type: TransactionType::Income,
            amount: Money::ofMinor(150_000),
            paymentMethod: $method,
        );

        $this->assertSame($cashBox->id, $transaction->account_id);
        $this->assertSame(150_000, $cashBox->balance()->minorAmount);
    }

    public function test_transfer_rejects_same_account(): void
    {
        $account = Account::factory()->create();

        $this->expectException(InvalidArgumentException::class);
        app(RecordTransfer::class)->execute($account, $account, Money::ofMinor(100_000));
    }

    public function test_admin_can_manage_accounts_and_transfers(): void
    {
        Account::factory()->count(2)->create();
        $admin = $this->admin();

        Livewire::actingAs($admin)->test(ManageAccounts::class)->assertOk();
        Livewire::actingAs($admin)->test(ManageTransfers::class)->assertOk();
    }
}
