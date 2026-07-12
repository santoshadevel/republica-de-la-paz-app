<?php

namespace App\Filament\Resources\Transactions\Schemas;

use App\Enums\TransactionType;
use App\Models\Category;
use App\Models\PaymentMethod;
use App\Support\Money;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Fieldset;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Schema;

class TransactionForm
{
    public static function configure(Schema $schema): Schema
    {
        $currency = config('currency.default');
        $symbol = config("currency.currencies.{$currency}.symbol");
        $digits = config("currency.currencies.{$currency}.digits");

        return $schema
            ->components([
                Select::make('type')
                    ->label('Tipo')
                    ->options(TransactionType::options())
                    ->default(TransactionType::Income->value)
                    ->required()
                    ->live(),
                TextInput::make('amount')
                    ->label('Monto')
                    ->rules(['numeric', 'min:0'])
                    ->inputMode('decimal')
                    ->required()
                    ->prefix($symbol)
                    ->step($digits > 0 ? (10 ** -$digits) : 1)
                    ->formatStateUsing(fn ($state) => $state instanceof Money ? $state->toMajor() : $state)
                    ->dehydrateStateUsing(fn ($state) => Money::ofMajor((float) $state)->minorAmount),
                DatePicker::make('occurred_on')
                    ->label('Fecha')
                    ->native(false)
                    ->default(now())
                    ->required(),
                Select::make('category_id')
                    ->label('Categoría')
                    ->options(fn (Get $get) => Category::query()
                        ->where('type', $get('type'))
                        ->with('parent')
                        ->get()
                        ->sortBy(fn (Category $c) => $c->fullName())
                        ->mapWithKeys(fn (Category $c) => [$c->id => $c->fullName()]))
                    ->searchable()
                    ->preload(),
                Select::make('cost_center_id')
                    ->label('Centro de costo')
                    ->relationship('costCenter', 'name')
                    ->searchable()
                    ->preload(),
                Select::make('payment_method_id')
                    ->label('Método de pago')
                    ->relationship('paymentMethod', 'name')
                    ->searchable()
                    ->preload()
                    ->live()
                    // Prefill the account from the payment method's default.
                    ->afterStateUpdated(function ($state, callable $set) {
                        $account = $state ? PaymentMethod::find($state)?->default_account_id : null;
                        if ($account) {
                            $set('account_id', $account);
                        }
                    }),
                Select::make('account_id')
                    ->label('Caja / cuenta')
                    ->relationship('account', 'name')
                    ->searchable()
                    ->preload()
                    ->helperText('De dónde entra o sale el dinero.'),
                Textarea::make('description')
                    ->label('Descripción')
                    ->columnSpanFull(),
                Fieldset::make('Facturación')
                    ->schema([
                        Toggle::make('invoice_issued')
                            ->label('¿Se emitió factura?')
                            ->live(),
                        TextInput::make('invoice_number')
                            ->label('Nº de factura')
                            ->visible(fn (Get $get) => (bool) $get('invoice_issued')),
                        TextInput::make('invoice_business_name')
                            ->label('Nombre / razón social')
                            ->visible(fn (Get $get) => (bool) $get('invoice_issued')),
                        TextInput::make('invoice_tax_id')
                            ->label('RUC / Nº fiscal')
                            ->visible(fn (Get $get) => (bool) $get('invoice_issued')),
                        TextInput::make('invoice_tax_condition')
                            ->label('Condición fiscal')
                            ->visible(fn (Get $get) => (bool) $get('invoice_issued')),
                    ])
                    ->columns(2),
            ]);
    }
}
