<?php

namespace App\Models;

use App\Casts\MoneyCast;
use App\Enums\MembershipOrderStatus;
use Database\Factories\MembershipOrderFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * A student's request to buy a pass. Created as pending from the portal; staff
 * approve it manually (which sells the membership). Payment gateway comes later.
 */
#[Fillable([
    'student_id',
    'membership_plan_id',
    'status',
    'price',
    'student_membership_id',
    'reviewed_by',
    'reviewed_at',
    'notes',
])]
class MembershipOrder extends Model
{
    /** @use HasFactory<MembershipOrderFactory> */
    use HasFactory;

    protected function casts(): array
    {
        return [
            'status' => MembershipOrderStatus::class,
            'price' => MoneyCast::class,
            'reviewed_at' => 'datetime',
        ];
    }

    public function student(): BelongsTo
    {
        return $this->belongsTo(Student::class);
    }

    public function plan(): BelongsTo
    {
        return $this->belongsTo(MembershipPlan::class, 'membership_plan_id');
    }

    /** The membership created when this order was approved. */
    public function membership(): BelongsTo
    {
        return $this->belongsTo(StudentMembership::class, 'student_membership_id');
    }

    public function reviewedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'reviewed_by');
    }

    public function isPending(): bool
    {
        return $this->status === MembershipOrderStatus::Pending;
    }

    public function scopePending(Builder $query): Builder
    {
        return $query->where('status', MembershipOrderStatus::Pending->value);
    }

    /** Create a pending purchase request, snapshotting the plan price. */
    public static function place(Student $student, MembershipPlan $plan): self
    {
        return static::create([
            'student_id' => $student->getKey(),
            'membership_plan_id' => $plan->getKey(),
            'status' => MembershipOrderStatus::Pending,
            'price' => $plan->price,
        ]);
    }

    /** Mark this order approved and link the membership it produced. */
    public function markApproved(User $by, StudentMembership $membership): self
    {
        $this->update([
            'status' => MembershipOrderStatus::Approved,
            'student_membership_id' => $membership->getKey(),
            'reviewed_by' => $by->getKey(),
            'reviewed_at' => now(),
        ]);

        return $this;
    }

    /** Mark this order rejected. */
    public function markRejected(User $by, ?string $notes = null): self
    {
        $this->update([
            'status' => MembershipOrderStatus::Rejected,
            'reviewed_by' => $by->getKey(),
            'reviewed_at' => now(),
            'notes' => $notes,
        ]);

        return $this;
    }
}
