<?php

namespace App\Actions\Accounting;

use App\Models\Account;
use App\Models\Transfer;
use App\Support\Money;
use Illuminate\Support\Carbon;
use InvalidArgumentException;

/** Moves money between two accounts. Not income/expense — just relocates funds. */
class RecordTransfer
{
    /**
     * @param  array<string, mixed>  $attributes  extra fields (description, created_by, occurred_on)
     */
    public function execute(
        Account $from,
        Account $to,
        Money $amount,
        array $attributes = [],
    ): Transfer {
        if ($from->is($to)) {
            throw new InvalidArgumentException('La cuenta de origen y destino no pueden ser la misma.');
        }

        if ($amount->minorAmount <= 0) {
            throw new InvalidArgumentException('El monto de la transferencia debe ser mayor a cero.');
        }

        return Transfer::create(array_merge([
            'from_account_id' => $from->getKey(),
            'to_account_id' => $to->getKey(),
            'amount' => $amount->minorAmount,
            'occurred_on' => $attributes['occurred_on'] ?? Carbon::now()->toDateString(),
        ], collect($attributes)->except('occurred_on')->all()));
    }
}
