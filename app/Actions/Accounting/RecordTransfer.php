<?php

namespace App\Actions\Accounting;

use App\Enums\TransactionType;
use App\Models\Account;
use App\Models\Transfer;
use App\Support\Money;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;

/**
 * Moves money between two accounts. Records TWO transactions — an expense on the
 * source account and an income on the destination — so each account's balance is
 * simply the sum of its transactions. Both are linked to the Transfer (source)
 * so reports can exclude them from real income/expense (they are internal).
 */
class RecordTransfer
{
    public function __construct(private RecordTransaction $recordTransaction) {}

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

        return DB::transaction(function () use ($from, $to, $amount, $attributes) {
            $occurredOn = $attributes['occurred_on'] ?? Carbon::now()->toDateString();
            $note = $attributes['description'] ?? null;

            $transfer = Transfer::record(array_merge([
                'from_account_id' => $from->getKey(),
                'to_account_id' => $to->getKey(),
                'amount' => $amount->minorAmount,
                'occurred_on' => $occurredOn,
            ], collect($attributes)->except('occurred_on')->all()));

            // Money leaving the source account.
            $this->recordTransaction->execute(
                type: TransactionType::Expense,
                amount: $amount,
                account: $from,
                source: $transfer,
                attributes: [
                    'description' => $note ?? "Transferencia a {$to->name}",
                    'occurred_on' => $occurredOn,
                ],
            );

            // Money arriving in the destination account.
            $this->recordTransaction->execute(
                type: TransactionType::Income,
                amount: $amount,
                account: $to,
                source: $transfer,
                attributes: [
                    'description' => $note ?? "Transferencia desde {$from->name}",
                    'occurred_on' => $occurredOn,
                ],
            );

            return $transfer;
        });
    }
}
