<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Tymon\JWTAuth\Facades\JWTAuth;

class UserManageController extends Controller
{
    public function getUsers(Request $request)
    {
        $query = User::where('role', 'USER');
            // ->where('verified_status', 'verified');

        if ($request->has('search') && $request->search != '') {
            $searchTerm = $request->search;
            $query->where(function ($q) use ($searchTerm) {
                $q->where('name', 'like', "%{$searchTerm}%")
                    ->orWhere('email', 'like', "%{$searchTerm}%");
            });
        }

        $users = $query->paginate($request->per_page ?? 10);

        $users->getCollection()->transform(function ($user) {
            return [
                'id' => $user->id,
                'avatar_url' => $user->avatar_url,
                'name' => $user->name,
                'email' => $user->email,
                'verified_status' => $user->verified_status
            ];
        });

        return response()->json([
            'status' => true,
            'message' => 'All users',
            'data' => $users
        ]);
    }
    public function viewUser(Request $request)
    {
        $user = User::where('role', 'USER')->where('id', $request->user_id)->first();
        if (!$user) {
            return response()->json([
                'status' => false,
                'message' => 'User not found'
            ]);
        }

        return response()->json([
            'status' => true,
            'message' => 'View user',
            'data' => $user
        ]);
    }

    public function verifiedUnverified(Request $request,$id)
    {
        $user = User::where('role', 'USER')->where('id', $id)->first();

        if (!$user) {
            return response()->json([
                'status' => false,
                'message' => 'User not found'
            ]);
        }

        $user->verified_status = $user->verified_status == 'unverified' ? 'verified' : 'unverified';
        $user->save();

        return response()->json([
            'status' => true,
            'message' => $user->verified_status == 'unverified' ? 'User verified successfully.' : 'User unverified successfully',
            'data' => $user
        ]);
    }

    public function deleteUser(Request $request)
    {
        // Validate that user_id is provided
        $validator = Validator::make($request->all(), [
            'user_id' => 'required|integer|gt:1|exists:users,id',
        ]);


        if ($validator->fails()) {
            return response()->json([
                'status' => false,
                'message' => $validator->errors()
            ], 422);
        }

        $user = User::find($request->user_id);

        if (!$user) {
            return response()->json([
                'status' => false,
                'message' => 'User not found'
            ], 404);
        }

        // Token blacklist করার জন্য ইউজারের token বের করা দরকার
        // $token = JWTAuth::fromUser($user); // ইউজার এর জন্য একটা token তৈরি করবে (যা সে আগে ব্যবহার করেছে ধরে নেওয়া হয়)

        // return $token;

        // JWTAuth::setToken($token)->invalidate(); // blacklist করে দিলাম

        // JWTAuth::invalidate(JWTAuth::getToken($user));

        // Soft delete user
        $user->delete();

        return response()->json([
            'status' => true,
            'message' => 'User deleted successfully'
        ]);
    }
}
