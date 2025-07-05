<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;

class MyProfileController extends Controller
{
    // admin profile update
    public function updateAdminProfile(Request $request)
    {
        // validation roles
        $validator = Validator::make($request->all(), [
            'avatar'            => 'required|image|mimes:jpeg,png,jpg,gif|max:2048', // 2MB max
            'first_name'        => 'required|string|max:255',
            'last_name'         => 'required|string',
            'contact_number'    => 'required|string',
            'location'          => 'required|string',

        ]);

        // check validation
        if ($validator->fails()) {
            return response()->json([
                'status'    => false,
                'message'   => $validator->errors()
            ], 422);
        }

        $user = User::find(Auth::id());

        // User Not Found
        if (!$user) {
            return response()->json([
                'status'  => false,
                'message' => 'User not found',
            ], 404);
        }

        if ($request->hasFile('avatar')) {
            if ($user->avatar && file_exists(public_path($user->avatar))) {
                unlink(public_path($user->avatar));
            }

            $file      = $request->file('avatar');
            $filename  = time() . '_' . $file->getClientOriginalName();
            $filepath  = $file->storeAs('avatars', $filename, 'public');
        }

        // avatar update
        $user->avatar = '/storage/' . $filepath;

        // update user name and bio
        $user->name = $user->name = trim(($request->first_name ?? '') . ' ' . ($request->last_name ?? ''));
        $user->contact_number = $request->contact_number;
        $user->location = $request->location;
        $user->save();

        return response()->json([
            'status'  => true,
            'message' => 'Profile updated successfully!',
        ]);
    }
}
