<?php

namespace App\Livewire\Portal;

use App\Models\MembershipOrder;
use App\Models\MembershipPlan;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Layout;
use Livewire\Component;

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

    public function render()
    {
        $student = Auth::user()->student;

        return view('livewire.portal.plans', [
            'plans' => MembershipPlan::query()->where('is_active', true)->orderBy('sort_order')->get(),
            'orders' => $student !== null
                ? MembershipOrder::with('plan')->where('student_id', $student->id)->latest()->get()
                : collect(),
        ]);
    }
}
