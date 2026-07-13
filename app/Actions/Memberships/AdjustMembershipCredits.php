<?php

namespace App\Actions\Memberships;

use App\Enums\CreditMovementType;
use App\Models\CreditMovement;
use App\Models\StudentMembership;
use App\Models\User;
use InvalidArgumentException;

/**
 * Manually add or subtract practice credits on a membership (admin action from
 * the PDF: "agregar o descontar prácticas manualmente"). Recorded in the ledger
 * with a reason and the acting user for traceability.
 */
class AdjustMembershipCredits
{
    public function execute(
        StudentMembership $membership,
        int $amount,
        string $reason,
        ?User $by = null,
    ): CreditMovement {
        if ($amount === 0) {
            throw new InvalidArgumentException('El ajuste de créditos no puede ser cero.');
        }

        if (trim($reason) === '') {
            throw new InvalidArgumentException('El ajuste manual requiere un motivo.');
        }

        return $membership->recordMovement(
            CreditMovementType::ManualAdjust,
            $amount,
            $reason,
            by: $by,
        );
    }
}
