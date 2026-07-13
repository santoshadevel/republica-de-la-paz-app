<?php

namespace App\Filament\Widgets;

use App\Services\Reporting\ReportService;
use Filament\Widgets\StatsOverviewWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

/** "Comunidad": members, memberships and sign-ups. */
class CommunityStats extends StatsOverviewWidget
{
    protected static ?int $sort = 3;

    protected ?string $heading = 'Comunidad';

    protected function getStats(): array
    {
        $c = app(ReportService::class)->communityStats();

        return [
            Stat::make('Alumnos activos', $c['active_members']),
            Stat::make('Nuevos este mes', $c['new_students_this_month']),
            Stat::make('Membresías activas', $c['active_memberships']),
            Stat::make('Por vencer (7 días)', $c['expiring_soon'])
                ->color($c['expiring_soon'] > 0 ? 'warning' : 'gray'),
        ];
    }
}
