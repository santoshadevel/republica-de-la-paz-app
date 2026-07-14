<?php

namespace App\Filament\Pages;

use App\Filament\Concerns\AdminOnly;
use App\Models\Practitioner;
use App\Services\Reporting\HonorariumService;
use BackedEnum;
use Filament\Pages\Page;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Concerns\InteractsWithTable;
use Filament\Tables\Contracts\HasTable;
use Filament\Tables\Table;
use Illuminate\Support\Carbon;

/** Monthly honorarium liquidation per practitioner. */
class HonorariumLiquidation extends Page implements HasTable
{
    use AdminOnly;
    use InteractsWithTable;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedCalculator;

    protected static string|\UnitEnum|null $navigationGroup = 'Contabilidad';

    protected static ?string $navigationLabel = 'Liquidación de honorarios';

    protected static ?string $title = 'Liquidación de honorarios';

    protected string $view = 'filament.pages.honorarium-liquidation';

    /** Selected month as "Y-m". */
    public string $month = '';

    /** @var array<int, array<string, mixed>> per-request cache of liquidations */
    private array $cache = [];

    public function mount(): void
    {
        $this->month = Carbon::today()->format('Y-m');
    }

    public function table(Table $table): Table
    {
        return $table
            ->query(Practitioner::query()->whereHas('feeSchemes'))
            ->emptyStateHeading('Sin esquemas de honorarios configurados')
            ->paginated(false)
            ->columns([
                TextColumn::make('name')
                    ->label('Profesional')
                    ->state(fn (Practitioner $record) => $record->fullName()),
                TextColumn::make('group_sessions')
                    ->label('Clases')
                    ->state(fn (Practitioner $record) => $this->liquidationFor($record)['group_sessions']),
                TextColumn::make('individual_sessions')
                    ->label('Sesiones')
                    ->state(fn (Practitioner $record) => $this->liquidationFor($record)['individual_sessions']),
                TextColumn::make('events')
                    ->label('Eventos')
                    ->state(fn (Practitioner $record) => $this->liquidationFor($record)['events']),
                TextColumn::make('income_generated')
                    ->label('Ingresos generados')
                    ->state(fn (Practitioner $record) => (string) $this->liquidationFor($record)['income_generated']),
                TextColumn::make('fee_total')
                    ->label('Honorarios a pagar')
                    ->weight('bold')
                    ->state(fn (Practitioner $record) => (string) $this->liquidationFor($record)['fee_total']),
            ]);
    }

    /** @return array<string, mixed> */
    public function liquidationFor(Practitioner $practitioner): array
    {
        return $this->cache[$practitioner->getKey()] ??= app(HonorariumService::class)
            ->liquidate($practitioner, Carbon::parse($this->month.'-01'));
    }
}
