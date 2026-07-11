<?php

namespace App\Casts;

use App\Support\Money;
use Illuminate\Contracts\Database\Eloquent\CastsAttributes;
use Illuminate\Database\Eloquent\Model;

/**
 * Casts an integer minor-unit column to a Money value object and back.
 * The currency is the app default (one brand per deploy); see config/currency.php.
 *
 * @implements CastsAttributes<Money, Money|int|float>
 */
class MoneyCast implements CastsAttributes
{
    public function get(Model $model, string $key, mixed $value, array $attributes): ?Money
    {
        return $value === null ? null : Money::ofMinor((int) $value);
    }

    public function set(Model $model, string $key, mixed $value, array $attributes): ?int
    {
        return match (true) {
            $value === null => null,
            $value instanceof Money => $value->minorAmount,
            default => (int) $value, // already minor units
        };
    }
}
