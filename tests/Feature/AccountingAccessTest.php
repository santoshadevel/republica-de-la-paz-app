<?php

namespace Tests\Feature;

use App\Enums\Role;
use App\Filament\Pages\HonorariumLiquidation;
use App\Filament\Resources\Accounts\AccountResource;
use App\Filament\Resources\Categories\CategoryResource;
use App\Filament\Resources\CostCenters\CostCenterResource;
use App\Filament\Resources\PaymentMethods\PaymentMethodResource;
use App\Filament\Resources\Transactions\TransactionResource;
use App\Filament\Resources\Transfers\TransferResource;
use App\Filament\Widgets\BusinessState;
use App\Filament\Widgets\TodayOverview;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\DataProvider;
use Spatie\Permission\Models\Role as SpatieRole;
use Tests\TestCase;

/** Accounting and financial widgets must be reachable by admins only. */
class AccountingAccessTest extends TestCase
{
    use RefreshDatabase;

    private function staffUser(Role $role): User
    {
        SpatieRole::findOrCreate($role->value, 'web');
        $user = User::factory()->create();
        $user->assignRole($role->value);

        return $user;
    }

    /** @return array<string, array{class-string}> */
    public static function accountingResources(): array
    {
        return [
            'transactions' => [TransactionResource::class],
            'transfers' => [TransferResource::class],
            'accounts' => [AccountResource::class],
            'categories' => [CategoryResource::class],
            'cost centers' => [CostCenterResource::class],
            'payment methods' => [PaymentMethodResource::class],
            'honorarium liquidation' => [HonorariumLiquidation::class],
        ];
    }

    #[DataProvider('accountingResources')]
    public function test_admin_can_access_accounting(string $class): void
    {
        $this->actingAs($this->staffUser(Role::Admin));

        $this->assertTrue($class::canAccess());
    }

    #[DataProvider('accountingResources')]
    public function test_receptionist_cannot_access_accounting(string $class): void
    {
        $this->actingAs($this->staffUser(Role::Receptionist));

        $this->assertFalse($class::canAccess());
    }

    #[DataProvider('accountingResources')]
    public function test_practitioner_cannot_access_accounting(string $class): void
    {
        $this->actingAs($this->staffUser(Role::Practitioner));

        $this->assertFalse($class::canAccess());
    }

    public function test_financial_widgets_are_admin_only(): void
    {
        $this->actingAs($this->staffUser(Role::Admin));
        $this->assertTrue(TodayOverview::canView());
        $this->assertTrue(BusinessState::canView());

        $this->actingAs($this->staffUser(Role::Receptionist));
        $this->assertFalse(TodayOverview::canView());
        $this->assertFalse(BusinessState::canView());
    }
}
