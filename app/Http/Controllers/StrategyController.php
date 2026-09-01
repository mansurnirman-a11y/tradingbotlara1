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
            $data['class_name'] = 'Webhook';
        } else {
            $rawClass = $data['class_name'] ?? '';
            $norm = strtolower(preg_replace('/[^a-zA-Z0-9]/', '', $rawClass));
            if ($norm === 'inbuildsupertrend' || $norm === 'supertrend' || $norm === 'supertrendstrategy') {
                $data['class_name'] = \App\Strategies\SupertrendStrategy::class;
            } elseif ($norm === 'emacrossover' || $norm === 'emacrossoverstrategy') {
                $data['class_name'] = \App\Strategies\EmaCrossoverStrategy::class;
            } elseif ($norm === 'rsireversal' || $norm === 'rsistrategy') {
                $data['class_name'] = \App\Strategies\RsiStrategy::class;
            } elseif ($norm === 'macdmomentum' || $norm === 'macdstrategy') {
                $data['class_name'] = \App\Strategies\MacdStrategy::class;
            } elseif ($norm === 'smatrend' || $norm === 'smacrossoverstrategy') {
                $data['class_name'] = \App\Strategies\SmaCrossoverStrategy::class;
            } elseif ($norm === 'bollingerscalper' || $norm === 'bollingerscalpingstrategy') {
                $data['class_name'] = \App\Strategies\BollingerScalpingStrategy::class;
            } elseif (!empty($rawClass) && !str_starts_with($rawClass, 'App\\Strategies\\')) {
                $data['class_name'] = 'App\\Strategies\\' . ltrim($rawClass, '\\');
            }
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
