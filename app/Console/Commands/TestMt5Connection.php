<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Http;
use App\Models\BrokerAccount;
use App\Services\ExchangeService;

class TestMt5Connection extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'mt5:test {--url=http://127.0.0.1:5000 : MT5 Python Bridge URL} {--symbol=EURUSD : Symbol to test}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Test connection to MT5 Python Bridge and verify account balance, ticker, and candles';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $url = rtrim($this->option('url'), '/');
        $symbol = $this->option('symbol');

        $this->info("==================================================");
        $this->info("  Testing MT5 Python Bridge at: {$url}");
        $this->info("==================================================");

        // 1. Health / Connection check
        $this->line("\n[1/4] Checking MT5 Health & Account Info...");
        try {
            $healthRes = Http::timeout(5)->get("{$url}/health");
            if ($healthRes->successful()) {
                $data = $healthRes->json();
                $this->info("  Status: " . ($data['status'] ?? 'OK'));
                $this->info("  Login / Account: " . ($data['account'] ?? 'N/A'));
                $this->info("  Server: " . ($data['server'] ?? 'N/A'));
                $this->info("  Company: " . ($data['company'] ?? 'N/A'));
                $this->info("  Balance: " . ($data['balance'] ?? '0.00') . ' ' . ($data['currency'] ?? 'USD'));
                $this->info("  Equity: " . ($data['equity'] ?? '0.00') . ' ' . ($data['currency'] ?? 'USD'));
            } else {
                $this->error("  Failed to connect to /health: HTTP " . $healthRes->status());
            }
        } catch (\Throwable $e) {
            $this->error("  Connection error: " . $e->getMessage());
            $this->warn("  Make sure 'python mt5_bridge.py' is running on the terminal.");
            return 1;
        }

        // 2. Ticker check
        $this->line("\n[2/4] Fetching Live Ticker for '{$symbol}'...");
        try {
            $tickerRes = Http::timeout(5)->get("{$url}/ticker", ['symbol' => $symbol]);
            if ($tickerRes->successful()) {
                $t = $tickerRes->json();
                $this->info("  Symbol: " . ($t['symbol'] ?? $symbol));
                $this->info("  Bid: " . ($t['bid'] ?? 'N/A') . " | Ask: " . ($t['ask'] ?? 'N/A') . " | Last: " . ($t['last'] ?? 'N/A'));
            } else {
                $this->warn("  Could not fetch ticker for {$symbol}: " . $tickerRes->body());
            }
        } catch (\Throwable $e) {
            $this->error("  Ticker error: " . $e->getMessage());
        }

        // 3. Candles check
        $this->line("\n[3/4] Fetching Historical 15m Candles for '{$symbol}'...");
        try {
            $candlesRes = Http::timeout(5)->get("{$url}/candles", ['symbol' => $symbol, 'timeframe' => '15m', 'limit' => 5]);
            if ($candlesRes->successful()) {
                $candles = $candlesRes->json();
                $this->info("  Fetched " . count($candles) . " candles successfully.");
                if (!empty($candles)) {
                    $last = end($candles);
                    $this->info("  Latest Candle: Time=" . date('Y-m-d H:i:s', $last[0] / 1000) . " Close=" . $last[4]);
                }
            } else {
                $this->warn("  Could not fetch candles: " . $candlesRes->body());
            }
        } catch (\Throwable $e) {
            $this->error("  Candles error: " . $e->getMessage());
        }

        // 4. Open Positions check
        $this->line("\n[4/4] Checking Open Positions on MT5...");
        try {
            $posRes = Http::timeout(5)->get("{$url}/positions");
            if ($posRes->successful()) {
                $positions = $posRes->json();
                $this->info("  Open Positions count: " . count($positions));
                foreach ($positions as $p) {
                    $this->line("   - Ticket #{$p['ticket']}: {$p['side']} {$p['symbol']} (Volume: {$p['contracts']}) Entry: {$p['entry_price']} | Profit: {$p['profit']}");
                }
            }
        } catch (\Throwable $e) {
            $this->error("  Positions error: " . $e->getMessage());
        }

        $this->info("\n MT5 Bridge verification complete!");
        return 0;
    }
}
