<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Post;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class PostManageController extends Controller
{
    public function getPosts(Request $request)
    {
        $query = Post::where('post_status', 'approved');


        // Search functionality
        if ($request->has('search') && $request->search != '') {
            $searchTerm = $request->search;
            $query->where(function ($q) use ($searchTerm) {
                $q->where('user_name', 'like', "%{$searchTerm}%");
                // ->orWhere('email', 'like', "%{$searchTerm}%");
            });
        }

        $posts = $query->paginate($request->per_page ?? 10);


        $posts->getCollection()->transform(function ($post) {
            return [
                'id' => $post->id,
                'user_id' => $post->user_id,
                'image' =>  $post->user->avatar ?? null, // user avatar,
                'name'  => $post->user->name,
                'location' => $post->location,
                'food_type' => $post->food_type,
                'have_it' => $post->have_it,
            ];
        });

        return response()->json([
            'status' => true,
            'message' => 'All posts',
            'data' => $posts
        ]);
    }


    public function deletePost(Request $request)
    {
        // Validate that user_id is provided
        $validator = Validator::make($request->all(), [
            'post_id' => 'required|integer|gt:0|exists:posts,id',
        ]);


        if ($validator->fails()) {
            return response()->json([
                'status' => false,
                'message'   => $validator->errors()
            ], 422);
        }

        $post = Post::find($request->post_id);

        if (!$post) {
            return response()->json([
                'status' => false,
                'message' => 'Post not found'
            ], 404);
        }

        // Token blacklist করার জন্য ইউজারের token বের করা দরকার
        // $token = JWTAuth::fromUser($user); // ইউজার এর জন্য একটা token তৈরি করবে (যা সে আগে ব্যবহার করেছে ধরে নেওয়া হয়)

        // return $token;

        // JWTAuth::setToken($token)->invalidate(); // blacklist করে দিলাম

        // JWTAuth::invalidate(JWTAuth::getToken($user));

        // Soft delete user
        $post->delete();

        return response()->json([
            'status' => true,
            'message' => 'Post deleted successfully'
        ]);
    }
}
