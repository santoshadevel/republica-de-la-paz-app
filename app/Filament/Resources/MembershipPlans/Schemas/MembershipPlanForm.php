<?php

namespace App\Filament\Resources\MembershipPlans\Schemas;

use App\Support\Money;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Fieldset;
use Filament\Schemas\Schema;

class MembershipPlanForm
{
    public static function configure(Schema $schema): Schema
    {
        $currency = config('currency.default');
        $symbol = config("currency.currencies.{$currency}.symbol");
        $digits = config("currency.currencies.{$currency}.digits");

        return $schema
            ->components([
                TextInput::make('name')
                    ->label('Nombre')
                    ->required()
                    ->maxLength(255),
                TextInput::make('slug')
                    ->label('Identificador')
                    ->maxLength(255)
                    ->unique(ignoreRecord: true)
                    ->helperText('Opcional: se genera del nombre si se deja vacío.'),
                Textarea::make('description')
                    ->label('Descripción')
                    ->columnSpanFull(),
                // Price is stored in minor units by MoneyCast; the field edits
                // major units and converts on load/save (currency from config).
                TextInput::make('price')
                    ->label('Precio')
                    ->required()
                    ->numeric()
                    ->minValue(0)
                    ->default(0)
                    ->prefix($symbol)
                    ->step($digits > 0 ? (10 ** -$digits) : 1)
                    ->formatStateUsing(fn ($state) => $state instanceof Money ? $state->toMajor() : $state)
                    ->dehydrateStateUsing(fn ($state) => Money::ofMajor((float) $state)->minorAmount),
                TextInput::make('sort_order')
                    ->label('Orden')
                    ->required()
                    ->numeric()
                    ->default(0),
                Toggle::make('is_active')
                    ->label('Activo')
                    ->default(true),
                // Behaviour bag. Common rules exposed as friendly fields; the
                // model reads them via ->rule()/->credits()/->isUnlimited().
                Fieldset::make('Reglas del plan')
                    ->schema([
                        Toggle::make('rules.unlimited')
                            ->label('Prácticas ilimitadas')
                            ->live(),
                        TextInput::make('rules.credits')
                            ->label('Créditos de prácticas')
                            ->numeric()
                            ->minValue(0)
                            ->disabled(fn (callable $get) => (bool) $get('rules.unlimited'))
                            ->helperText('Cantidad de prácticas incluidas (vacío si es ilimitado).'),
                        TextInput::make('rules.validity_days')
                            ->label('Vigencia (días)')
                            ->numeric()
                            ->minValue(1),
                        TextInput::make('rules.cancellation.group_hours')
                            ->label('Cancelación grupal (horas antes)')
                            ->numeric()
                            ->minValue(0)
                            ->default(1),
                    ])
                    ->columns(2),
            ]);
    }
}
