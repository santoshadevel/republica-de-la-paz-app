<?php

namespace App\Filament\Resources\Transactions\Widgets;

use App\Models\Transaction;
use App\Support\Money;
use Filament\Widgets\StatsOverviewWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

/** Cash summary for the current month: income, expense and balance. */
class CashSummary extends StatsOverviewWidget
{
    protected function getStats(): array
    {
        $start = now()->startOfMonth()->toDateString();

        // P&L excludes internal transfers between accounts.
        $income = (int) Transaction::query()->income()->notTransfer()->whereDate('occurred_on', '>=', $start)->sum('amount');
        $expense = (int) Transaction::query()->expense()->notTransfer()->whereDate('occurred_on', '>=', $start)->sum('amount');

        return [
            Stat::make('Ingresos del mes', (string) Money::ofMinor($income))
                ->color('success'),
            Stat::make('Egresos del mes', (string) Money::ofMinor($expense))
                ->color('danger'),
            Stat::make('Saldo del mes', (string) Money::ofMinor($income - $expense))
                ->color($income - $expense >= 0 ? 'success' : 'danger'),
        ];
    }
}
