<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Setting;
use Illuminate\Http\Request;

class SettingsController extends Controller
{
    public function getSettings()
    {
        $settings = Setting::all()->pluck('value', 'key');

        return response()->json([
            'status' => true,
            'message' => 'Settings',
            'data' => $settings
        ]);
    }
    public function updateSettings(Request $request)
    {
        foreach ($request->all() as $key => $value) {
            Setting::updateOrCreate(['key' => $key], ['value' => $value]);
        }

        return response()->json([
            'status' => true,
            'message' => 'Settings updated successfully'
        ]);
    }
}