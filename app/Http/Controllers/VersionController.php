<?php

namespace App\Http\Controllers;

use App\Models\Version;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class VersionController extends Controller
{
    public function addVersion(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'version' => [
                'required',
                'regex:/^\d+\.\d+\.\d+$/', // 1.0.2 format
                'unique:versions,version'
            ],
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status' => false,
                'message' => $validator->errors()
            ], 422);
        }

        // optional: old versions inactive
        Version::where('is_active', true)->update(['is_active' => false]);

        $version = Version::create([
            'version' => $request->version,
            'is_active' => true
        ]);

        return response()->json([
            'status' => true,
            'message' => 'Version added successfully',
            'data' => $version
        ]);
    }

    public function getVersion()
    {
        $version = Version::where('is_active', true)
            ->orderByDesc('id')
            ->first();

        if (!$version) {
            return response()->json([
                'status' => false,
                'message' => 'No version found'
            ]);
        }

        return response()->json([
            'status' => true,
            'version' => $version->version
        ]);
    }
}
