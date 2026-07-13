<?php

namespace App\Models;

use App\Casts\MoneyCast;
use App\Enums\AccountType;
use App\Support\Money;
use Database\Factories\AccountFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * A money account / cash box (Caja chica, bank account...). Its balance is the
 * opening balance plus income, minus expenses, adjusted by transfers in/out.
 */
#[Fillable(['name', 'type', 'account_number', 'opening_balance', 'is_active', 'notes'])]
class Account extends Model
{
    /** @use HasFactory<AccountFactory> */
    use HasFactory;

    protected function casts(): array
    {
        return [
            'type' => AccountType::class,
            'opening_balance' => MoneyCast::class,
            'is_active' => 'boolean',
        ];
    }

    public function transactions(): HasMany
    {
        return $this->hasMany(Transaction::class);
    }

    public function transfersOut(): HasMany
    {
        return $this->hasMany(Transfer::class, 'from_account_id');
    }

    public function transfersIn(): HasMany
    {
        return $this->hasMany(Transfer::class, 'to_account_id');
    }

    /**
     * Current balance = opening + income - expense, over ALL transactions of the
     * account. Transfers are recorded as transactions too, so they are already
     * included here (money in/out of this account).
     */
    public function balance(): Money
    {
        $income = (int) $this->transactions()->income()->sum('amount');
        $expense = (int) $this->transactions()->expense()->sum('amount');

        return Money::ofMinor($this->opening_balance->minorAmount + $income - $expense);
    }
}
