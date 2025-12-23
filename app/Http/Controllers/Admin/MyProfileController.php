<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;

class MyProfileController extends Controller
{
    public function updateAdminProfile(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'avatar' => 'sometimes|image|mimes:jpeg,png,jpg,gif|max:20480', // 20 MB max
            'name' => 'nullable|string',
            'last_name' => 'nullable',
            'contact_number' => 'nullable|string',
            'location' => 'nullable|string',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status' => false,
                'message' => $validator->errors()
            ], 422);
        }

        $user = Auth::user();

        if (!$user) {
            return response()->json([
                'status' => false,
                'message' => 'User not found',
            ], 404);
        }

        if ($request->hasFile('avatar')) {

            if ($user->avatar && Storage::disk('public')->exists(str_replace('/storage/', '', $user->avatar))) {
                Storage::disk('public')->delete(str_replace('/storage/', '', $user->avatar));
            }

            $file = $request->file('avatar');
            $filepath = imageUpload(
                $file,
                'avatar',
                'uploads/avatars',
                512,
                512,
                80,
                false
            );

            $user->avatar = '/storage/' . $filepath;
        }

        $user->name = $request->name ?? $user->name;
        $user->last_name = $request->last_name ?? $user->last_name;
        $user->contact_number = $request->contact_number ?? $user->contact_number;
        $user->location = $request->location ?? $user->location;
        $user->save();

        return response()->json([
            'status' => true,
            'message' => 'Profile updated successfully!',
            'data' => $user,
        ]);
    }
    public function getAdminProfile()
    {
        $admin = User::where('id', Auth::id())->where('role', 'ADMIN')->first();

        if (!$admin) {
            return response()->json([
                'status' => false,
                'message' => 'User not found',
            ]);
        }

        $admin->last_name = $admin->last_name == null ? '' : $admin->last_name;
        $admin->location = $admin->location == null ? '' : $admin->location;

        return response()->json([
            'status' => true,
            'message' => 'Get profile',
            'data' => $admin
        ]);
    }
}
