<?php

namespace App\Filament\Widgets;

use App\Filament\Concerns\AdminOnlyWidget;
use App\Services\Reporting\ReportService;
use Filament\Widgets\StatsOverviewWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

/** "Hoy": operational summary of the day. */
class TodayOverview extends StatsOverviewWidget
{
    use AdminOnlyWidget;

    protected static ?int $sort = 1;

    protected ?string $heading = 'Hoy';

    protected function getStats(): array
    {
        $s = app(ReportService::class)->todaySummary();

        return [
            Stat::make('Alumnos agendados', $s['scheduled_students']),
            Stat::make('Prácticas', $s['group_sessions']),
            Stat::make('Acompañamientos', $s['individual_sessions']),
            Stat::make('Eventos', $s['events']),
            Stat::make('Ingresos del día', (string) $s['income'])->color('success'),
            Stat::make('Egresos del día', (string) $s['expense'])->color('danger'),
            Stat::make('Saldo del día', (string) $s['balance'])
                ->color($s['balance']->minorAmount >= 0 ? 'success' : 'danger'),
        ];
    }
}
