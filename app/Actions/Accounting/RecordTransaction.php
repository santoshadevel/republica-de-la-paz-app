<?php

namespace App\Actions\Accounting;

use App\Enums\TransactionType;
use App\Models\Account;
use App\Models\Category;
use App\Models\CostCenter;
use App\Models\PaymentMethod;
use App\Models\Transaction;
use App\Support\Money;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Carbon;

/**
 * Records any economic movement (income or expense). Central entry point so the
 * accounting rules live in one place, reusable by Filament, hooks and the API.
 */
class RecordTransaction
{
    /**
     * @param  array<string, mixed>  $attributes  extra fields (description, invoice_*, created_by, occurred_on)
     */
    public function execute(
        TransactionType $type,
        Money $amount,
        ?Category $category = null,
        ?CostCenter $costCenter = null,
        ?PaymentMethod $paymentMethod = null,
        ?Model $source = null,
        ?Account $account = null,
        array $attributes = [],
    ): Transaction {
        // Route to the payment method's default account when none is given.
        $account ??= $paymentMethod?->defaultAccount;

        return Transaction::record(array_merge([
            'type' => $type,
            'amount' => $amount->minorAmount,
            'occurred_on' => $attributes['occurred_on'] ?? Carbon::now()->toDateString(),
            'category_id' => $category?->getKey(),
            'cost_center_id' => $costCenter?->getKey(),
            'payment_method_id' => $paymentMethod?->getKey(),
            'account_id' => $account?->getKey(),
        ], collect($attributes)->except('occurred_on')->all()), $source);
    }
}
