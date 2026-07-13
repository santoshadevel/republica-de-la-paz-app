<?php

namespace App\Filament\Resources\MembershipOrders\Pages;

use App\Filament\Resources\MembershipOrders\MembershipOrderResource;
use Filament\Resources\Pages\ListRecords;

class ListMembershipOrders extends ListRecords
{
    protected static string $resource = MembershipOrderResource::class;
}
