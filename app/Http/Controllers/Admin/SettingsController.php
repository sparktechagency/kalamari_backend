<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;

class SettingsController extends Controller
{
    public function editProfile(Request $request){
        $user = User::where('id',Auth::id())->where('role','ADMIN')->first();

        // Validation Rules
        $validator = Validator::make($request->all(), [
            'name' => 'required|string',
            'email' => 'required|email',
        ]);

        // Validation Errors
        if ($validator->fails()) {
            return response()->json([
                'status' => false,
                'message' => $validator->errors(),
            ], 422);
        }

        $user->name = $request->name;
        $user->email = $request->email;
        $user->save();

        return response()->json([
            'status' => true,
            'message' => 'Profile update successful',
            'data' => $user
        ], 201);
    }

    public function changePassword(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'current_password' => 'required|min:6',
            'new_password'         => 'required|string|min:6|confirmed'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status' => false,
                'message' => $validator->errors()
            ], 422);
        }

        $user = User::where('id',Auth::id())->where('role','ADMIN')->first();

        if (! $user) {
            return response()->json([
                'status' => false,
                'message' => 'User not found'
            ], 404);
        }

        if (Hash::check($request->current_password, $user->password)) {
            $user->password = Hash::make($request->new_password);
            $user->save();

            return response()->json([
                'status' => true,
                'message' => 'Password updated successfully!',
            ]);
        } else {
            return response()->json([
                'status' => false,
                'message' => 'Invalid current password!',
            ]);
        }
    }

    public function changeAvatar(Request $request)
    {
         $user = User::where('id',Auth::id())->where('role','ADMIN')->first();

        $validator = Validator::make($request->all(), [
            'avatar' => 'required|image|mimes:jpeg,png,jpg,gif|max:2048', // 2MB max
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status' => false,
                'message' => $validator->errors()
            ], 422);
        }

        if ($request->hasFile('avatar')) {
            if ($user->avatar && file_exists(public_path($user->avatar))) {
                unlink(public_path($user->avatar));
            }

            $file      = $request->file('avatar');
            $filename  = time() . '_' . $file->getClientOriginalName();
            $filepath  = $file->storeAs('images', $filename, 'public');

            $user->avatar = '/storage/' . $filepath;
            $user->save();

            return response()->json([
                'status'      => true,
                'message' => 'Avatar updated successfully!',
                'path'    => $user->avatar, 
            ]);
        }

        return response()->json([
            'status' => false,
            'message' => 'No image uploaded!',
        ], 400);
    }
}
