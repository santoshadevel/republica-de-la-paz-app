<?php

namespace App\Actions\Memberships;

use App\Actions\Accounting\RecordTransaction;
use App\Enums\CreditMovementType;
use App\Enums\MembershipStatus;
use App\Enums\TransactionType;
use App\Models\Category;
use App\Models\MembershipPlan;
use App\Models\PaymentMethod;
use App\Models\Student;
use App\Models\StudentMembership;
use App\Support\Money;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

/**
 * Sell a membership plan to a student: snapshot the plan, open the validity
 * window and seed the credit ledger with the granted credits. If a payment
 * method is given, it also records the income transaction (accounting).
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
        ?PaymentMethod $paymentMethod = null,
        array $attributes = [],
    ): StudentMembership {
        return DB::transaction(function () use ($student, $plan, $startsAt, $paymentMethod, $attributes) {
            $startsAt = ($startsAt ?? now())->startOfDay();
            $validityDays = $plan->validityDays() ?? 0;
            $creditsTotal = $plan->credits();
            $isUnlimited = $plan->isUnlimited();

            $membership = $student->openMembership(array_merge([
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
                $membership->recordMovement(
                    CreditMovementType::Sale,
                    $creditsTotal,
                    "Venta de {$plan->name}",
                );
            }

            // Accounting: record the income for this sale (only when we know how
            // it was paid). Uses the actual price charged (may be a snapshot override,
            // not the live catalog price). Category resolved to the "Membresías" tree.
            if ($paymentMethod !== null && $membership->price_paid->minorAmount > 0) {
                app(RecordTransaction::class)->execute(
                    type: TransactionType::Income,
                    amount: Money::ofMinor($membership->price_paid->minorAmount, $membership->currency_code),
                    category: $this->incomeCategory($plan),
                    paymentMethod: $paymentMethod,
                    source: $membership,
                    attributes: [
                        'description' => "Venta de {$plan->name}",
                        'occurred_on' => $startsAt->toDateString(),
                    ],
                );
            }

            return $membership;
        });
    }

    /** The income category for a membership sale (subcategory by plan name, else the parent). */
    private function incomeCategory(MembershipPlan $plan): ?Category
    {
        $parent = Category::query()->income()->whereNull('parent_id')->where('name', 'Membresías')->first();

        if ($parent === null) {
            return null;
        }

        return $parent->children()->where('name', $plan->name)->first() ?? $parent;
    }
}
