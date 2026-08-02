<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;

class SchedulerHeartbeat extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'scheduler:heartbeat';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Write a heartbeat log line to verify Hostinger cron / schedule:run is working';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $logPath = storage_path('logs/scheduler-heartbeat.log');
        $dir = dirname($logPath);

        if (!File::isDirectory($dir)) {
            File::makeDirectory($dir, 0755, true);
        }

        $line = sprintf(
            "[%s] WORKING — scheduler heartbeat OK (timezone: %s)%s",
            now()->format('Y-m-d H:i:s'),
            config('app.timezone'),
            PHP_EOL
        );

        File::append($logPath, $line);

        $this->info(trim($line));

        return self::SUCCESS;
    }
}
