<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Cancellation policy
    |--------------------------------------------------------------------------
    |
    | Business rules for cancellations, configurable per brand (white-label).
    |
    | - group_cancellation_hours: a group practice cancelled at least this many
    |   hours before it starts refunds the credit; later cancellations (or
    |   no-shows) consume it.
    | - individual_cancellation_hours / individual_late_fee_percent: for
    |   individual sessions (Fase 6) — cancelling with less notice charges a fee.
    |
    */

    'group_cancellation_hours' => (int) env('BOOKING_GROUP_CANCELLATION_HOURS', 1),

    'individual_cancellation_hours' => (int) env('BOOKING_INDIVIDUAL_CANCELLATION_HOURS', 24),

    'individual_late_fee_percent' => (int) env('BOOKING_INDIVIDUAL_LATE_FEE_PERCENT', 50),

];
