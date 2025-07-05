<?php

namespace App\Http\Controllers\User;

use App\Http\Controllers\Controller;
use App\Models\Bookmark;
use App\Models\Post;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class BookmarkController extends Controller
{
    public function toggleBookmark(Request $request)
    {
        $postId = $request->post_id;
        $targetId = Auth::id();
        $type     = $request->type;

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

        $exists = Bookmark::where('post_id', $postId)
            ->where('user_id', $targetId)
            ->first();

        if ($exists) {
            $exists->delete();
            return response()->json([
                'status' => true,
                'message' => 'Bookmark removed'
            ]);
        } else {
            Bookmark::create([
                'user_id' => $targetId,
                'post_id' => $postId,
                'type'   => $type

            ]);
            return response()->json([
                'status' => true,
                'message' => 'bookmark saved'
            ]);
        }
    }

    // public function getBookmarks(Request $request)
    // {
    //     $bookmarks_id = Bookmark::where('user_id', $request->user_id ?? Auth::id())
    //         ->pluck('post_id');

    //     // Count for both types
    //     $restaurantCount = Post::whereIn('id', $bookmarks_id)
    //         ->where('have_it', 'Restaurant')
    //         ->count();

    //     $homeMadeCount = Post::whereIn('id', $bookmarks_id)
    //         ->where('have_it', 'Home-made')
    //         ->count();

    //     if ($request->have_it == 'Restaurant') {
    //         $posts = Post::whereIn('id', $bookmarks_id)
    //             ->where('have_it', 'Restaurant')
    //             ->latest()
    //             ->paginate($request->per_page ?? 10);

    //             $posts->data['restaurantCount'] = $restaurantCount;

    //     } elseif ($request->have_it == 'Home-made') {
    //         $posts = Post::whereIn('id', $bookmarks_id)
    //             ->where('have_it', 'Home-made')
    //             ->latest()
    //             ->paginate($request->per_page ?? 10);

    //             $posts->data['homeMadeCount'] = $homeMadeCount;

    //     }

    //     if ($posts->isEmpty()) {
    //         return response()->json([
    //             'status' => false,
    //             'message' => 'You have not bookmarked any posts'
    //         ]);
    //     }

    //     foreach ($posts as $post) {
    //         $post->tagged = json_decode($post->tagged);
    //         $post->photo = json_decode($post->photo);
    //     }

    //     return response()->json([
    //         'status' => true,
    //         'message' => 'Bookmarks',
    //         'data' => $posts
    //     ]);
    // }

    public function getBookmarks(Request $request)
    {
        $userId = $request->user_id ?? Auth::id();

        $bookmarks_id = Bookmark::where('user_id', $userId)->where('type', $request->type)->pluck('post_id');

        // Count for both types
        $restaurantCount = Post::whereIn('id', $bookmarks_id)
            ->count();

        $homeMadeCount = Post::whereIn('id', $bookmarks_id)
            ->count();

        // Filter based on have_it
        if ($request->type === '1') {
            $posts = Post::whereIn('id', $bookmarks_id)
                ->latest()
                ->paginate($request->per_page ?? 10);
        } elseif ($request->type === '2') {
            $posts = Post::whereIn('id', $bookmarks_id)
                ->latest()
                ->paginate($request->per_page ?? 10);
        } else {
            $posts = Post::whereIn('id', $bookmarks_id)
                ->latest()
                ->paginate($request->per_page ?? 10);
        }

        // If no post found
        if ($posts->isEmpty()) {
            return response()->json([
                'status' => false,
                'message' => 'You have not bookmarked any posts',
                'restaurant_count' => $restaurantCount,
                'home_made_count' => $homeMadeCount,
            ]);
        }

        foreach ($posts as $post) {
            $post->tagged = json_decode($post->tagged);
            $post->photo = json_decode($post->photo);
        }

        return response()->json([
            'status' => true,
            'message' => 'Bookmarks',
            $request->type == '1' ? 'restaurant_count' : 'home_made_count' =>  $request->type == '1' ? $restaurantCount : $homeMadeCount,
            'data' => $posts,
        ]);
    }


    public function viewPost(Request $request)
    {

        $post = Post::find($request->post_id);

        if (!$post) {
            return response()->json([
                'status' => false,
                'message' => 'Post not found',
            ]);
        }


        $post->tagged = json_decode($post->tagged);
        $post->photo = json_decode($post->photo);


        return response()->json([
            'status' => true,
            'message' => 'View post',
            'data' => $post
        ]);
    }

    public function getSearchHave_it(Request $request)
    {
        if ($request->type == '1') {
            $posts = Post::where('restaurant_name', 'like', '%' . $request->search_have_it . '%')
                ->where('have_it', $request->type == '1' ? 'Restaurant' : null)
                ->get();
        } elseif ($request->type == '2') {
            $posts = Post::where('restaurant_name', 'like', '%' . $request->search_have_it . '%')
                ->where('have_it', $request->type == '2' ? 'Home-made' : null)
                ->get();
        }

        return response()->json([
            'status' => true,
            'message' => 'Search your result',
            'data' => $posts
        ]);
    }
}