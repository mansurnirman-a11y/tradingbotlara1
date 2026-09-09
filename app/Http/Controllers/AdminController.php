<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
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
            ->take(10)
            ->get();

        $openPositions = Position::with(['botInstance.brokerAccount', 'user'])
            ->where('status', 'OPEN')
            ->orderBy('opened_at', 'desc')
            ->get();

        $users = User::with(['brokerAccounts'])->withCount('botInstances')->latest()->paginate(20);
        $allBots = BotInstance::with(['brokerAccount', 'user'])->latest()->get();

        return view('admin.dashboard', compact('metrics', 'users', 'chartLabels', 'chartData', 'recentTrades', 'allBots', 'openPositions'));
    }

    public function globalKillSwitch(Request $request)
    {
        $affected = BotInstance::where('status', 'running')
            ->update(['status' => 'stopped']);

        return back()->with('success', "EMERGENCY PROTOCOL ACTIVATED: {$affected} running bots have been instantly halted.");
    }

    public function updateUser(Request $request, User $user)
    {
        if ($request->input('is_active') === 'delete') {
            return $this->deleteUser($request, $user);
        }

        $request->validate([
            'role' => 'nullable|in:user,admin,superadmin',
            'max_bots' => 'required|integer|min:0',
            'is_active' => 'required|in:0,1,delete',
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

    public function deleteUser(Request $request, User $user)
    {
        // Only superadmin or admin can delete users
        if (!in_array(Auth::user()->role, ['superadmin', 'admin'])) {
            return back()->with('error', 'Access Denied: Superadmin privileges required.');
        }

        // Cannot delete yourself
        if ($user->id === Auth::id()) {
            return back()->with('error', 'You cannot delete your own account.');
        }

        // Cannot delete a superadmin account
        if ($user->role === 'superadmin') {
            return back()->with('error', 'Cannot delete a Superadmin account.');
        }

        $userName = $user->name;

        // Cascade delete all related data
        DB::transaction(function () use ($user) {
            // Delete positions and trades linked directly to user or user's bots
            $botIds = BotInstance::withTrashed()->where('user_id', $user->id)->pluck('id');
            Position::where('user_id', $user->id)->orWhereIn('bot_instance_id', $botIds)->delete();
            Trade::where('user_id', $user->id)->orWhereIn('bot_instance_id', $botIds)->delete();

            // Force delete all user bots (including soft-deleted)
            BotInstance::withTrashed()->where('user_id', $user->id)->forceDelete();

            // Force delete broker accounts (including soft-deleted)
            \App\Models\BrokerAccount::withTrashed()->where('user_id', $user->id)->forceDelete();

            // Delete audit logs
            DB::table('audit_logs')->where('user_id', $user->id)->delete();

            // Finally delete the user
            $user->delete();
        });

        return back()->with('success', "✅ User account '{$userName}' and all associated data have been permanently deleted.");
    }
}
