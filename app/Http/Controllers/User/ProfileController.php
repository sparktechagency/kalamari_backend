<?php

namespace App\Http\Controllers\User;

use App\Http\Controllers\Controller;
use App\Models\follower;
use App\Models\Post;
use App\Models\RecentPost;
use App\Models\User;
use App\Models\UserBlock;
use App\Models\UserReport;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;


class ProfileController extends Controller
{
    // user profile update by id
    public function updateUserProfile(Request $request)
    {
        // validation roles
        $validator = Validator::make($request->all(), [
            'avatar'      => 'required|image|mimes:jpeg,png,jpg,gif|max:2048', // 2MB max
            'name'        => 'required|string|max:255',
            'bio'         => 'required|string',

        ]);

        // check validation
        if ($validator->fails()) {
            return response()->json([
                'status'    => false,
                'message'   => $validator->errors()
            ], 422);
        }

        $user = User::find(Auth::id());

        // User Not Found
        if (!$user) {
            return response()->json([
                'status'  => false,
                'message' => 'User not found',
            ], 404);
        }


        if ($request->hasFile('avatar')) {
            if ($user->avatar && file_exists(public_path($user->avatar))) {
                unlink(public_path($user->avatar));
            }

            $file      = $request->file('avatar');
            $filename  = time() . '_' . $file->getClientOriginalName();
            $filepath  = $file->storeAs('avatars', $filename, 'public');
        }

        // avatar update
        $user->avatar = '/storage/' . $filepath;

        // update user name and bio
        $user->name = ucfirst($request->name);
        $user->user_name = '@' . explode(' ', trim(ucfirst($request->name)))[0] . '_' . rand(0, 9);
        $user->bio = $request->bio;
        $user->save();

        return response()->json([
            'status'  => true,
            'message' => 'Profile updated successfully!',
        ]);
    }

    public function getFollowing(Request $request)
    {
        $followings_id = Follower::where('follower_id', $request->user_id ?? Auth::id())->get()->pluck('user_id');

        $followings = User::select('id', 'name', 'avatar')->whereIn('id', $followings_id);

        $followings = $followings->paginate($request->per_page ?? 10);

        // $followings = $followings->map(function ($follower) use ($followings_id) {
        //     $follower->status = $followings_id->contains($follower->id) ? 'following' : 'follow';
        //     return $follower;
        // });
        $followings->getCollection()->transform(function ($follower) use ($followings_id) {
            // $follower->status = $followings_id->contains($follower->id) ? 'following' : 'follow';
            $follower->status = 'Unfollowing';
            return $follower;
        });

        return response()->json([
            'status' => true,
            'message' => $request->user_id ? 'User who following' : 'Who I am following',
            'following_count' => count($followings),
            'data' => !$request->user_id ? $followings : null
        ]);
    }

    public function getFollower(Request $request)
    {
        $followers_id = Follower::where('user_id', $request->user_id ?? Auth::id())->pluck('follower_id');
        $followings_id = Follower::where('follower_id', Auth::id())->pluck('user_id');

        // followers list
        $followers = User::select('id', 'name', 'avatar')->whereIn('id', $followers_id);
        $followers = $followers->paginate($request->per_page ?? 10);

        // $followers = $followers->map(function ($follower) use ($followings_id) {
        //     $follower->status = $followings_id->contains($follower->id) ? 'following' : 'follow';
        //     return $follower;
        // });
        $followers->getCollection()->transform(function ($follower) use ($followings_id) {
            $follower->status = $followings_id->contains($follower->id) ? 'Following' : 'Follow';
            return $follower;
        });

        return response()->json([
            'status' => true,
            'message' => $request->user_id ? 'User followers' : 'My followers',
            'follower_count' => count($followers),
            'data' => !$request->user_id ? $followers : null
        ]);
    }

    // public function recentPost(Request $request)
    // {
    //     $user = User::find(Auth::id());

    //     // User Not Found
    //     if (!$user) {
    //         return response()->json([
    //             'ok' => false,
    //             'message' => 'User not found',
    //         ], 404);
    //     }

    //     $checkUser = RecentPost::where('post_id', $request->post_id)->first();

    //     if (!$checkUser) {
    //         $recent_post = new RecentPost();
    //         $recent_post->user_id = $user->id;
    //         $recent_post->post_id = $request->post_id;
    //         $recent_post->save();
    //     } else {
    //         $checkUser->created_at = Carbon::now();
    //         $checkUser->save();
    //     }

    //     return response()->json([
    //         'status' => true,
    //         'message' => 'Recent post recorded'
    //     ]);
    // }

    // public function getRecentPost(Request $request)
    // {
    //     $recent_posts = RecentPost::latest()->get();

    //     $postIds = $recent_posts->pluck('post_id')->toArray();


    //     // return $postIds;

    //     $recent_posts =  Post::whereIn('id', $postIds)->paginate($request->per_page ?? 10);

    //     foreach ($recent_posts as $recent_post) {
    //         $recent_post->tagged = json_decode($recent_post->tagged);
    //         $recent_post->photo = json_decode($recent_post->photo);
    //     }

    //     return response()->json([
    //         'status' => true,
    //         'message' => 'My recent posts',
    //         'data' => $recent_posts
    //     ]);
    // }

    // public function getRecentPost(Request $request)
    // {
    //     $perPage = $request->per_page ?? 10;

    //     // Step 1: Get paginated recent posts
    //     $recentPaginated = RecentPost::orderBy('created_at', 'desc')->paginate($perPage);

    //     // Step 2: Collect post IDs in order
    //     $postIds = $recentPaginated->pluck('post_id')->toArray();

    //     // Step 3: Fetch posts maintaining the same order
    //     $posts = Post::whereIn('id', $postIds)->get()->keyBy('id');

    //     // Step 4: Maintain the order of posts same as postIds
    //     $orderedPosts = collect($postIds)->map(function ($id) use ($posts) {
    //         $post = $posts[$id] ?? null;
    //         if ($post) {
    //             $post->tagged = json_decode($post->tagged);
    //             $post->photo = json_decode($post->photo);
    //         }
    //         return $post;
    //     })->filter();

    //     // Step 5: Set ordered posts into paginated object
    //     $recentPaginated->setCollection($orderedPosts);

    //     return response()->json([
    //         'status' => true,
    //         'message' => 'My recent posts',
    //         'data' => $recentPaginated,
    //     ]);
    // }

    public function getMyPosts(Request $request)
    {
        $my_posts = Post::where('user_id', $request->user_id ?? Auth::id())->latest()->paginate($request->per_page ?? 10);

        foreach ($my_posts as $my_post) {
            $my_post->tagged = json_decode($my_post->tagged);
            $my_post->photo = json_decode($my_post->photo);
        }

        return response()->json([
            'status' => true,
            'message' => $request->user_id ? 'User posts' : 'My posts',
            'data' => $my_posts
        ]);
    }

    public function userBlock(Request $request)
    {
        // validation roles
        $validator = Validator::make($request->all(), [
            'blocked_id' => 'required|numeric|exists:users,id',
        ]);

        // check validation
        if ($validator->fails()) {
            return response()->json([
                'status' => false,
                'message'   => $validator->errors()
            ], 422);
        }

        $blocker_id = Auth::id();
        $blocked_id = $request->blocked_id;

        if ($blocker_id == $blocked_id) {
            return response()->json(['message' => 'Cannot block yourself'], 422);
        }

        $alreadyBlocked = UserBlock::where('blocker_id', $blocker_id)
            ->where('blocked_id', $blocked_id)
            ->first();

        if ($alreadyBlocked) {
            return response()->json(['message' => 'Already blocked'], 200);
        }

        $user_block = UserBlock::create([
            'blocker_id' => $blocker_id,
            'blocked_id' => $blocked_id,
        ]);

        return response()->json([
            'status' => true,
            'message' => 'User blocked successfully',
            'data' => $user_block
        ], 201);
    }

    public function userReport(Request $request)
    {
        // validation roles
        $validator = Validator::make($request->all(), [
            'reported_id' => 'required|numeric|exists:users,id',
            'content' => 'required|string'
        ]);

        // check validation
        if ($validator->fails()) {
            return response()->json([
                'status' => false,
                'message'   => $validator->errors()
            ], 422);
        }


        $user_report = UserReport::create([
            'reporter_id' => Auth::id(),
            'reported_id' => $request->reported_id,
            'content' => $request->content,
        ]);

        return response()->json([
            'status' => true,
            'message' => 'User report successfully',
            'data' => $user_report
        ], 201);
    }
}