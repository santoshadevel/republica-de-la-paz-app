<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Default currency
    |--------------------------------------------------------------------------
    |
    | ISO 4217 code of the brand's currency. White-label installs override this
    | via the APP_CURRENCY env var. Money is stored across the app as integers
    | in the currency's minor unit (see "digits" below).
    |
    */

    'default' => env('APP_CURRENCY', 'PYG'),

    /*
    |--------------------------------------------------------------------------
    | Currency definitions
    |--------------------------------------------------------------------------
    |
    | "digits" is the number of minor-unit decimal places. Guaraní (PYG) has no
    | subdivision (0), so amounts are whole guaraníes; USD/EUR use 2 (cents).
    |
    */

    'currencies' => [
        'PYG' => ['symbol' => 'Gs', 'digits' => 0, 'symbol_first' => true],
        'USD' => ['symbol' => '$', 'digits' => 2, 'symbol_first' => true],
        'EUR' => ['symbol' => '€', 'digits' => 2, 'symbol_first' => false],
        'BRL' => ['symbol' => 'R$', 'digits' => 2, 'symbol_first' => true],
        'ARS' => ['symbol' => '$', 'digits' => 2, 'symbol_first' => true],
    ],

    /*
    | Grouping/decimal separators for display formatting.
    */
    'format' => [
        'thousands_separator' => '.',
        'decimal_separator' => ',',
    ],

];
