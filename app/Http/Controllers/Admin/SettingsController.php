<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Setting;
use Illuminate\Http\Request;

class SettingsController extends Controller
{
    // // POST: Save or Update
    // public function termsConditions(Request $request)
    // {

    //     $request->validate([
    //         'content' => 'required|string',
    //     ]);

    //     $term = Setting::first();

    //     if ($term) {
    //         $term->update(['terms_&_conditions' => $request->content]);
    //     } else {
    //         $term = Setting::create(['terms_&_conditions' => $request->content]);
    //     }

    //     return response()->json([
    //         'status' => true,
    //         'message' => 'Terms & Conditions saved successfully',
    //         'data' => $term,
    //     ]);
    // }

    // // GET: Fetch
    // public function getTermsConditions()
    // {
    //     $term = Setting::first();

    //     return response()->json([
    //         'status' => true,
    //         'data' => $term,
    //     ]);
    // }

    // // POST: Save or Update
    // public function privacyPolicy(Request $request)
    // {
    //     $request->validate([
    //         'content' => 'required|string',
    //     ]);

    //     $term = Setting::first();

    //     if ($term) {
    //         $term->update(['privacy_policy' => $request->content]);
    //     } else {
    //         $term = Setting::create(['privacy_policy' => $request->content]);
    //     }

    //     return response()->json([
    //         'status' => true,
    //         'message' => 'Privacy Policy saved successfully',
    //         'data' => $term,
    //     ]);
    // }

    // // GET: Fetch
    // public function getPrivacyPolicy()
    // {
    //     $term = Setting::first();

    //     return response()->json([
    //         'status' => true,
    //         'data' => $term,
    //     ]);
    // }

    // // POST: Save or Update
    // public function ourMission(Request $request)
    // {
    //     $request->validate([
    //         'content' => 'required|string',
    //     ]);

    //     $term = Setting::first();

    //     if ($term) {
    //         $term->update(['our_mission' => $request->content]);
    //     } else {
    //         $term = Setting::create(['our_mission' => $request->content]);
    //     }

    //     return response()->json([
    //         'status' => true,
    //         'message' => 'Our Mission saved successfully',
    //         'data' => $term,
    //     ]);
    // }

    // // GET: Fetch
    // public function getOurMission()
    // {
    //     $term = Setting::first();

    //     return response()->json([
    //         'status' => true,
    //         'data' => $term,
    //     ]);
    // }

    // // GET: Fetch
    // public function getSettings()
    // {
    //     $term = Setting::first();

    //     return response()->json([
    //         'status' => true,
    //         'message' => 'Settings',
    //         'data' => $term,
    //     ]);
    // }

     // GET all settings as key-value pair
    public function getSettings()
    {
        $settings = Setting::all()->pluck('value', 'key');

        return response()->json([
            'status' => true,
            'message' => 'Settings',
            'data' => $settings
        ]);
    }

    // POST or UPDATE single or multiple settings
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