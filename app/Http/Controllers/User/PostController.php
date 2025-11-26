<?php

namespace App\Http\Controllers\User;

use App\Http\Controllers\Controller;
use App\Models\Bookmark;
use App\Models\Follower;
use App\Models\Heart;
use App\Models\Post;
use App\Models\User;
use App\Models\UserBlock;
use App\Notifications\Me\NewPostCreated as MeNewPostCreated;
use App\Notifications\NewFollowNotification;
use App\Notifications\NewPostCreated;
use App\Notifications\NewPostCreationNotification;
use App\Services\PushNotificationService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;

class PostController extends Controller
{
    public function createPost(Request $request, PushNotificationService $pushNotificationService)
    {
        $validator = Validator::make($request->all(), [
            'meal_name' => 'required|string',
            'have_it' => 'required|string|in:1,2',
            'restaurant_name' => 'nullable|string',
            'food_type' => 'required|string',
            'location' => 'nullable|string',
            'lat' => 'nullable|string',
            'lng' => 'nullable|string',
            'description' => 'required|string',
            'rating' => 'nullable|string',
            'tagged' => 'sometimes|array',
            'images' => 'required|array|max:5',
            'images.*' => 'image|mimes:jpeg,png,jpg,webp,gif,svg|max:20480', //20 mb
        ]);

        // Custom conditional validation after base validation
        $validator->after(function ($validator) use ($request) {
            if ($request->have_it == 1) {
                if (!$request->restaurant_name) {
                    $validator->errors()->add('restaurant_name', 'The restaurant name field is required when have_it is 1.');
                }
                if (!$request->location) {
                    $validator->errors()->add('location', 'The location field is required when have_it is 1.');
                }
                if (!$request->lat) {
                    $validator->errors()->add('lat', 'The lat field is required when have_it is 1.');
                }
                if (!$request->lng) {
                    $validator->errors()->add('lng', 'The lng field is required when have_it is 1.');
                }
                if (!$request->rating) {
                    $validator->errors()->add('rating', 'The rating field is required when have_it is 1.');
                }
            }
        });

        if ($validator->fails()) {
            return response()->json([
                'status' => false,
                'errors' => $validator->errors()
            ], 422);
        }

        $user = User::find(Auth::id());
        if (!$user) {
            return response()->json([
                'status' => false,
                'message' => 'User not found',
            ], 404);
        }

        $paths = [];
        foreach ($request->file('images') as $image) {
            if ($user->photo && file_exists(public_path($user->photo))) {
                unlink(public_path($user->photo));
            }
            $paths[] = '/storage/' . $image->store('posts', 'public');
        }

        $post = Post::create([
            'user_id' => Auth::id(),
            'user_name' => Auth::user()->name,
            'meal_name' => $request->meal_name,
            'have_it' => $request->have_it == 1 ? 'Restaurant' : 'Homemade',
            'restaurant_name' => $request->restaurant_name ?? null,
            'food_type' => $request->food_type,
            'location' => $request->location ?? null,
            'latitude' => $request->lat ?? null,
            'longitude' => $request->lng ?? null,
            'description' => $request->description,
            'rating' => $request->rating ?? null,
            'tagged' => json_encode($request->tagged),
            'tagged_count' => $request->tagged ? count($request->tagged) - 1 : 0,
            'photo' => json_encode($paths) ?? null
        ]);


        Auth::user()->notify(new MeNewPostCreated($post));
        // $device_token = Auth::user()->device_token;
        // $pushNotificationService->sendNotification(
        //     $device_token,
        //     'New post added',
        //     'You' . ' post added successfully.',
        //     [
        //         'user_id' => $post->user_id,
        //         'post_id' => $post->id,
        //         'redirect' => 'post_id'
        //     ]
        // );


        $followers_id = Follower::where('user_id', Auth::id())->pluck('follower_id');
        // $followers_id = Follower::where('user_id', Auth::id())->pluck('follower_id')->filter()->values();
        $users = User::whereIn('id', $followers_id)->get();
        foreach ($users as $user) {
            $user->notify(new NewPostCreated($post));
            // $device_token = $user->device_token;
            // $pushNotificationService->sendNotification(
            //     $device_token,
            //     'New post',
            //     Auth::user()->user_name . ' added a post.',
            //     [
            //         'user_id' => $post->user_id,
            //         'post_id' => $post->id,
            //         'redirect' => 'post_id'
            //     ]
            // );
        }

        $notifyAdmin = User::where('role', 'ADMIN')->first();
        $notifyAdmin->notify(new NewPostCreationNotification($post));
         
        return response()->json([
            'status' => true,
            'message' => 'Post created successful',
            'data' => $post
        ]);
    }
    public function searchFollower(Request $request)
    {
        $followers_id = Follower::where('user_id', Auth::id())->pluck('follower_id');
        $followers = User::select('id', 'name', 'avatar')->whereIn('id', $followers_id);
        if ($request->filled('search')) {
            $followers = $followers->where('name', 'LIKE', "%" . $request->search . "%");
        }
        $followers = $followers->paginate($request->per_page ?? 10);
        if ($followers->isEmpty()) {
            return response()->json([
                'status' => false,
                'message' => 'No users found',
            ]);
        }

        return response()->json([
            'status' => true,
            'message' => 'Search result',
            'data' => $followers,
        ]);
    }
    public function following(Request $request)
    {
        $authId = Auth::id();

        // following id get
        $followings_id = Follower::where('follower_id', $authId)->pluck('user_id');

        // approved post paginate get
        $followings = Post::where('post_status', 'approved')
            ->whereIn('user_id', $followings_id)
            ->latest() // add latest
            // ->inRandomOrder() // 🔀 ORDER BY RAND()/RANDOM() of sql
            ->paginate($request->per_page ?? 10);
        // ->get();

        // every post status add
        $followings->transform(function ($post) {
            $post->tagged = json_decode($post->tagged);
            $post->photo = json_decode($post->photo);
            $post->status = 'Following'; //  status add (no database store)

            // ✅ Actual isHeart check for this post (optional: if you track heart at post level)
            $post->isHeart = Heart::where('post_id', $post->id)
                ->where('user_id', Auth::id())
                ->exists();

            $post->isBookmark = Bookmark::where('post_id', $post->id)
                ->where('user_id', Auth::id())
                ->exists();

            // Get avatar from user relation
            $post->avatar = $post->user->avatar ?? null;

            unset($post->user); // optional: if you don't want to expose full user data

            return $post;
        });

        if ($followings->isEmpty()) {
            return response()->json([
                'status' => false,
                'message' => 'No following posts here',
            ]);
        }

        return response()->json([
            'status' => true,
            'message' => 'Following all posts',
            'data' => $followings
        ]);
    }
    public function discovery(Request $request)
    {
        $authId = Auth::id();
        $perPage = $request->per_page ?? 10;

        $blockedUserIds = UserBlock::where('blocked_id', Auth::id())->pluck('blocker_id')->toArray();

        $latestPosts = Post::whereNotIn('user_id', $blockedUserIds)
            ->orderByDesc('love_reacts')
            ->orderByDesc('created_at') // fallback, if, love_reacts is equal
            ->paginate($perPage ?? 10);
        // ->get();

        if ($latestPosts->isEmpty()) {
            return response()->json([
                'status' => false,
                'message' => 'No discovery posts here',
            ]);
        }

        $followingIds = Follower::where('follower_id', $authId)->pluck('user_id')->toArray();

        // Transform
        $latestPosts->transform(function ($post) use ($authId, $followingIds) {
            $post->tagged = json_decode($post->tagged);
            $post->photo = json_decode($post->photo);

            // ✅ Actual isHeart check for this post (optional: if you track heart at post level)
            $post->isHeart = Heart::where('post_id', $post->id)
                ->where('user_id', $authId)
                ->exists();

            $post->isBookmark = Bookmark::where('post_id', $post->id)
                ->where('user_id', Auth::id())
                ->exists();

            if ($post->user_id == $authId) {
                $post->status = null;
            } elseif (in_array($post->user_id, $followingIds)) {
                $post->status = 'Following';
            } else {
                $post->status = 'Follow';
            }

            // Get avatar from user relation
            $post->avatar = $post->user->avatar ?? null;

            unset($post->user); // optional: if you don't want to expose full user data

            return $post;
        });

        return response()->json([
            'status' => true,
            'message' => 'Discovery',
            'data' => $latestPosts,
        ]);
    }
    public function discoveryToggleFollow(Request $request)
    {
        $userId = $request->user_id;
        $targetId = Auth::id();

        $user = User::where('id', $userId)->first();
        if (!$user) {
            return response()->json([
                'status' => false,
                'message' => 'User not found'
            ]);
        }

        if ($user->verified_status == 'unverified') {
            return response()->json([
                'status' => false,
                'message' => 'User not verified'
            ]);
        }


        $exists = Follower::where('user_id', $userId)
            ->where('follower_id', $targetId)
            ->first();

        if ($exists) {
            $exists->delete();
            return response()->json([
                'status' => true,
                'message' => 'unfollowed'
            ]);
        } else {
            $Follower = Follower::create([
                'user_id' => $userId,
                'follower_id' => $targetId,
            ]);

            $notifyUser = User::where('id', $request->user_id)->first();
            // Notify user
            $notifyUser->notify(new NewFollowNotification($Follower));

            return response()->json([
                'status' => true,
                'message' => 'followed'
            ]);
        }
    }
    public function userSearch(Request $request)
    {
        $users = User::where('name', 'like', '%' . $request->user_name . '%')
            ->select('id', 'name', 'avatar')
            ->get();

        return response()->json([
            'status' => true,
            'message' => 'Search your result',
            'data' => $users
        ]);
    }
    public function restaurantSearch(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'latitude' => 'required|numeric',
            'longitude' => 'required|numeric',
            'radius' => 'sometimes|numeric',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status' => false,
                'message' => $validator->errors()
            ], 422);
        }

        $lat = $request->latitude;
        $lng = $request->longitude;
        $radiusInKm = $request->radius ?? 10; // default 10km


        // Subquery: সর্বোচ্চ rating এর post.id প্রতি restaurant group এ
        $subquery = DB::table('posts as p1')
            ->select('p1.id')
            ->whereRaw('p1.restaurant_name = p2.restaurant_name')
            ->orderByDesc('p1.rating')
            ->limit(1);

        // Main Query
        $restaurants = DB::table('posts as p2')
            ->select(
                DB::raw("({$subquery->toSql()}) as id"),
                'p2.photo',
                'p2.restaurant_name',
                'p2.location',
                'p2.latitude',
                'p2.longitude',
                DB::raw('COUNT(*) as post_count'),
                DB::raw('AVG(p2.rating) as average_rating'),
                DB::raw("(
            6371 * acos(
                cos(radians($lat)) * cos(radians(p2.latitude)) *
                cos(radians(p2.longitude) - radians($lng)) +
                sin(radians($lat)) * sin(radians(p2.latitude))
            )
        ) AS distance")
            )
            ->whereNotNull('p2.restaurant_name')
            ->where('p2.have_it', 'Restaurant')
            ->where('p2.post_status', 'approved')
            ->groupBy('p2.photo', 'p2.restaurant_name', 'p2.location', 'p2.latitude', 'p2.longitude')
            ->having('distance', '<=', $radiusInKm)
            ->orderBy('distance')
            ->get();

        if ($request->has('id')) {
            $restaurant = collect($restaurants)->firstWhere('id', $request->id);

            $restaurant->photo = json_decode($restaurant->photo, true);

            if ($restaurant) {
                return response()->json([
                    'status' => true,
                    'message' => 'Single restaurant found',
                    'data' => $restaurant
                ]);
            } else {
                return response()->json([
                    'status' => false,
                    'message' => 'Restaurant not found',
                    'data' => null
                ]);
            }
        }

        foreach ($restaurants as $restaurant) {
            $restaurant->photo = json_decode($restaurant->photo, true);
        }

        return response()->json([
            'status' => true,
            'message' => $request->radius ? "Nearby Restaurants within {$radiusInKm}km" : 'Search by latitude longitude',
            'latest_post_images' => '',
            'center' => [
                'latitude' => $lat,
                'longitude' => $lng,
            ],
            'data' => $request->radius ? $restaurants : $restaurants->first()
        ]);
    }
}
