<?php

namespace App\Http\Controllers\User;

use App\Http\Controllers\Controller;
use App\Models\Bookmark;
use App\Models\Heart;
use App\Models\Post;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;

class BookmarkController extends Controller
{
    public function toggleBookmark(Request $request)
    {
        $postId = $request->post_id;
        $targetId = Auth::id();
        $type = $request->type;

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
                'message' => 'Bookmark removed',
                'isBookmark' => false
            ]);
        } else {
            Bookmark::create([
                'user_id' => $targetId,
                'post_id' => $postId,
                'type' => $type

            ]);
            return response()->json([
                'status' => true,
                'message' => 'bookmark saved',
                'isBookmark' => true
            ]);
        }
    }
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
                ->where('have_it', 'Restaurant')
                ->latest()
                ->paginate(10);
        } elseif ($request->type === '2') {
            $posts = Post::whereIn('id', $bookmarks_id)
                ->where('have_it', 'Home-made')
                ->latest()
                ->paginate(10);
        } else {
            $posts = Post::whereIn('id', $bookmarks_id)
                ->latest()
                ->paginate(10);
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
            $request->type == '1' ? 'restaurant_count' : 'home_made_count' => $request->type == '1' ? $restaurantCount : $homeMadeCount,
            'data' => $posts,
        ]);
    }
    public function viewPost(Request $request)
    {
        $post = Post::with('user:id,name,user_name,avatar,verified_status')->find($request->post_id);

        if (!$post) {
            return response()->json([
                'status' => false,
                'message' => 'Post not found',
            ]);
        }

        $post->tagged = json_decode($post->tagged);
        $post->photo = json_decode($post->photo);

        // ✅ Actual isHeart check for this post (optional: if you track heart at post level)
        $post->isHeart = Heart::where('post_id', $post->id)
            ->where('user_id', Auth::id())
            ->exists();

        $post->isBookmark = Bookmark::where('post_id', $post->id)
            ->where('user_id', Auth::id())
            ->exists();

        $post->avatar = User::where('id', $post->user_id)->first()->avatar;

        return response()->json([
            'status' => true,
            'message' => 'View post',
            'data' => $post
        ]);
    }
    public function getSearchHave_it(Request $request)
    {
        // Step 2: Get current user ID
        $userId = $request->user_id ?? Auth::id();

        // Step 3: Get bookmark post IDs
        $bookmarks_id = Bookmark::where('user_id', $userId)
            ->where('type', $request->type)
            ->pluck('post_id');

        // Step 4: Search logic based on type
        $posts = Post::whereIn('id', $bookmarks_id)
            ->when($request->type == '1', function ($query) use ($request) {
                $query->where('have_it', 'Restaurant')
                    ->where(function ($q) use ($request) {
                        $q->where('meal_name', 'like', '%' . $request->search_have_it . '%')
                            ->orWhere('restaurant_name', 'like', '%' . $request->search_have_it . '%');
                    });
            })
            ->when($request->type == '2', function ($query) use ($request) {
                $query->where('have_it', 'Home-made')
                    ->where('meal_name', 'like', '%' . $request->search_have_it . '%');
            })
            ->get();

        foreach ($posts as $post) {
            $post->photo = json_decode($post->photo);
            $post->tagged = json_decode($post->tagged);
        }

        // Step 5: Return response
        return response()->json([
            'status' => true,
            'message' => 'Search your result',
            'data' => $posts
        ]);
    }
    public function deleteHave_it(Request $request)
    {
        $user = Auth::user();

        if ($request->type == '1') {
            $bookmark = Bookmark::where('post_id', $request->post_id)
                ->where('user_id', $user->id)
                ->where('type', $request->type)
                ->first();

            if (!$bookmark) {
                return response()->json([
                    'status' => false,
                    'message' => 'Restaurant bookmark not found or not authorized to delete'
                ], 404);
            }

            $bookmark->delete();

            return response()->json([
                'status' => true,
                'message' => 'Restaurant bookmark deleted successfully'
            ]);
        } elseif ($request->type == '2') {
            $bookmark = Bookmark::where('post_id', $request->post_id)
                ->where('user_id', $user->id)
                ->where('type', $request->type)
                ->first();

            if (!$bookmark) {
                return response()->json([
                    'status' => false,
                    'message' => 'Home made bookmark not found or not authorized to delete'
                ], 404);
            }

            $bookmark->delete();

            return response()->json([
                'status' => true,
                'message' => 'Home made bookmark deleted successfully'
            ]);
        }
    }

}