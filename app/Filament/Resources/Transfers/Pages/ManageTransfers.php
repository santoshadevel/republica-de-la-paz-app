<?php

namespace App\Filament\Resources\Transfers\Pages;

use App\Actions\Accounting\RecordTransfer;
use App\Filament\Resources\Transfers\TransferResource;
use App\Models\Account;
use App\Support\Money;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ManageRecords;

class ManageTransfers extends ManageRecords
{
    protected static string $resource = TransferResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make()
                // Go through the action so the two ledger transactions are created.
                ->using(fn (array $data) => app(RecordTransfer::class)->execute(
                    Account::findOrFail($data['from_account_id']),
                    Account::findOrFail($data['to_account_id']),
                    Money::ofMinor((int) $data['amount']),
                    [
                        'description' => $data['description'] ?? null,
                        'occurred_on' => $data['occurred_on'],
                    ],
                )),
        ];
    }
}
