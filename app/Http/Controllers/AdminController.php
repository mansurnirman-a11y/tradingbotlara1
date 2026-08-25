<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\User;
use App\Models\BotInstance;
use App\Models\Trade;
use App\Models\Position;
use Illuminate\Support\Facades\DB;

class AdminController extends Controller
{
    public function index()
    {
        $runningBots = BotInstance::where('status', 'running')->get();
        $totalCapital = $runningBots->sum('allocated_capital');
        $activeBotsCount = $runningBots->count();

        $closedPositions = Trade::where('status', 'FILLED')->get(); // Wait, Realized PNL is in Position model not Trade! Let me use Position model instead.

        $closedPositions = Position::where('status', 'CLOSED')->get();
        $runningPnl = $closedPositions->sum('realized_pnl');
        $closedTradesCount = $closedPositions->count();

        $winRate = 0;
        if ($closedTradesCount > 0) {
            $profitableTrades = $closedPositions->where('realized_pnl', '>', 0)->count();
            $winRate = ($profitableTrades / $closedTradesCount) * 100;
        }

        $metrics = [
            'totalUsers' => User::count(),
            'activeUsers' => User::where('is_active', true)->count(),
            'totalAdmins' => User::whereIn('role', ['admin', 'superadmin'])->count(),
            'totalBots' => BotInstance::count(),
            'totalCapital' => $totalCapital,
            'activeBotsCount' => $activeBotsCount,
            'runningPnl' => $runningPnl,
            'winRate' => $winRate,
            'closedTradesCount' => $closedTradesCount,
        ];

        // Generate Chart Data for the last 7 days based on closed trades globally
        $chartLabels = [];
        $chartData = [];
        $currentDate = \Carbon\Carbon::now()->subDays(6);
        $cumulativePnl = 0;

        for ($i = 0; $i < 7; $i++) {
            $chartLabels[] = $currentDate->format('D'); // Mon, Tue, etc.
            
            // Sum PNL for this specific day globally
            $dayPnl = $closedPositions->filter(function($pos) use ($currentDate) {
                return $pos->closed_at && $pos->closed_at->format('Y-m-d') === $currentDate->format('Y-m-d');
            })->sum('realized_pnl');
            
            $cumulativePnl += $dayPnl;
            // Display Global Portfolio Value (Starting with Total Capital + PNL)
            $chartData[] = $totalCapital + $cumulativePnl;

            $currentDate->addDay();
        }

        $recentTrades = Trade::with(['botInstance', 'user'])
            ->orderBy('executed_at', 'desc')
            ->take(5)
            ->get();

        $users = User::withCount('botInstances')->latest()->paginate(20);

        return view('admin.dashboard', compact('metrics', 'users', 'chartLabels', 'chartData', 'recentTrades'));
    }

    public function globalKillSwitch(Request $request)
    {
        $affected = BotInstance::where('status', 'running')
            ->update(['status' => 'stopped']);

        return back()->with('success', "EMERGENCY PROTOCOL ACTIVATED: {$affected} running bots have been instantly halted.");
    }

    public function updateUser(Request $request, User $user)
    {
        $request->validate([
            'role' => 'nullable|in:user,admin,superadmin',
            'max_bots' => 'required|integer|min:0',
            'is_active' => 'required|in:0,1',
        ]);

        $user->update([
            'role' => $request->role ?? $user->role,
            'is_active' => $request->is_active == '1',
            'max_bots' => $request->max_bots,
        ]);

        return back()->with('success', "User {$user->name} updated successfully.");
    }

    public function showImport()
    {
        return view('admin.import_history');
    }

    public function processImport(Request $request)
    {
        $request->validate([
            'csv' => 'required|file|mimes:csv,txt',
            'user_id' => 'required|exists:users,id'
        ]);

        $path = $request->file('csv')->getRealPath();
        
        $output = [];
        $returnVar = 0;
        $artisan = base_path('artisan');
        exec("php " . escapeshellarg($artisan) . " import:tv-csv " . escapeshellarg($path) . " " . escapeshellarg($request->user_id) . " 2>&1", $output, $returnVar);

        if ($returnVar === 0) {
            return back()->with('success', 'History Imported Successfully! ' . implode('<br>', $output));
        } else {
            return back()->with('error', 'Import Failed: ' . implode('<br>', $output));
        }
    }
}
