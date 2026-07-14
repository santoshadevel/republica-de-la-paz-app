<?php

namespace App\Actions\Memberships;

use App\Models\MembershipOrder;
use App\Models\PaymentMethod;
use App\Models\StudentMembership;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use RuntimeException;

/**
 * Approves a pending pass request: sells the membership to the student (which
 * grants credits and records the income when a payment method is given) and marks
 * the order approved. This is the manual-approval step until a payment gateway
 * exists.
 */
class ApproveMembershipOrder
{
    public function __construct(private SellMembership $sellMembership) {}

    public function execute(MembershipOrder $order, User $by, ?PaymentMethod $paymentMethod = null): StudentMembership
    {
        return DB::transaction(function () use ($order, $by, $paymentMethod) {
            // Re-read under lock: the student may be withdrawing this very order
            // from the portal. Checking outside the transaction would let both win.
            if (! MembershipOrder::lockedPending($order->getKey())) {
                throw new RuntimeException('La solicitud ya fue revisada.');
            }

            // Honour the price snapshotted when the order was placed, not the live
            // catalog price (which may have changed after the student requested it).
            $membership = $this->sellMembership->execute(
                $order->student,
                $order->plan,
                null,
                $paymentMethod,
                ['price_paid' => $order->price?->minorAmount ?? 0],
            );
            $order->markApproved($by, $membership);

            return $membership;
        });
    }
}
