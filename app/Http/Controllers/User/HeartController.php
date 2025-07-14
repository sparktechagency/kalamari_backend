<?php

namespace App\Http\Controllers\User;

use App\Http\Controllers\Controller;
use App\Models\Heart;
use App\Models\Post;
use App\Models\User;
use App\Notifications\NewReactNotification;
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
                'message' => 'Heart removed',
                'isHeart' => false
            ]);
        } else {
            $post->increment('love_reacts');
            $heart = Heart::create([
                'user_id' => $targetId,
                'post_id' => $postId,
            ]);

            // post user
            $post_user_id = Post::where('id', $request->post_id)->first()->user_id;

            $notifyUser = User::where('id', $post_user_id)->first();

            // Notify post user
            $notifyUser->notify(new NewReactNotification($heart));

            return response()->json([
                'status' => true,
                'message' => 'Heart saved',
                'isHeart' => true
            ]);
        }
    }
}
