<?php

namespace App\Http\Controllers\User;

use App\Http\Controllers\Controller;
use App\Models\Follower;
use App\Models\Post;
use App\Models\RecentPost;
use App\Models\User;
use App\Models\UserBlock;
use App\Models\UserReport;
use App\Notifications\NewReportCreationNotification;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;


class ProfileController extends Controller
{
    // user profile update by id
    public function updateUserProfile(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'avatar' => 'sometimes|file|mimes:jpeg,png,jpg,webp,gif,svg,heic|max:102400', // 100 MB max
            'name' => 'nullable|string|max:255',
            'bio' => 'nullable|string',
            'contact_number' => 'nullable',
            'country_code' => 'nullable',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status' => false,
                'message' => $validator->errors()
            ], 422);
        }

        $user = User::find(Auth::id());
        if (!$user) {
            return response()->json([
                'status' => false,
                'message' => 'User not found',
            ], 404);
        }

        if ($request->hasFile('avatar')) {

            if ($user->avatar && Storage::disk('public')->exists(str_replace('/storage/', '', $user->avatar))) {
                Storage::disk('public')->delete(str_replace('/storage/', '', $user->avatar));
            }

            $file = $request->file('avatar');
            $filepath = imageUpload(
                $file,
                'avatar',
                'uploads/avatars',
                512,
                512,
                80,
                false
            );

            $user->avatar = '/storage/' . $filepath;
        }

        $user->name = ucfirst($request->name) ?? ucfirst($user->name);
        // $user->user_name = '@' . explode(' ', trim(ucfirst($request->name)))[0] . '_' . rand(0, 9);
        $user->bio = $request->bio ?? $user->bio;
        $user->contact_number = $request->contact_number ?? $user->contact_number;
        $user->country_code = $request->country_code ?? $user->country_code;
        $user->save();

        return response()->json([
            'status' => true,
            'message' => 'Profile updated successfully!',
        ]);
    }
    public function getFollowing(Request $request)
    {
        $followings_id = Follower::where('follower_id', $request->user_id ?? Auth::id())->get()->pluck('user_id');

        $followings = User::select('id', 'name', 'avatar','verified_status')->whereIn('id', $followings_id);

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
            // 'following_count' => formatCount(count($followings)),
            'following_count' => kmCount(count($followings)),
            'data' => $followings
        ]);
    }
    public function getFollower(Request $request)
    {
        $followers_id = Follower::where('user_id', $request->user_id ?? Auth::id())->pluck('follower_id');
        $followings_id = Follower::where('follower_id', Auth::id())->pluck('user_id');

        // followers list
        $followers = User::select('id', 'name', 'avatar','verified_status')->whereIn('id', $followers_id);
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
            'follower_count' => kmCount(count($followers)),
            'data' => $followers
        ]);
    }
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
                'message' => $validator->errors()
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
        $validator = Validator::make($request->all(), [
            'reported_id' => 'required|numeric|exists:users,id',
            'content' => 'required|string'
        ]);
 
        if ($validator->fails()) {
            return response()->json([
                'status' => false,
                'message' => $validator->errors()
            ], 422);
        }

        $user_report = UserReport::create([
            'reporter_id' => Auth::id(),
            'reported_id' => $request->reported_id,
            'content' => $request->content
        ]);

        $notifyUser = User::where('role', 'ADMIN')->first();
        $notifyUser->notify(new NewReportCreationNotification($user_report));

        return response()->json([
            'status' => true,
            'message' => 'User report successfully',
            'data' => $user_report
        ], 201);
    }
    public function deleteRecent(Request $request)
    {
        $user = Auth::user();

        $post = Post::where('id', $request->post_id)
            ->where('user_id', $user->id)
            ->first();

        if (!$post) {
            return response()->json([
                'status' => false,
                'message' => 'Post not found or not authorized to delete'
            ], 404);
        }

        $post->delete();

        return response()->json([
            'status' => true,
            'message' => 'Post deleted successfully'
        ]);
    }
}