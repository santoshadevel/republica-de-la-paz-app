<?php

namespace App\Filament\Resources\ScheduledSessions\Pages;

use App\Filament\Resources\ScheduledSessions\ScheduledSessionResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListScheduledSessions extends ListRecords
{
    protected static string $resource = ScheduledSessionResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
