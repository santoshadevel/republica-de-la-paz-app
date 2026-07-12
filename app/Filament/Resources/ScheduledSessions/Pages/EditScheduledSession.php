<?php

namespace App\Filament\Resources\ScheduledSessions\Pages;

use App\Filament\Resources\ScheduledSessions\ScheduledSessionResource;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

class EditScheduledSession extends EditRecord
{
    protected static string $resource = ScheduledSessionResource::class;

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
        ];
    }
}
