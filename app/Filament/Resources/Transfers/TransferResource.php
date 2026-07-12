<?php

namespace App\Filament\Resources\Transfers;

use App\Filament\Resources\Transfers\Pages\ManageTransfers;
use App\Models\Transfer;
use App\Support\Money;
use BackedEnum;
use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class TransferResource extends Resource
{
    protected static ?string $model = Transfer::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedArrowsRightLeft;

    protected static string|\UnitEnum|null $navigationGroup = 'Contabilidad';

    protected static ?string $navigationLabel = 'Transferencias';

    protected static ?string $modelLabel = 'Transferencia';

    protected static ?string $pluralModelLabel = 'Transferencias';

    public static function form(Schema $schema): Schema
    {
        $currency = config('currency.default');
        $symbol = config("currency.currencies.{$currency}.symbol");
        $digits = config("currency.currencies.{$currency}.digits");

        return $schema->components([
            Select::make('from_account_id')
                ->label('Desde')
                ->relationship('fromAccount', 'name')
                ->searchable()
                ->preload()
                ->required(),
            Select::make('to_account_id')
                ->label('Hacia')
                ->relationship('toAccount', 'name')
                ->searchable()
                ->preload()
                ->required()
                ->rules(['different:from_account_id'])
                ->helperText('Debe ser distinta a la cuenta de origen.'),
            TextInput::make('amount')
                ->label('Monto')
                ->rules(['numeric', 'min:0.01'])
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
            TextInput::make('description')
                ->label('Descripción')
                ->maxLength(255)
                ->columnSpanFull(),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->defaultSort('occurred_on', 'desc')
            ->columns([
                TextColumn::make('occurred_on')
                    ->label('Fecha')
                    ->date('d/m/Y')
                    ->sortable(),
                TextColumn::make('fromAccount.name')
                    ->label('Desde'),
                TextColumn::make('toAccount.name')
                    ->label('Hacia'),
                TextColumn::make('amount')
                    ->label('Monto'),
                TextColumn::make('description')
                    ->label('Descripción')
                    ->placeholder('—'),
            ])
            ->recordActions([
                EditAction::make(),
                DeleteAction::make(),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => ManageTransfers::route('/'),
        ];
    }
}
