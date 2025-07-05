<?php

namespace App\Http\Controllers\User;

use App\Http\Controllers\Controller;
use App\Models\Heart;
use App\Models\Post;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class HeartController extends Controller
{
    public function toggleHeart(Request $request)
    {

        $postId = $request->post_id;

        $targetId = Auth::id();

        $post = Post::where('id', $postId)->first();
        if (!$post) {
            return response()->json([
                'status' => false,
                'message' => 'Post not found'
            ]);
        }

        if ($post->post_status != 'approved') {
            return response()->json([
                'status' => false,
                'message' => 'Post not approved'
            ]);
        }

        $exists = Heart::where('post_id', $postId)
            ->where('user_id', $targetId)
            ->first();

        if ($exists) {
            $post->decrement('love_reacts');
            $exists->delete();
            return response()->json([
                'status' => true,
                'message' => 'Heart removed'
            ]);
        } else {
            $post->increment('love_reacts');
            Heart::create([
                'user_id' => $targetId,
                'post_id' => $postId,
            ]);
            return response()->json([
                'status' => true,
                'message' => 'Heart saved'
            ]);
        }
    }
}
