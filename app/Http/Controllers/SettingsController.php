<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class SettingsController extends Controller
{
    public function index()
    {
        $user = Auth::user();
        return view('settings.index', compact('user'));
    }

    public function update(Request $request)
    {
        $request->validate([
            'telegram_chat_id' => 'nullable|string|max:100',
            'mobile_number' => 'nullable|string|max:20',
        ]);

        $user = Auth::user();
        $user->telegram_chat_id = $request->telegram_chat_id;
        $user->mobile_number = $request->mobile_number;
        $user->save();

        return back()->with('success', 'Settings updated successfully.');
    }
}
