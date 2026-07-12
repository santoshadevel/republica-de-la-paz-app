<?php

namespace App\Filament\Resources\ScheduledSessions\Pages;

use App\Filament\Resources\ScheduledSessions\ScheduledSessionResource;
use Filament\Resources\Pages\CreateRecord;

class CreateScheduledSession extends CreateRecord
{
    protected static string $resource = ScheduledSessionResource::class;
}
