<?php

namespace App\Models;

use App\Casts\MoneyCast;
use App\Enums\FeeType;
use App\Support\Money;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * A practitioner's compensation rule for a service: a fixed amount per session
 * or a percentage of the income it generates. See docs/REQUISITOS.md (4.9).
 */
#[Fillable([
    'practitioner_id',
    'activity_id',
    'type',
    'fixed_amount',
    'percentage',
    'notes',
])]
class FeeScheme extends Model
{
    protected function casts(): array
    {
        return [
            'type' => FeeType::class,
            'fixed_amount' => MoneyCast::class,
            'percentage' => 'integer',
        ];
    }

    public function practitioner(): BelongsTo
    {
        return $this->belongsTo(Practitioner::class);
    }

    public function activity(): BelongsTo
    {
        return $this->belongsTo(Activity::class);
    }

    /** The fee owed for one service, given the income it generated. */
    public function feeFor(Money $income): Money
    {
        return match ($this->type) {
            FeeType::FixedPerSession => $this->fixed_amount ?? Money::ofMinor(0),
            FeeType::Percentage => Money::ofMinor((int) round($income->minorAmount * ($this->percentage ?? 0) / 100)),
        };
    }
}
