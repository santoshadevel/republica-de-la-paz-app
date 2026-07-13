<?php

namespace App\Livewire\Portal;

use App\Services\Scheduling\StudentAgendaService;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Layout;
use Livewire\Component;

#[Layout('components.layouts.app')]
class Dashboard extends Component
{
    public function render()
    {
        $student = Auth::user()->student;

        $agenda = $student !== null
            ? app(StudentAgendaService::class)->for($student)
            : ['upcoming' => [], 'past' => []];

        return view('livewire.portal.dashboard', [
            'student' => $student,
            'agenda' => $agenda,
            'membership' => $student?->currentMembership(),
        ]);
    }
}
