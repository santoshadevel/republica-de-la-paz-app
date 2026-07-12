<?php

namespace App\Models;

use App\Casts\MoneyCast;
use App\Enums\TransactionType;
use Database\Factories\TransactionFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * Any economic movement of the platform (income or expense). Classified by
 * category/subcategory, cost center and payment method; optionally linked to the
 * record that generated it. See docs/REQUISITOS.md (section 4).
 */
#[Fillable([
    'type',
    'amount',
    'occurred_on',
    'description',
    'category_id',
    'cost_center_id',
    'payment_method_id',
    'account_id',
    'source_type',
    'source_id',
    'invoice_issued',
    'invoice_number',
    'invoice_business_name',
    'invoice_tax_id',
    'invoice_tax_condition',
    'created_by',
])]
class Transaction extends Model
{
    /** @use HasFactory<TransactionFactory> */
    use HasFactory, SoftDeletes;

    protected function casts(): array
    {
        return [
            'type' => TransactionType::class,
            'amount' => MoneyCast::class,
            'occurred_on' => 'date',
            'invoice_issued' => 'boolean',
        ];
    }

    public function category(): BelongsTo
    {
        return $this->belongsTo(Category::class);
    }

    public function costCenter(): BelongsTo
    {
        return $this->belongsTo(CostCenter::class);
    }

    public function paymentMethod(): BelongsTo
    {
        return $this->belongsTo(PaymentMethod::class);
    }

    /** The account the money entered / left. */
    public function account(): BelongsTo
    {
        return $this->belongsTo(Account::class);
    }

    /** The record that generated this movement (membership sale, event, etc.). */
    public function source(): MorphTo
    {
        return $this->morphTo();
    }

    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function scopeIncome(Builder $query): Builder
    {
        return $query->where('type', TransactionType::Income->value);
    }

    public function scopeExpense(Builder $query): Builder
    {
        return $query->where('type', TransactionType::Expense->value);
    }
}
