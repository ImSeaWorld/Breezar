<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Jobs\SyncFlyInstances;

class SyncFlyCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'fly:sync {--client= : Sync a specific client by ID}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Sync Fly.io instances for all active clients or a specific client';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $clientId = $this->option('client');

        if ($clientId) {
            $this->info("Dispatching Fly.io sync job for client ID: {$clientId}");
        } else {
            $this->info('Dispatching Fly.io sync job for all active clients');
        }

        SyncFlyInstances::dispatch($clientId);

        $this->info('Sync job has been dispatched');

        return Command::SUCCESS;
    }
}
