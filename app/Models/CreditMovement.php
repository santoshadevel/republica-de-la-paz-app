<?php

namespace App\Models;

use App\Enums\CreditMovementType;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * One signed entry in a membership's credit ledger. The balance of a membership
 * is the sum of its movements' `amount`. See docs/MODULO_MEMBRESIAS.md.
 */
#[Fillable([
    'student_membership_id',
    'type',
    'amount',
    'reason',
    'booking_id',
    'created_by',
])]
class CreditMovement extends Model
{
    protected function casts(): array
    {
        return [
            'type' => CreditMovementType::class,
            'amount' => 'integer',
        ];
    }

    public function membership(): BelongsTo
    {
        return $this->belongsTo(StudentMembership::class, 'student_membership_id');
    }

    /** The user who performed a manual adjustment (null for system movements). */
    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }
}
