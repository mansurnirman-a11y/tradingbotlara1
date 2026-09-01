<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\User;
use App\Events\LiveDataUpdated;
use App\Services\ExchangeService;
use Exception;

class FetchLiveDataDaemon extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'bots:fetch-live-data';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Continuously fetch live balances and bot prices, broadcasting to users via WebSockets';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info("Starting live data daemon...");

        while (true) {
            $users = User::with(['brokerAccounts' => function($q) {
                $q->where('is_active', true);
            }, 'botInstances' => function($q) {
                $q->where('status', 'running');
            }])->get();

            foreach ($users as $user) {
                $balances = [];
                $botPrices = [];

                // Fetch Balances
                foreach ($user->brokerAccounts as $account) {
                    try {
                        $exchange = new ExchangeService($account);
                        $bal = $exchange->getAvailableBalance();
                        $balances[$account->id] = number_format($bal, 2);
                    } catch (Exception $e) {
                        $balances[$account->id] = 'Error/API limits';
                    }
                }

                // Fetch Bot Prices
                foreach ($user->botInstances as $bot) {
                    if ($bot->brokerAccount && $bot->brokerAccount->is_active) {
                        try {
                            $exchange = new ExchangeService($bot->brokerAccount);
                            $price = $exchange->fetchTicker($bot->symbol);
                            $botPrices[$bot->id] = $price ? number_format($price, 2) : '---';
                        } catch (Exception $e) {
                            $botPrices[$bot->id] = '---';
                        }
                    }
                }

                // Broadcast if we have data
                if (count($balances) > 0 || count($botPrices) > 0) {
                    broadcast(new LiveDataUpdated($user->id, $balances, $botPrices));
                }
            }

            // Sleep for 3 seconds before next cycle to prevent rate limits
            sleep(3);
        }
    }
}
