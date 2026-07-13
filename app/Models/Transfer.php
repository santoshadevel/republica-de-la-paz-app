<?php

namespace App\Models;

use App\Casts\MoneyCast;
use Database\Factories\TransferFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphMany;

/**
 * An internal money movement between two accounts. It is not income/expense, but
 * it materialises as two transactions (an expense on the source and an income on
 * the destination) so account balances derive purely from transactions.
 */
#[Fillable([
    'from_account_id',
    'to_account_id',
    'amount',
    'occurred_on',
    'description',
    'created_by',
])]
class Transfer extends Model
{
    /** @use HasFactory<TransferFactory> */
    use HasFactory;

    protected function casts(): array
    {
        return [
            'amount' => MoneyCast::class,
            'occurred_on' => 'date',
        ];
    }

    protected static function booted(): void
    {
        // Removing a transfer removes its two ledger entries too.
        static::deleting(function (self $transfer): void {
            $transfer->transactions()->delete();
        });
    }

    /** Persist an internal transfer between two accounts. */
    public static function record(array $attributes): self
    {
        return static::create($attributes);
    }

    /** The two transactions (expense + income) this transfer generated. */
    public function transactions(): MorphMany
    {
        return $this->morphMany(Transaction::class, 'source');
    }

    public function fromAccount(): BelongsTo
    {
        return $this->belongsTo(Account::class, 'from_account_id');
    }

    public function toAccount(): BelongsTo
    {
        return $this->belongsTo(Account::class, 'to_account_id');
    }

    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }
}
