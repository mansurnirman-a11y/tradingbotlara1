<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\BotInstance;
use App\Jobs\EvaluateStrategyJob;

class RunTradingBots extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'bots:run';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Dispatch evaluate jobs for all running bots';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $bots = BotInstance::where('status', 'running')->with('strategy')->get();

        $dispatched = 0;
        foreach ($bots as $bot) {
            // Skip bots using webhook strategies as they are event-driven
            if ($bot->strategy && $bot->strategy->type === 'webhook') {
                continue;
            }
            
            EvaluateStrategyJob::dispatch($bot);
            $dispatched++;
        }

        $this->info("Dispatched {$dispatched} bot jobs to the queue.");
    }
}
