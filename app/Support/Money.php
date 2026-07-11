<?php

namespace App\Support;

use InvalidArgumentException;

/**
 * Immutable money value object. Stores an integer amount in the currency's
 * minor unit (e.g. cents; whole guaraníes for PYG which has 0 decimals).
 *
 * White-label friendly: the currency and its formatting come from config,
 * never hardcoded. See config/currency.php.
 */
final class Money
{
    public function __construct(
        public readonly int $minorAmount,
        public readonly string $currency,
    ) {
        if (! array_key_exists($currency, config('currency.currencies'))) {
            throw new InvalidArgumentException("Unknown currency [{$currency}].");
        }
    }

    /** Build from a minor-unit integer using the app's default currency. */
    public static function ofMinor(int $minorAmount, ?string $currency = null): self
    {
        return new self($minorAmount, $currency ?? config('currency.default'));
    }

    /** Build from a major-unit value (e.g. 50000 Gs, or 12.50 USD). */
    public static function ofMajor(int|float|string $majorAmount, ?string $currency = null): self
    {
        $currency ??= config('currency.default');
        $digits = self::definition($currency)['digits'];

        return new self((int) round(((float) $majorAmount) * (10 ** $digits)), $currency);
    }

    /** Amount expressed in major units (float). */
    public function toMajor(): float
    {
        return $this->minorAmount / (10 ** self::definition($this->currency)['digits']);
    }

    /** Human-readable formatted string, e.g. "Gs 50.000" or "$12,50". */
    public function format(): string
    {
        $def = self::definition($this->currency);
        $fmt = config('currency.format');

        $number = number_format(
            $this->toMajor(),
            $def['digits'],
            $fmt['decimal_separator'],
            $fmt['thousands_separator'],
        );

        return $def['symbol_first']
            ? "{$def['symbol']} {$number}"
            : "{$number} {$def['symbol']}";
    }

    public function __toString(): string
    {
        return $this->format();
    }

    /** @return array{symbol: string, digits: int, symbol_first: bool} */
    private static function definition(string $currency): array
    {
        return config("currency.currencies.{$currency}");
    }
}
