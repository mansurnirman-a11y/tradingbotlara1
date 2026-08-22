<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;

class ImportTvCsv extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'import:tv-csv {file} {user_id=7}';
    protected $description = 'Import TradingView Strategy CSV as fake history for a user';

    public function handle()
    {
        $filePath = $this->argument('file');
        $userId = $this->argument('user_id');

        if (!file_exists($filePath)) {
            $this->error("File not found: {$filePath}");
            return;
        }

        $user = \App\Models\User::find($userId);
        if (!$user) {
            $this->error("User ID {$userId} not found.");
            return;
        }

        // Find or create a dummy bot
        $bot = \App\Models\BotInstance::firstOrCreate(
            ['user_id' => $user->id, 'symbol' => 'BTC/USD'],
            [
                'name' => 'TradingView Backtest Bot',
                'strategy_id' => \App\Models\Strategy::first()->id ?? 1,
                'broker_account_id' => \App\Models\BrokerAccount::where('user_id', $user->id)->first()->id ?? 1,
                'status' => 'stopped',
                'timeframe' => '15m',
                'allocated_capital' => 10000,
                'max_drawdown_pct' => 100,
                'strategy_class' => 'Webhook',
                'parameters' => []
            ]
        );

        $file = fopen($filePath, 'r');
        $header = fgetcsv($file); // Skip header

        $tradesByNum = [];
        while (($row = fgetcsv($file)) !== false) {
            if (count($row) < 10) continue;

            $tradeNum = $row[0];
            $type = $row[1]; // "Entry short", "Exit short", "Entry long", "Exit long"
            $datetime = $row[2];
            $price = $row[4];
            $qty = $row[5];
            $value = $row[6];
            $pnl = $row[7]; // usually only filled on Exit row

            if (!isset($tradesByNum[$tradeNum])) {
                $tradesByNum[$tradeNum] = ['entry' => null, 'exit' => null];
            }

            if (str_contains(strtolower($type), 'entry')) {
                $tradesByNum[$tradeNum]['entry'] = [
                    'side' => str_contains(strtolower($type), 'long') ? 'LONG' : 'SHORT',
                    'datetime' => $datetime,
                    'price' => $price,
                    'qty' => $qty,
                    'value' => $value
                ];
            } else {
                $tradesByNum[$tradeNum]['exit'] = [
                    'datetime' => $datetime,
                    'price' => $price,
                    'pnl' => $pnl
                ];
            }
        }
        fclose($file);

        $positionsCreated = 0;

        foreach ($tradesByNum as $num => $data) {
            if (!$data['entry'] || !$data['exit']) continue;

            $entry = $data['entry'];
            $exit = $data['exit'];

            $entrySide = $entry['side'];
            $exitSide = $entrySide === 'LONG' ? 'SELL' : 'BUY';
            $tradeEntrySide = $entrySide === 'LONG' ? 'BUY' : 'SELL';

            // Create Entry Trade
            \App\Models\Trade::create([
                'bot_instance_id' => $bot->id,
                'user_id' => $user->id,
                'order_id' => 'TV-SIM-EN-' . uniqid(),
                'symbol' => 'BTC/USD',
                'side' => $tradeEntrySide,
                'type' => 'MARKET',
                'price' => $entry['price'],
                'quantity' => $entry['qty'],
                'volume_usd' => $entry['value'],
                'status' => 'FILLED',
                'executed_at' => \Carbon\Carbon::parse($entry['datetime']),
            ]);

            // Create Exit Trade
            \App\Models\Trade::create([
                'bot_instance_id' => $bot->id,
                'user_id' => $user->id,
                'order_id' => 'TV-SIM-EX-' . uniqid(),
                'symbol' => 'BTC/USD',
                'side' => $exitSide,
                'type' => 'MARKET',
                'price' => $exit['price'],
                'quantity' => $entry['qty'],
                'volume_usd' => $entry['qty'] * $exit['price'],
                'status' => 'FILLED',
                'executed_at' => \Carbon\Carbon::parse($exit['datetime']),
            ]);

            // Create Closed Position
            \App\Models\Position::create([
                'bot_instance_id' => $bot->id,
                'user_id' => $user->id,
                'symbol' => 'BTC/USD',
                'side' => $entrySide,
                'quantity' => $entry['qty'],
                'entry_price' => $entry['price'],
                'exit_price' => $exit['price'],
                'status' => 'CLOSED',
                'realized_pnl' => floatval($exit['pnl']),
                'opened_at' => \Carbon\Carbon::parse($entry['datetime']),
                'closed_at' => \Carbon\Carbon::parse($exit['datetime']),
            ]);

            $positionsCreated++;
        }

        $this->info("Successfully imported {$positionsCreated} historical positions for User {$user->name}.");
    }
}
