<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Comment;
use App\Models\Post;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class PostManageController extends Controller
{
    public function getPosts(Request $request)
    {
        $query = Post::where('post_status', 'approved')
            ->with('user');

        if ($request->has('search') && $request->search != '') {
            $search = $request->search;
            $query->whereHas('user', function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%");
            });
        }

        $posts = $query->paginate($request->per_page ?? 10);

        $posts->getCollection()->transform(function ($post) {
            return [
                'post_id' => $post->id,
                'user_id' => $post->user_id,
                'avatar_url' => $post->user->avatar_url,
                'name' => $post->user->name ?? null,
                'location' => $post->location,
                'food_type' => $post->food_type,
                'have_it' => $post->have_it,
                'restaurant_name' => $post->restaurant_name
            ];
        });

        return response()->json([
            'status' => true,
            'message' => 'All posts',
            'data' => $posts
        ]);
    }
    public function getPost(Request $request){
        $post = Post::where('id', $request->post_id)->first();

        if (!$post) {
            return response()->json([
                'status'=> false,
                'message'=> 'Post not found'
            ]);
        }
        
        $post->photo = json_decode($post->photo, true);
        $post->tagged = json_decode($post->tagged, true);
        $post->commentCounts = Comment::where('post_id', $post->id)->get()->count();
        $post->avatar_url = User::where('id',$post->user_id)->first()->avatar_url;

        return response()->json([
            'status'=> true,
            'message'=> 'View post',
            'data'=> $post
        ]);
    }
    public function deletePost(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'post_id' => 'required|integer|gt:0|exists:posts,id',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status' => false,
                'message' => $validator->errors()
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