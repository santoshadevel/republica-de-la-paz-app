<?php

namespace App\Filament\Resources\MembershipPlans\Schemas;

use App\Enums\ActivityType;
use App\Support\Money;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Select;
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
                    // Not ->numeric(): that adds a NumberStateCast that runs before
                    // formatStateUsing and cannot convert the Money object. We format
                    // the Money to major units here and dehydrate back to minor units.
                    ->rules(['numeric', 'min:0'])
                    ->inputMode('decimal')
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
                // What the public landing shows for this plan. Also part of the
                // rules bag, so a new plan needs no migration and no brand copy
                // ends up hardcoded in a view.
                Fieldset::make('Publicación en la landing')
                    ->schema([
                        Toggle::make('rules.featured')
                            ->label('Destacar como "Más elegido"')
                            ->helperText('Resalta esta tarjeta entre los pases.'),
                        Repeater::make('rules.features')
                            ->label('Beneficios')
                            ->simple(
                                TextInput::make('feature')
                                    ->required()
                                    ->maxLength(120)
                                    ->placeholder('Ej: Acceso a todas las disciplinas grupales'),
                            )
                            ->addActionLabel('Agregar beneficio')
                            ->reorderable()
                            ->default([])
                            ->helperText('Se listan con ✓ en la tarjeta del pase, en el orden de acá.'),
                    ])
                    ->columns(1),
                // Coverage: which activities the plan includes (hybrid model).
                Fieldset::make('Actividades incluidas')
                    ->schema([
                        Select::make('rules.included_types')
                            ->label('Tipos de actividad incluidos')
                            ->multiple()
                            ->options(ActivityType::options())
                            ->helperText('El plan cubre TODAS las actividades de estos tipos.'),
                        Select::make('includedActivities')
                            ->label('Actividades específicas')
                            ->relationship('includedActivities', 'name')
                            ->multiple()
                            ->searchable()
                            ->preload()
                            ->helperText('Actividades puntuales incluidas además de los tipos.'),
                    ])
                    ->columns(1),
            ]);
    }
}
