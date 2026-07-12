<?php

namespace App\Models;

use App\Enums\TransactionType;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/** An accounting category (income or expense), optionally a subcategory. */
#[Fillable(['name', 'type', 'parent_id', 'is_active'])]
class Category extends Model
{
    protected function casts(): array
    {
        return [
            'type' => TransactionType::class,
            'is_active' => 'boolean',
        ];
    }

    public function parent(): BelongsTo
    {
        return $this->belongsTo(self::class, 'parent_id');
    }

    public function children(): HasMany
    {
        return $this->hasMany(self::class, 'parent_id');
    }

    public function transactions(): HasMany
    {
        return $this->hasMany(Transaction::class);
    }

    /** Full path label, e.g. "Honorarios › Profesores". */
    public function fullName(): string
    {
        return $this->parent
            ? "{$this->parent->name} › {$this->name}"
            : $this->name;
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
