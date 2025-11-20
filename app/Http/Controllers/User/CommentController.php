<?php

namespace App\Http\Controllers\User;

use App\Http\Controllers\Controller;
use App\Models\Comment;
use App\Models\Heart;
use App\Models\Like;
use App\Models\Post;
use App\Models\Replay;
use App\Models\User;
use App\Notifications\NewCommentNotification;
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
                'message' => $validator->errors()
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
            'post_id' => $request->post_id,
            'user_id' => Auth::id(),
            'comment' => $request->comment
        ]);


        // post user
        $post_user_id = Post::where('id', $request->post_id)->first()->user_id;

        $notifyUser = User::where('id', $post_user_id)->first();

        // Notify post user
        $notifyUser->notify(new NewCommentNotification($comment));



        return response()->json([
            'status' => true,
            'message' => 'Comment created successful',
            'data' => $comment
        ]);
    }

    public function deleteComment(Request $request)
    {
        $authUser = Auth::user();

        $comment = Comment::find($request->comment_id);

        // যদি comment না পাওয়া যায়
        if (!$comment) {
            return response()->json([
                'status' => false,
                'message' => 'Comment not found',
            ], 404);
        }

        // Post fetch
        $post = Post::find($comment->post_id);

        // যদি post না পাওয়া যায়
        if (!$post) {
            return response()->json([
                'status' => false,
                'message' => 'Post not found',
            ], 404);
        }

        // ✅ কেবল post owner অথবা comment owner delete করতে পারবে
        if ($authUser->id !== $post->user_id && $authUser->id !== $comment->user_id) {
            return response()->json([
                'status' => false,
                'message' => 'You are not authorized to delete this comment',
            ], 403);
        }

        // ✅ Delete
        $comment->delete();

        return response()->json([
            'status' => true,
            'message' => 'Comment deleted successfully',
        ]);
    }

    public function getComments(Request $request)
    {
        $comments = Comment::where('post_id', $request->post_id)->latest()->get();

        // foreach ($comments as $comment) {
        //     $comment->post_user_id = Post::where('id',$comment->post_id)->first()->user_id;
        // }

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
                'message' => $validator->errors()
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
            'comment_id' => $request->comment_id,
            'user_id' => Auth::id(),
            'replay' => $request->replay
        ]);

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


    // public function getCommentWithReplayLike(Request $request)
    // {
    //     $posts = Post::with(['comments.user', 'comments.replies'])
    //         ->where('id', $request->post_id)
    //         ->get();

    //     $posts->transform(function ($post) {
    //         // JSON decode
    //         $post->tagged = json_decode($post->tagged);
    //         $post->photo = json_decode($post->photo);

    //         // Total comment count (without replies)
    //         $post->comment_count = $post->comments->count();

    //         // Transform comments
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
    //                 'replies' => $comment->replies, // If needed, you can transform replies too
    //             ];
    //         });

    //         return $post;
    //     });

    //     return response()->json([
    //         'status' => true,
    //         'message' => 'get comment by post with replay and like',
    //         'data' => $posts
    //     ]);
    // }


    // public function getCommentWithReplayLike(Request $request)
    // {
    //     $posts = Post::with(['comments.user', 'comments.replies.user'])
    //         ->where('id', $request->post_id)
    //         ->get();

    //     $isHeart = true;

    //     $posts->transform(function ($post) {
    //         // JSON decode
    //         $post->tagged = json_decode($post->tagged);
    //         $post->photo = json_decode($post->photo);

    //         // Total comment count (excluding replies)
    //         $post->comment_count = $post->comments->count();
    //         $post->isHeart = $isHeart;

    //         // Transform comments
    //         $post->comments->transform(function ($comment) {
    //             // Transform replies with user info
    //             $replies = $comment->replies->map(function ($reply) {
    //                 return [
    //                     'id' => $reply->id,
    //                     'user_id' => $reply->user_id,
    //                     'user_name' => $reply->user->name ?? null,
    //                     'avatar' => $reply->user->avatar ?? null,
    //                     'replay' => $reply->replay,
    //                     'created_at' => $reply->created_at,
    //                 ];
    //             });

    //             return [
    //                 'id' => $comment->id,
    //                 'post_id' => $comment->post_id,
    //                 'user_id' => $comment->user_id,
    //                 'user_name' => $comment->user->name ?? null,
    //                 'avatar' => $comment->user->avatar ?? null,
    //                 'comment' => $comment->comment,
    //                 'like' => $comment->like,
    //                 'reply_count' => $comment->replies->count(), // ✅ Added reply count
    //                 'replies' => $replies,
    //                 'created_at' => $comment->created_at,
    //                 'updated_at' => $comment->updated_at,
    //             ];
    //         });

    //         return $post;
    //     });

    //     return response()->json([
    //         'status' => true,
    //         'message' => 'get comment by post with replay and like',
    //         'data' => $posts
    //     ]);
    // }

    public function getCommentWithReplayLike(Request $request)
    {
        $authId = Auth::id();

        $post_user_id = Post::where('id', $request->post_id)->first()->user_id;

        if ($post_user_id == Auth::id()) {
            $showDeleteButton = true;
        }

        $posts = Post::with([
            'comments.user',
            'comments.replies' => function ($query) {
                $query->latest();
            },
            'comments.replies.user',
        ])
            ->where('id', $request->post_id)
            ->get();

        // $posts->transform(function ($post) use ($authId) {

        //     $post->tagged = json_decode($post->tagged);
        //     $post->photo = json_decode($post->photo);

        //     // Total comment count
        //     $post->comment_count = $post->comments->count();

        //     // ✅ Actual isHeart check for this post (optional: if you track heart at post level)
        //     $post->isHeart = Heart::where('post_id', $post->id)
        //         ->where('user_id', $authId)
        //         ->exists();

        //     // Comments
        //     $post->comments->transform(function ($comment) use ($authId) {
        //         // Replies
        //         $replies = $comment->replies->map(function ($reply) use ($authId) {
        //             return [
        //                 'id' => $reply->id,
        //                 'user_id' => $reply->user_id,
        //                 'user_name' => $reply->user->name ?? null,
        //                 'avatar' => $reply->user->avatar ?? null,
        //                 'replay' => $reply->replay,
        //                 'created_at' => $reply->created_at,
        //             ];
        //         });

        //         return [
        //             'id' => $comment->id,
        //             'post_id' => $comment->post_id,
        //             'user_id' => $comment->user_id,
        //             'user_name' => $comment->user->name ?? null,
        //             'avatar' => $comment->user->avatar ?? null,
        //             'comment' => $comment->comment,
        //             'like' => $comment->like,
        //             'reply_count' => $comment->replies->count(),
        //             'replies' => $replies,
        //             'created_at' => $comment->created_at,
        //             'updated_at' => $comment->updated_at,
        //         ];
        //     });

        //     return $post;
        // });


        // $posts->transform(function ($post) use ($authId,$showDeleteButton) {
        //     $post->tagged = json_decode($post->tagged);
        //     $post->photo = json_decode($post->photo);



        //     // Total comment count
        //     $post->comment_count = kmCount($post->comments->count());

        //     // ❤️ love_reacts কেঃ 1.5K / 2M এ রূপান্তর করো
        //     $post->love_reacts = kmCount($post->love_reacts ?? 0);

        //     // IsHeart
        //     $post->isHeart = Heart::where('post_id', $post->id)
        //         ->where('user_id', $authId)
        //         ->exists();

        //     // Comments 
        //     $post->comments->transform(function ($comment) use ($authId,$showDeleteButton) {
        //         $replies = $comment->replies->map(function ($reply) use ($authId) {
        //             return [
        //                 'id' => $reply->id,
        //                 'user_id' => $reply->user_id,
        //                 'user_name' => $reply->user->name ?? null,
        //                 'avatar' => $reply->user->avatar ?? null,
        //                 'replay' => $reply->replay,
        //                 'created_at' => $reply->created_at,
        //             ];
        //         });

        //         return [
        //             'id' => $comment->id,
        //             'post_id' => $comment->post_id,
        //             'user_id' => $comment->user_id,
        //             'user_name' => $comment->user->name ?? null,
        //             'avatar' => $comment->user->avatar ?? null,
        //             'comment' => $comment->comment,
        //             'like' => kmCount($comment->like),
        //             'reply_count' => kmCount($comment->replies->count()),
        //             'replies' => $replies,
        //             'showDeleteButton' => $showDeleteButton,
        //             'created_at' => $comment->created_at,
        //             'updated_at' => $comment->updated_at,
        //         ];
        //     });

        //     return $post;
        // });



        $posts->transform(function ($post) use ($authId) {
            $post->tagged = json_decode($post->tagged);
            $post->photo = json_decode($post->photo);

            // Total comment count
            $post->comment_count = kmCount($post->comments->count());

            // ❤️ love_reacts কেঃ 1.5K / 2M এ রূপান্তর করো
            $post->love_reacts = kmCount($post->love_reacts ?? 0);

            // IsHeart
            $post->isHeart = Heart::where('post_id', $post->id)
                ->where('user_id', $authId)
                ->exists();

            // Comments 
            $post->comments->transform(function ($comment) use ($authId, $post) {
                $replies = $comment->replies->map(function ($reply) {
                    return [
                        'id' => $reply->id,
                        'user_id' => $reply->user_id,
                        'user_name' => $reply->user->name ?? null,
                        'avatar' => $reply->user->avatar ?? null,
                        'replay' => $reply->replay,
                        'created_at' => $reply->created_at,
                    ];
                });

                // ✅ Show delete button if logged-in user is post owner OR comment owner
                $showDeleteButton = ($post->user_id == $authId || $comment->user_id == $authId);

                return [
                    'id' => $comment->id,
                    'post_id' => $comment->post_id,
                    'user_id' => $comment->user_id,
                    'user_name' => $comment->user->name ?? null,
                    'avatar' => $comment->user->avatar ?? null,
                    'comment' => $comment->comment,
                    'like' => kmCount($comment->like),
                    'reply_count' => kmCount($comment->replies->count()),
                    'replies' => $replies,
                    'showDeleteButton' => $showDeleteButton,
                    'created_at' => $comment->created_at,
                    'updated_at' => $comment->updated_at,
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
