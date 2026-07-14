<?php

namespace App\Livewire\Portal;

use App\Models\MembershipOrder;
use App\Models\MembershipPlan;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Layout;
use Livewire\Component;
use RuntimeException;

/** Pass catalog + purchase requests (approved manually by staff for now). */
#[Layout('components.layouts.app')]
class Plans extends Component
{
    public function requestPlan(int $planId): void
    {
        $student = Auth::user()->student;
        $plan = MembershipPlan::where('is_active', true)->find($planId);

        if ($student === null || $plan === null) {
            return;
        }

        $alreadyPending = MembershipOrder::query()
            ->where('student_id', $student->id)
            ->where('membership_plan_id', $plan->id)
            ->pending()
            ->exists();

        if ($alreadyPending) {
            session()->flash('status', 'Ya tenés una solicitud pendiente para ese pase.');

            return;
        }

        MembershipOrder::place($student, $plan);
        session()->flash('status', 'Solicitud enviada. La revisamos y activamos tu pase a la brevedad.');
    }

    /** Withdraw a request staff have not reviewed yet. */
    public function cancelOrder(int $orderId): void
    {
        $student = Auth::user()->student;
        $order = MembershipOrder::where('student_id', $student?->id)->find($orderId);

        if ($order === null) {
            return;
        }

        try {
            $order->markCancelledBy($student);
            session()->flash('status', 'Solicitud cancelada.');
        } catch (RuntimeException $e) {
            session()->flash('error', $e->getMessage());
        }
    }

    public function render()
    {
        $student = Auth::user()->student;

        $orders = $student !== null
            ? MembershipOrder::with('plan')->where('student_id', $student->id)->latest()->get()
            : collect();

        return view('livewire.portal.plans', [
            'plans' => MembershipPlan::active()->get(),
            'orders' => $orders,
            // Lets the catalog mark the passes already awaiting review, instead
            // of only telling the student after they hit "solicitar" again.
            'pendingPlanIds' => $orders->filter->isPending()->pluck('membership_plan_id'),
        ]);
    }
}
