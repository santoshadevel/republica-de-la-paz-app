<?php

namespace App\Filament\Widgets;

use App\Models\StudentMembership;
use App\Services\Reporting\ReportService;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Filament\Widgets\TableWidget;

/** Alert: memberships expiring within the next 7 days. */
class ExpiringMemberships extends TableWidget
{
    protected static ?int $sort = 4;

    protected int|string|array $columnSpan = 'full';

    protected static ?string $heading = 'Membresías por vencer (próximos 7 días)';

    public function table(Table $table): Table
    {
        return $table
            ->query(fn () => app(ReportService::class)->expiringMembershipsQuery())
            ->emptyStateHeading('Sin membresías por vencer')
            ->columns([
                TextColumn::make('student.first_name')
                    ->label('Alumno')
                    ->formatStateUsing(fn (StudentMembership $record) => $record->student?->fullName()),
                TextColumn::make('plan.name')
                    ->label('Plan')
                    ->placeholder('—'),
                TextColumn::make('ends_at')
                    ->label('Vence')
                    ->date('d/m/Y')
                    ->sortable(),
                TextColumn::make('credits_remaining')
                    ->label('Saldo')
                    ->state(fn (StudentMembership $record) => $record->is_unlimited ? 'Ilimitado' : $record->creditsRemaining()),
            ]);
    }
}
