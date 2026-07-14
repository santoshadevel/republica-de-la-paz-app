<?php

namespace App\Filament\Resources\Accounts;

use App\Enums\AccountType;
use App\Filament\Concerns\AdminOnly;
use App\Filament\Resources\Accounts\Pages\ManageAccounts;
use App\Models\Account;
use App\Support\Money;
use BackedEnum;
use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class AccountResource extends Resource
{
    use AdminOnly;

    protected static ?string $model = Account::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedWallet;

    protected static string|\UnitEnum|null $navigationGroup = 'Contabilidad';

    protected static ?string $navigationLabel = 'Cajas y cuentas';

    protected static ?string $modelLabel = 'Caja / cuenta';

    protected static ?string $pluralModelLabel = 'Cajas y cuentas';

    public static function form(Schema $schema): Schema
    {
        $currency = config('currency.default');
        $symbol = config("currency.currencies.{$currency}.symbol");
        $digits = config("currency.currencies.{$currency}.digits");

        return $schema->components([
            TextInput::make('name')
                ->label('Nombre')
                ->required()
                ->maxLength(255)
                ->unique(ignoreRecord: true),
            Select::make('type')
                ->label('Tipo')
                ->options(AccountType::options())
                ->default(AccountType::Cash->value)
                ->required()
                ->live(),
            TextInput::make('account_number')
                ->label('Nº de cuenta')
                ->maxLength(255)
                ->visible(fn (Get $get) => $get('type') === AccountType::Bank->value),
            TextInput::make('opening_balance')
                ->label('Saldo inicial')
                ->rules(['numeric'])
                ->inputMode('decimal')
                ->default(0)
                ->prefix($symbol)
                ->step($digits > 0 ? (10 ** -$digits) : 1)
                ->formatStateUsing(fn ($state) => $state instanceof Money ? $state->toMajor() : $state)
                ->dehydrateStateUsing(fn ($state) => Money::ofMajor((float) ($state ?: 0))->minorAmount),
            Toggle::make('is_active')
                ->label('Activa')
                ->default(true),
            Textarea::make('notes')
                ->label('Notas')
                ->columnSpanFull(),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('name')
                    ->label('Nombre')
                    ->searchable(),
                TextColumn::make('type')
                    ->label('Tipo')
                    ->badge(),
                TextColumn::make('account_number')
                    ->label('Nº de cuenta')
                    ->placeholder('—'),
                TextColumn::make('opening_balance')
                    ->label('Saldo inicial'),
                TextColumn::make('balance')
                    ->label('Saldo actual')
                    ->state(fn (Account $record) => (string) $record->balance())
                    ->weight('bold'),
                IconColumn::make('is_active')
                    ->label('Activa')
                    ->boolean(),
            ])
            ->defaultSort('name')
            ->recordActions([
                EditAction::make(),
                DeleteAction::make(),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => ManageAccounts::route('/'),
        ];
    }
}
