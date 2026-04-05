<?php

namespace App\Console\Commands;

use App\Models\User;
use Illuminate\Console\Command;

class DeactivateExpiredSubscriptions extends Command
{
    protected $signature = 'subscriptions:deactivate-expired';

    protected $description = 'Set users with past subscription end date to inactive (SaaS billing)';

    public function handle(): int
    {
        $count = User::query()
            ->where('is_active', true)
            ->whereNotNull('subscription_ends_at')
            ->where('subscription_ends_at', '<=', now())
            ->update(['is_active' => false]);

        $this->info("Deactivated {$count} user(s) with expired subscriptions.");

        return self::SUCCESS;
    }
}
