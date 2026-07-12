<?php

namespace App\Filament\Resources\Transactions\Tables;

use App\Enums\TransactionType;
use App\Models\Transaction;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Actions\ForceDeleteBulkAction;
use Filament\Actions\RestoreBulkAction;
use Filament\Forms\Components\DatePicker;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\Filter;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Filters\TrashedFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class TransactionsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->defaultSort('occurred_on', 'desc')
            ->columns([
                TextColumn::make('occurred_on')
                    ->label('Fecha')
                    ->date('d/m/Y')
                    ->sortable(),
                TextColumn::make('type')
                    ->label('Tipo')
                    ->badge(),
                TextColumn::make('category.name')
                    ->label('Categoría')
                    ->formatStateUsing(fn (Transaction $record) => $record->category?->fullName())
                    ->placeholder('—'),
                TextColumn::make('costCenter.name')
                    ->label('Centro de costo')
                    ->placeholder('—'),
                TextColumn::make('paymentMethod.name')
                    ->label('Método')
                    ->placeholder('—'),
                TextColumn::make('account.name')
                    ->label('Caja / cuenta')
                    ->placeholder('—')
                    ->toggleable(),
                TextColumn::make('amount')
                    ->label('Monto')
                    ->sortable(),
                IconColumn::make('invoice_issued')
                    ->label('Factura')
                    ->boolean()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                SelectFilter::make('type')
                    ->label('Tipo')
                    ->options(TransactionType::options()),
                SelectFilter::make('cost_center_id')
                    ->label('Centro de costo')
                    ->relationship('costCenter', 'name'),
                SelectFilter::make('payment_method_id')
                    ->label('Método de pago')
                    ->relationship('paymentMethod', 'name'),
                Filter::make('occurred_on')
                    ->schema([
                        DatePicker::make('from')->label('Desde'),
                        DatePicker::make('until')->label('Hasta'),
                    ])
                    ->query(fn (Builder $query, array $data) => $query
                        ->when($data['from'] ?? null, fn (Builder $q, $date) => $q->whereDate('occurred_on', '>=', $date))
                        ->when($data['until'] ?? null, fn (Builder $q, $date) => $q->whereDate('occurred_on', '<=', $date))),
                TrashedFilter::make(),
            ])
            ->recordActions([
                EditAction::make(),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                    ForceDeleteBulkAction::make(),
                    RestoreBulkAction::make(),
                ]),
            ]);
    }
}
