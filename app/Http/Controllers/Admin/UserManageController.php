<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Tymon\JWTAuth\Facades\JWTAuth;

class UserManageController extends Controller
{
    // public function getUsers(Request $request)
    // {
    //     $users = User::where('role', 'USER')
    //         ->where('verified_status', 'verified')
    //         ->paginate($request->per_page ?? 10);

    //     $users->getCollection()->transform(function ($user) {
    //         return [
    //             'image' => $user->avatar ?? null,
    //             'name'  => $user->name,
    //             'email' => $user->email,
    //         ];
    //     });

    //     return response()->json([
    //         'status' => true,
    //         'message' => 'All users',
    //         'data' => $users
    //     ]);
    // }

    public function getUsers(Request $request)
    {
        $query = User::where('role', 'USER')
            ->where('verified_status', 'verified');

        // Search functionality
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
                'image' => $user->avatar ?? null,
                'name'  => $user->name,
                'email' => $user->email,
            ];
        });

        return response()->json([
            'status' => true,
            'message' => 'All users',
            'data' => $users
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
                'message'   => $validator->errors()
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
