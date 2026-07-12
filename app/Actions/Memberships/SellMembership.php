<?php

namespace App\Actions\Memberships;

use App\Enums\CreditMovementType;
use App\Enums\MembershipStatus;
use App\Models\MembershipPlan;
use App\Models\Student;
use App\Models\StudentMembership;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

/**
 * Sell a membership plan to a student: snapshot the plan, open the validity
 * window and seed the credit ledger with the granted credits.
 *
 * Reusable by Filament and the future API. See docs/MODULO_MEMBRESIAS.md.
 */
class SellMembership
{
    /**
     * @param  array<string, mixed>  $attributes  extra overrides (e.g. notes, price_paid)
     */
    public function execute(
        Student $student,
        MembershipPlan $plan,
        ?Carbon $startsAt = null,
        array $attributes = [],
    ): StudentMembership {
        return DB::transaction(function () use ($student, $plan, $startsAt, $attributes) {
            $startsAt = ($startsAt ?? now())->startOfDay();
            $validityDays = $plan->validityDays() ?? 0;
            $creditsTotal = $plan->credits();
            $isUnlimited = $plan->isUnlimited();

            $membership = $student->memberships()->create(array_merge([
                'membership_plan_id' => $plan->getKey(),
                'credits_total' => $isUnlimited ? null : $creditsTotal,
                'is_unlimited' => $isUnlimited,
                'price_paid' => $plan->price?->minorAmount ?? 0,
                'currency_code' => config('currency.default'),
                'starts_at' => $startsAt,
                'ends_at' => $startsAt->copy()->addDays($validityDays),
                'status' => MembershipStatus::Active,
            ], $attributes));

            // Seed the ledger with the granted credits (unlimited grants none —
            // its bookings are recorded in Fase 5 without touching the balance).
            if (! $isUnlimited && $creditsTotal !== null && $creditsTotal > 0) {
                $membership->movements()->create([
                    'type' => CreditMovementType::Sale,
                    'amount' => $creditsTotal,
                    'reason' => "Venta de {$plan->name}",
                ]);
            }

            // Fase 7: registrar aquí el ingreso contable (Transaction) — este es el
            // único punto de la venta, así el asiento se agrega sin refactor.

            return $membership;
        });
    }
}
