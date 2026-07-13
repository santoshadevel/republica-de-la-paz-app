<?php

namespace App\Models;

use App\Casts\MoneyCast;
use App\Enums\CreditMovementType;
use App\Enums\MembershipStatus;
use Database\Factories\StudentMembershipFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * A membership/pass a student bought. Plan values are snapshotted at sale time;
 * the practice balance lives in the credit_movements ledger (balance = SUM(amount)).
 * See docs/MODULO_MEMBRESIAS.md.
 */
#[Fillable([
    'student_id',
    'membership_plan_id',
    'credits_total',
    'is_unlimited',
    'price_paid',
    'currency_code',
    'starts_at',
    'ends_at',
    'status',
    'notes',
])]
class StudentMembership extends Model
{
    /** @use HasFactory<StudentMembershipFactory> */
    use HasFactory, SoftDeletes;

    protected function casts(): array
    {
        return [
            'credits_total' => 'integer',
            'is_unlimited' => 'boolean',
            'price_paid' => MoneyCast::class,
            'starts_at' => 'date',
            'ends_at' => 'date',
            'status' => MembershipStatus::class,
        ];
    }

    public function student(): BelongsTo
    {
        return $this->belongsTo(Student::class);
    }

    /** The catalog plan this was sold from (may be null if the plan was deleted). */
    public function plan(): BelongsTo
    {
        return $this->belongsTo(MembershipPlan::class, 'membership_plan_id');
    }

    /** The signed credit ledger for this membership. */
    public function movements(): HasMany
    {
        return $this->hasMany(CreditMovement::class);
    }

    /** Practice credits still available. Null means unlimited (no cap). */
    public function creditsRemaining(): ?int
    {
        if ($this->is_unlimited) {
            return null;
        }

        return (int) $this->movements()->sum('amount');
    }

    /** Practice credits already consumed (absolute value of consumption entries). */
    public function creditsConsumed(): int
    {
        return (int) abs($this->movements()
            ->where('type', CreditMovementType::Consumption->value)
            ->sum('amount'));
    }

    /** Whether the membership is active AND still within its validity window. */
    public function isCurrentlyActive(): bool
    {
        return $this->status === MembershipStatus::Active
            && $this->ends_at !== null
            && $this->ends_at->endOfDay()->greaterThanOrEqualTo(now());
    }

    /** Whether a group practice can still be booked against this membership. */
    public function hasAvailableCredit(): bool
    {
        if (! $this->isCurrentlyActive()) {
            return false;
        }

        return $this->is_unlimited || $this->creditsRemaining() > 0;
    }

    /** Scope: memberships that are active and not past their end date. */
    public function scopeActive(Builder $query): Builder
    {
        return $query
            ->where('status', MembershipStatus::Active->value)
            ->whereDate('ends_at', '>=', now()->toDateString());
    }

    /** Append a signed entry to this membership's credit ledger. */
    public function recordMovement(
        CreditMovementType $type,
        int $amount,
        string $reason,
        ?Booking $booking = null,
        ?User $by = null,
    ): CreditMovement {
        return $this->movements()->create([
            'type' => $type,
            'amount' => $amount,
            'reason' => $reason,
            'booking_id' => $booking?->getKey(),
            'created_by' => $by?->getKey(),
        ]);
    }

    /** Mark active memberships past their validity window as expired; returns the count. */
    public static function expireOverdue(): int
    {
        return static::query()
            ->where('status', MembershipStatus::Active->value)
            ->whereDate('ends_at', '<', now()->toDateString())
            ->update(['status' => MembershipStatus::Expired->value]);
    }
}
