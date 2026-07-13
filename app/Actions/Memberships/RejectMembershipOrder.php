<?php

namespace App\Actions\Memberships;

use App\Models\MembershipOrder;
use App\Models\User;
use RuntimeException;

/** Rejects a pending pass request. */
class RejectMembershipOrder
{
    public function execute(MembershipOrder $order, User $by, ?string $notes = null): void
    {
        if (! $order->isPending()) {
            throw new RuntimeException('La solicitud ya fue revisada.');
        }

        $order->markRejected($by, $notes);
    }
}
