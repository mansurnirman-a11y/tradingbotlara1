<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\Models\BotInstance;
use App\Models\Trade;

class DashboardController extends Controller
{
    public function index()
    {
        $user = Auth::user();
        
        // Redirect superadmins and admins directly to their global dashboard
        if (in_array($user->role, ['admin', 'superadmin'])) {
            return redirect()->route('admin.dashboard');
        }
        
        $bots = $user->botInstances();
        $totalCapital = $bots->sum('allocated_capital');
        $activeBotsCount = $bots->where('status', 'running')->count();

        $positions = \App\Models\Position::where('user_id', $user->id)->get();
        
        $totalPositions = $positions->count();
        $closedPositions = $positions->where('status', 'CLOSED');
        $closedTradesCount = $closedPositions->count();
        
        // Calculate total Realized PNL
        $runningPnl = $closedPositions->sum('realized_pnl');
        
        // Calculate Win Rate
        $winRate = 0;
        if ($closedTradesCount > 0) {
            $profitableTrades = $closedPositions->where('realized_pnl', '>', 0)->count();
            $winRate = ($profitableTrades / $closedTradesCount) * 100;
        }

        // Generate Chart Data for the last 7 days based on closed trades
        $chartLabels = [];
        $chartData = [];
        $currentDate = \Carbon\Carbon::now()->subDays(6);
        $cumulativePnl = 0;

        for ($i = 0; $i < 7; $i++) {
            $chartLabels[] = $currentDate->format('D'); // Mon, Tue, etc.
            
            // Sum PNL for this specific day
            $dayPnl = $closedPositions->filter(function($pos) use ($currentDate) {
                return $pos->closed_at && $pos->closed_at->format('Y-m-d') === $currentDate->format('Y-m-d');
            })->sum('realized_pnl');
            
            $cumulativePnl += $dayPnl;
            // Display Portfolio Value (Starting with Total Capital + PNL)
            $chartData[] = $totalCapital + $cumulativePnl;

            $currentDate->addDay();
        }

        $recentTrades = Trade::where('user_id', $user->id)
            ->with('botInstance')
            ->orderBy('executed_at', 'desc')
            ->take(5)
            ->get();

        return view('dashboard', compact(
            'totalCapital', 
            'activeBotsCount', 
            'runningPnl', 
            'winRate', 
            'closedTradesCount',
            'recentTrades',
            'chartLabels',
            'chartData'
        ));
    }
}
