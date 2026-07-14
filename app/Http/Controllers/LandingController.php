<?php

namespace App\Http\Controllers;

use App\Enums\ActivityType;
use App\Models\Activity;
use App\Models\MembershipPlan;
use App\Models\Practitioner;
use App\Support\ContactChannels;
use App\Support\LandingContent;
use Illuminate\Contracts\View\View;

/**
 * The public landing. Resolves everything the view needs up front so no query
 * ever runs from a Blade (see CLAUDE.md, Regla #1).
 */
class LandingController extends Controller
{
    public function __invoke(): View
    {
        return view('public.home', [
            'plans' => MembershipPlan::active()->get(),
            'practitioners' => Practitioner::active()->with('activities')->get(),
            'therapies' => Activity::active()
                ->ofType(ActivityType::IndividualSession)
                ->with('practitioners')
                ->get(),
            'constitution' => LandingContent::constitution(),
            'finalProvision' => LandingContent::finalProvision(),
            'faqGroups' => LandingContent::faqs(),
            'contact' => ContactChannels::fromConfig(),
        ]);
    }
}
