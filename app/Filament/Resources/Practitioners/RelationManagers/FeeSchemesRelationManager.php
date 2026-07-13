<?php

namespace App\Filament\Resources\Practitioners\RelationManagers;

use App\Enums\FeeType;
use App\Models\FeeScheme;
use App\Support\Money;
use Filament\Actions\CreateAction;
use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

/** Compensation rules (honorarios) for a practitioner, per activity or default. */
class FeeSchemesRelationManager extends RelationManager
{
    protected static string $relationship = 'feeSchemes';

    protected static ?string $title = 'Honorarios';

    public function form(Schema $schema): Schema
    {
        $currency = config('currency.default');
        $symbol = config("currency.currencies.{$currency}.symbol");
        $digits = config("currency.currencies.{$currency}.digits");

        return $schema->components([
            Select::make('activity_id')
                ->label('Actividad / especialidad')
                ->relationship('activity', 'name')
                ->searchable()
                ->preload()
                ->placeholder('Por defecto (todas)')
                ->helperText('Vacío = regla por defecto del profesional.'),
            Select::make('type')
                ->label('Esquema')
                ->options(FeeType::options())
                ->default(FeeType::FixedPerSession->value)
                ->required()
                ->live(),
            TextInput::make('fixed_amount')
                ->label('Monto fijo por sesión')
                ->rules(['numeric', 'min:0'])
                ->inputMode('decimal')
                ->prefix($symbol)
                ->step($digits > 0 ? (10 ** -$digits) : 1)
                ->visible(fn (Get $get) => $get('type') === FeeType::FixedPerSession->value)
                ->formatStateUsing(fn ($state) => $state instanceof Money ? $state->toMajor() : $state)
                ->dehydrateStateUsing(fn ($state) => $state === null || $state === ''
                    ? null
                    : Money::ofMajor((float) $state)->minorAmount),
            TextInput::make('percentage')
                ->label('Porcentaje (%)')
                ->numeric()
                ->minValue(0)
                ->maxValue(100)
                ->visible(fn (Get $get) => $get('type') === FeeType::Percentage->value),
            Textarea::make('notes')
                ->label('Notas')
                ->columnSpanFull(),
        ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('activity.name')
                    ->label('Actividad')
                    ->placeholder('Por defecto'),
                TextColumn::make('type')
                    ->label('Esquema')
                    ->badge(),
                TextColumn::make('fixed_amount')
                    ->label('Monto fijo')
                    ->placeholder('—'),
                TextColumn::make('percentage')
                    ->label('%')
                    ->formatStateUsing(fn (FeeScheme $record) => $record->percentage !== null ? "{$record->percentage}%" : '—'),
            ])
            ->headerActions([
                CreateAction::make(),
            ])
            ->recordActions([
                EditAction::make(),
                DeleteAction::make(),
            ]);
    }
}
