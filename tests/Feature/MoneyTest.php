<?php

namespace Tests\Feature;

use App\Support\Money;
use InvalidArgumentException;
use Tests\TestCase;

class MoneyTest extends TestCase
{
    public function test_pyg_has_no_decimals_and_formats_with_symbol_first(): void
    {
        $money = Money::ofMinor(200000, 'PYG');

        $this->assertSame(200000.0, $money->toMajor());
        $this->assertSame('Gs 200.000', $money->format());
    }

    public function test_two_decimal_currencies_round_trip_major_to_minor(): void
    {
        $money = Money::ofMajor(12.5, 'USD');

        $this->assertSame(1250, $money->minorAmount);
        $this->assertSame('$ 12,50', $money->format());
    }

    public function test_symbol_can_be_placed_after_the_amount(): void
    {
        $this->assertSame('1.250,75 €', Money::ofMajor(1250.75, 'EUR')->format());
    }

    public function test_default_currency_comes_from_config(): void
    {
        config()->set('currency.default', 'PYG');

        $this->assertSame('PYG', Money::ofMajor(1000)->currency);
    }

    public function test_unknown_currency_is_rejected(): void
    {
        $this->expectException(InvalidArgumentException::class);

        new Money(100, 'XyZ');
    }
}
