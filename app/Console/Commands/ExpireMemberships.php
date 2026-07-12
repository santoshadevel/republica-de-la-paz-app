<?php

namespace App\Console\Commands;

use App\Enums\MembershipStatus;
use App\Models\StudentMembership;
use Illuminate\Console\Command;

/**
 * Marks active memberships whose validity window has passed as expired.
 * Meant to run daily from the scheduler.
 */
class ExpireMemberships extends Command
{
    protected $signature = 'memberships:expire';

    protected $description = 'Marca como vencidas las membresías activas cuya vigencia expiró.';

    public function handle(): int
    {
        $count = StudentMembership::query()
            ->where('status', MembershipStatus::Active->value)
            ->whereDate('ends_at', '<', now()->toDateString())
            ->update(['status' => MembershipStatus::Expired->value]);

        $this->info("Membresías vencidas: {$count}.");

        return self::SUCCESS;
    }
}
