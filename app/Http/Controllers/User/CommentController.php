<?php

namespace App\Http\Controllers\User;

use App\Http\Controllers\Controller;
use App\Models\Comment;
use App\Models\Like;
use App\Models\Post;
use App\Models\Replay;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;

class CommentController extends Controller
{
    public function createComment(Request $request)
    {
        // validation roles
        $validator = Validator::make($request->all(), [
            'post_id' => 'required|numeric|min:1',
            'comment' => 'required|string'
        ]);

        // check validation
        if ($validator->fails()) {
            return response()->json([
                'status' => false,
                'message'   => $validator->errors()
            ], 422);
        }

        $user = User::where('id', Auth::id())->first();
        if (!$user) {
            return response()->json([
                'status' => false,
                'message' => 'User not found'
            ]);
        }

        $post = Post::where('id', $request->post_id)->first();
        if (!$post) {
            return response()->json([
                'status' => false,
                'message' => 'Post not found'
            ]);
        }

        $comment = Comment::create([
            'post_id'      => $request->post_id,
            'user_id'      => Auth::id(),
            'comment'      => $request->comment
        ], 201);

        return response()->json([
            'status' => true,
            'message' => 'Comment created successful',
            'data' => $comment
        ]);
    }

    public function getComments(Request $request)
    {
        $comments = Comment::where('post_id', $request->post_id)->latest()->get();

        return response()->json([
            'status' => true,
            'message' => 'get comment by post',
            'data' => $comments
        ]);
    }

    public function replay(Request $request)
    {
        // validation roles
        $validator = Validator::make($request->all(), [
            'comment_id' => 'required|numeric|min:1',
            'replay' => 'required|string'
        ]);

        // check validation
        if ($validator->fails()) {
            return response()->json([
                'status' => false,
                'message'   => $validator->errors()
            ], 422);
        }

        $user = User::where('id', Auth::id())->first();
        if (!$user) {
            return response()->json([
                'status' => false,
                'message' => 'User not found'
            ]);
        }

        $comment = Comment::where('id', $request->comment_id)->first();
        if (!$comment) {
            return response()->json([
                'status' => false,
                'message' => 'Comment not found'
            ]);
        }

        $replay = Replay::create([
            'comment_id'      => $request->comment_id,
            'user_id'      => Auth::id(),
            'replay'      => $request->replay
        ], 201);

        return response()->json([
            'status' => true,
            'message' => 'Comment replay created successful',
            'data' => $replay
        ]);
    }

    // public function like(Request $request)
    // {
    //     $commentId = $request->comment_id;

    //     $comment = Comment::where('id', $commentId)->first();
    //     if (!$comment) {
    //         return response()->json([
    //             'status' => false,
    //             'message' => 'Comment not found'
    //         ]);
    //     }

    //     $exists = Comment::where('id', $commentId)->first();

    //     if ($exists) {
    //         $exists->decrement('like');
    //         return response()->json([
    //             'status' => true,
    //             'message' => 'Like removed'
    //         ]);
    //     } else {
    //         $exists->increment('like');
    //         return response()->json([
    //             'status' => true,
    //             'message' => 'Like saved'
    //         ]);
    //     }
    // }

    public function like(Request $request)
    {
        $commentId = $request->comment_id;
        $targetId = Auth::id();

        $comment = Comment::where('id', $commentId)->first();
        if (!$comment) {
            return response()->json([
                'status' => false,
                'message' => 'comment not found'
            ]);
        }

        $exists = Like::where('comment_id', $commentId)
            ->where('user_id', $targetId)
            ->first();

        if ($exists) {
            $comment->decrement('like');
            $exists->delete();
            return response()->json([
                'status' => true,
                'message' => 'Like removed'
            ]);
        } else {
            $comment->increment('like');
            Like::create([
                'user_id' => $targetId,
                'comment_id' => $commentId,
            ]);
            return response()->json([
                'status' => true,
                'message' => 'Like saved'
            ]);
        }
    }

    // public function getCommentWithReplayLike(Request $request)
    // {

    //     $post = Post::with(['comments.user', 'comments.replies'])->where('id',$request->post_id)->get();

    //     $post->tagged = json_decode($post->tagged);
    //         $post->photo = json_decode($post->photo);

    //     $post->transform(function ($post) {
    //         $post->comments->transform(function ($comment) {
    //             return [
    //                 'id' => $comment->id,
    //                 'post_id' => $comment->post_id,
    //                 'user_id' => $comment->user_id,
    //                 'user_name' => $comment->user->name ?? null,
    //                 'avatar' => $comment->user->avatar ?? null,
    //                 'comment' => $comment->comment,
    //                 'like' => $comment->like,
    //                 'created_at' => $comment->created_at,
    //                 'updated_at' => $comment->updated_at,
    //                 'replies' => $comment->replies,
    //             ];
    //         });

    //         return $post;
    //     });

    //     return response()->json([
    //         'status' => true,
    //         'message' => 'get comment by post with replay and like',
    //         'data' => $post
    //     ]);
    // }

    public function getCommentWithReplayLike(Request $request)
    {
        $posts = Post::with(['comments.user', 'comments.replies'])
            ->where('id', $request->post_id)
            ->get();

        $posts->transform(function ($post) {
            // JSON decode
            $post->tagged = json_decode($post->tagged);
            $post->photo = json_decode($post->photo);

            // Total comment count (without replies)
            $post->comment_count = $post->comments->count();

            // Transform comments
            $post->comments->transform(function ($comment) {
                return [
                    'id' => $comment->id,
                    'post_id' => $comment->post_id,
                    'user_id' => $comment->user_id,
                    'user_name' => $comment->user->name ?? null,
                    'avatar' => $comment->user->avatar ?? null,
                    'comment' => $comment->comment,
                    'like' => $comment->like,
                    'created_at' => $comment->created_at,
                    'updated_at' => $comment->updated_at,
                    'replies' => $comment->replies, // If needed, you can transform replies too
                ];
            });

            return $post;
        });

        return response()->json([
            'status' => true,
            'message' => 'get comment by post with replay and like',
            'data' => $posts
        ]);
    }
}
