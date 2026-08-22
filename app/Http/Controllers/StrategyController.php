<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

use App\Models\Strategy;
use Illuminate\Support\Str;

class StrategyController extends Controller
{
    public function index()
    {
        $strategies = Strategy::latest()->get();
        return view('admin.strategies', compact('strategies'));
    }

    public function store(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'type' => 'required|in:internal,webhook',
            'class_name' => 'nullable|string',
        ]);

        $data = $request->only(['name', 'description', 'type', 'class_name']);
        
        if ($data['type'] === 'webhook') {
            $data['webhook_key'] = Str::random(32);
        }

        Strategy::create($data);

        return back()->with('success', 'Strategy added successfully.');
    }

    public function toggle(Strategy $strategy)
    {
        $strategy->update(['is_active' => !$strategy->is_active]);
        
        $status = $strategy->is_active ? 'activated' : 'deactivated';
        return back()->with('success', "Strategy {$status}.");
    }

    public function destroy(Strategy $strategy)
    {
        if ($strategy->botInstances()->exists()) {
            return back()->with('error', 'Cannot delete strategy because it is currently used by bots. Deactivate it instead.');
        }
        
        $strategy->delete();
        return back()->with('success', 'Strategy deleted.');
    }
}
