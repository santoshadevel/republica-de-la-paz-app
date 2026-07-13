<?php

namespace App\Filament\Widgets;

use App\Services\Reporting\ReportService;
use Filament\Widgets\StatsOverviewWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

/** "Este mes": income, expense, result and margin. */
class BusinessState extends StatsOverviewWidget
{
    protected static ?int $sort = 2;

    protected ?string $heading = 'Estado del negocio (mes)';

    protected function getStats(): array
    {
        $b = app(ReportService::class)->monthlyBusinessState();

        return [
            Stat::make('Ingresos del mes', (string) $b['income'])->color('success'),
            Stat::make('Egresos del mes', (string) $b['expense'])->color('danger'),
            Stat::make('Resultado', (string) $b['result'])
                ->color($b['result']->minorAmount >= 0 ? 'success' : 'danger'),
            Stat::make('Margen', $b['margin'].'%'),
        ];
    }
}
