<?php

namespace App\Console\Commands;

use App\Models\DriverCheckIn;
use Illuminate\Console\Command;

class AutoCheckoutCheckIns extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'checkins:auto-checkout';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Auto check-out driver sessions that have exceeded 12 hours';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $count = DriverCheckIn::autoCheckoutExpired();

        $this->info("Auto-checked out {$count} driver check-in(s).");

        return self::SUCCESS;
    }
}
