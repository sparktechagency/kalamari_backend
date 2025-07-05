<?php

namespace App\Http\Controllers\User;

use App\Http\Controllers\Controller;
use App\Models\Follower;
use App\Models\Post;
use App\Models\User;
use App\Models\UserBlock;
use App\Notifications\Me\NewPostCreated as MeNewPostCreated;
use App\Notifications\NewPostCreated;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;

class PostController extends Controller
{
    public function createPost(Request $request)
    {
        // validation roles
        $validator = Validator::make($request->all(), [
            'meal_name'       => 'required|string',
            'have_it'         => 'required|string|in:1,2', // নিশ্চিতভাবে 1 বা 2 হতে হবে
            'restaurant_name' => 'nullable|string',
            'food_type'       => 'required|string',
            'location'        => 'nullable|string',
            'lat'        => 'nullable|string',
            'lng'        => 'nullable|string',
            'description'     => 'required|string',
            'rating'          => 'nullable|string',
            'tagged'          => 'sometimes|array',
            'images'          => 'required|array|max:3', // max 3 image
            'images.*'        => 'image|mimes:jpeg,png,jpg,gif,svg|max:2048',
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

        // User Not Found
        if (!$user) {
            return response()->json([
                'status'  => false,
                'message' => 'User not found',
            ], 404);
        }


        // store image max 3
        $paths = [];
        foreach ($request->file('images') as $image) {
            if ($user->photo && file_exists(public_path($user->photo))) {
                unlink(public_path($user->photo));
            }
            $paths[] = '/storage/' . $image->store('posts', 'public');
        }

        $post = Post::create([
            'user_id'     => Auth::id(),
            'user_name'   => Auth::user()->name,
            'meal_name'   => $request->meal_name,
            'have_it'     => $request->have_it == 1 ? 'Restaurant' : 'Home-made',
            'restaurant_name'   => $request->restaurant_name ?? null,
            'food_type'   => $request->food_type,
            'location'    => $request->location ?? null,
            'lat'    => $request->lat ?? null,
            'lng'    => $request->lng ?? null,
            'description' => $request->description,
            'rating'      => $request->rating ?? null,
            'tagged'      => json_encode($request->tagged),
            'tagged_count' => $request->tagged ? count($request->tagged) - 1 : 0,
            'photo'       => json_encode($paths) ?? null
        ]);

        // 🔔 Notify all users
        $users = User::where('id', '!=', Auth::id())->get(); // excluding the creator
        // Notify me
        Auth::user()->notify(new MeNewPostCreated($post));
        // Notify all without me
        foreach ($users as $user) {
            $user->notify(new NewPostCreated($post));
        }

        return response()->json([
            'status' => true,
            'message' => 'Post created successful with notification',
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

    // own
    // public function following(Request $request)
    // {

    //     $posts = Post::where('post_status', 'approved')->get();

    //     if ($posts->isEmpty()) {
    //         return response()->json([
    //             'status' => false,
    //             'message' => 'No following posts',
    //         ]);
    //     }

    //     $followings_id = Follower::where('follower_id', Auth::id())->get()->pluck('user_id');

    //     $followings = Post::whereIn('user_id', $followings_id)->paginate($request->per_page ?? 10);


    //     foreach ($followings as $following) {
    //         $following->tagged = json_decode($following->tagged);
    //         $following->photo = json_decode($following->photo);
    //     }

    //     return response()->json([
    //         'status' => true,
    //         'message' => 'Following all posts',
    //         'data' => $followings
    //     ]);
    // }

    // status add (use getCollection()->transform())
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

        // every post status add
        $followings->getCollection()->transform(function ($post) {
            $post->tagged = json_decode($post->tagged);
            $post->photo = json_decode($post->photo);
            $post->status = 'Following'; //  status add (no database store)
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

    // own
    // public function discovery(Request $request)
    // {

    //     $posts = Post::where('post_status', 'approved')->paginate($request->per_page ?? 10);

    //     if ($posts->isEmpty()) {
    //         return response()->json([
    //             'status' => false,
    //             'message' => 'No discovery posts',
    //         ]);
    //     }

    //     foreach ($posts as $post) {
    //         $post->tagged = json_decode($post->tagged);
    //         $post->photo = json_decode($post->photo);
    //     }

    //     return response()->json([
    //         'status' => true,
    //         'message' => 'Discovery',
    //         'data' => $posts,

    //     ]);
    // }

    // use map()
    // public function discovery(Request $request)
    // {
    //     // সব user এবং তাদের সর্বশেষ approved post আনবে
    //     $users = User::with(['latestApprovedPost'])->get();

    //     // শুধুমাত্র যাদের latestApprovedPost আছে
    //     $usersWithPosts = $users->filter(function ($user) {
    //         return $user->latestApprovedPost !== null;
    //     })->map(function ($user) {
    //         $post = $user->latestApprovedPost;
    //         $post->tagged = json_decode($post->tagged);
    //         $post->photo = json_decode($post->photo);
    //         return $post;
    //     });

    //     if ($usersWithPosts->isEmpty()) {
    //         return response()->json([
    //             'status' => false,
    //             'message' => 'No discovery posts',
    //         ]);
    //     }

    //     return response()->json([
    //         'status' => true,
    //         'message' => 'Discovery',
    //         'data' => $usersWithPosts->values(), // reset index
    //     ]);
    // }

    // use getCollection()->transform()
    // public function discovery(Request $request)
    // {
    //     // সব user এবং তাদের সর্বশেষ approved post আনবে
    //     $users = User::with('latestApprovedPost')->paginate($request->per_page ?? 10);

    //     // Collection থেকে শুধু যাদের latestApprovedPost আছে তাদের নিয়ে কাজ করা
    //     $filtered = $users->getCollection()->filter(function ($user) {
    //         return $user->latestApprovedPost !== null;
    //     })->values(); // index reset

    //     // transform করে post modify করা (tagged/photo decode)
    //     $users->setCollection(
    //         $filtered->transform(function ($user) {
    //             $post = $user->latestApprovedPost;
    //             $post->tagged = json_decode($post->tagged);
    //             $post->photo = json_decode($post->photo);
    //             return $post;
    //         })
    //     );

    //     if ($users->isEmpty()) {
    //         return response()->json([
    //             'status' => false,
    //             'message' => 'No discovery posts',
    //         ]);
    //     }

    //     return response()->json([
    //         'status' => true,
    //         'message' => 'Discovery',
    //         'data' => $users, // paginate object return হচ্ছে
    //     ]);
    // }

    // use getCollection()->transform() + have follow/unfollow use follower table.
    // public function discovery(Request $request)
    // {
    //     $authId = Auth::id();

    //     // Auth user কাদের follow করে রেখেছে তাদের user_id সংগ্রহ করি
    //     $followingIds = Follower::where('follower_id', $authId)->pluck('user_id')->toArray();

    //     // সব user এবং তাদের সর্বশেষ approved post আনবে
    //     $users = User::with('latestApprovedPost')->paginate($request->per_page ?? 10);

    //     // শুধুমাত্র যাদের latestApprovedPost আছে
    //     $filtered = $users->getCollection()->filter(function ($user) {
    //         return $user->latestApprovedPost !== null;
    //     })->values();

    //     // transform করে tagged/photo decode + follow status add করা হচ্ছে
    //     $users->setCollection(
    //         $filtered->transform(function ($user) use ($followingIds) {
    //             $post = $user->latestApprovedPost;
    //             $post->tagged = json_decode($post->tagged);
    //             $post->photo = json_decode($post->photo);
    //             $post->status = in_array($post->user_id, $followingIds) ? 'Following' : 'Follow';
    //             return $post;
    //         })
    //     );

    //     if ($users->isEmpty()) {
    //         return response()->json([
    //             'status' => false,
    //             'message' => 'No discovery posts',
    //         ]);
    //     }

    //     return response()->json([
    //         'status' => true,
    //         'message' => 'Discovery',
    //         'data' => $users,
    //     ]);
    // }

    // public function discovery(Request $request)
    // {
    //     $authId = Auth::id();

    //     // Auth user যাদের follow করে, তাদের user_id গুলি
    //     $followingIds = Follower::where('follower_id', $authId)->pluck('user_id')->toArray();

    //     // সব user এবং তাদের সর্বশেষ approved post আনবে
    //     $users = User::with('latestApprovedPost')->paginate($request->per_page ?? 10);

    //     // শুধুমাত্র যাদের latestApprovedPost আছে
    //     $filtered = $users->getCollection()->filter(function ($user) {
    //         return $user->latestApprovedPost !== null;
    //     })->values();

    //     // transform: photo/tagged decode + status add
    //     $users->setCollection(
    //         $filtered->transform(function ($user) use ($authId, $followingIds) {
    //             $post = $user->latestApprovedPost;
    //             $post->tagged = json_decode($post->tagged);
    //             $post->photo = json_decode($post->photo);

    //             // status নির্ধারণ
    //             if ($post->user_id == $authId) {
    //                 $post->status = null;
    //             } elseif (in_array($post->user_id, $followingIds)) {
    //                 $post->status = 'Following';
    //             } else {
    //                 $post->status = 'Follow';
    //             }

    //             return $post;
    //         })
    //     );

    //     if ($users->isEmpty()) {
    //         return response()->json([
    //             'status' => false,
    //             'message' => 'No discovery posts',
    //         ]);
    //     }

    //     return response()->json([
    //         'status' => true,
    //         'message' => 'Discovery',
    //         'data' => $users,
    //     ]);
    // }

    // public function discovery(Request $request)
    // {
    //     $authId = Auth::id();

    //     // Auth user যাদের follow করে, তাদের user_id গুলি
    //     $followingIds = Follower::where('follower_id', $authId)->pluck('user_id')->toArray();

    //     // সব user এবং তাদের সর্বশেষ approved post আনবে
    //     $users = User::with('latestApprovedPost')->get();

    //     // শুধুমাত্র যাদের latestApprovedPost আছে
    //     $filtered = $users->filter(function ($user) {
    //         return $user->latestApprovedPost !== null;
    //     });

    //     // প্রতিটি ইউজারের latest post কে collect করো
    //     $posts = $filtered->map(function ($user) use ($authId, $followingIds) {
    //         $post = $user->latestApprovedPost;
    //         $post->tagged = json_decode($post->tagged);
    //         $post->photo = json_decode($post->photo);

    //         if ($post->user_id == $authId) {
    //             $post->status = null;
    //         } elseif (in_array($post->user_id, $followingIds)) {
    //             $post->status = 'Following';
    //         } else {
    //             $post->status = 'Follow';
    //         }

    //         return $post;
    //     });

    //     // সর্বশেষ post খুঁজে বের করো
    //     $latestPost = $posts->sortByDesc('created_at')->first();

    //     // ওই post কে সবথেকে উপরে বসাও
    //     $posts = $posts->reject(function ($post) use ($latestPost) {
    //         return $post->id === $latestPost->id;
    //     });

    //     $finalPosts = collect([$latestPost])->merge($posts)->values();

    //     // Pagination manually
    //     $perPage = $request->per_page ?? 10;
    //     $page = $request->page ?? 1;
    //     $paged = $finalPosts->forPage($page, $perPage);

    //     return response()->json([
    //         'status' => true,
    //         'message' => 'Discovery',
    //         'data' => [
    //             'current_page' => (int)$page,
    //             'per_page' => (int)$perPage,
    //             'total' => $finalPosts->count(),
    //             'last_page' => ceil($finalPosts->count() / $perPage),
    //             'data' => $paged->values(),
    //         ],
    //     ]);
    // }

    // use getCollection()->transform() + have follow/unfollow use follower table + trending if use
    // public function discovery(Request $request)
    // {
    //     $authId = Auth::id();
    //     $perPage = $request->per_page ?? 10;

    //     $followingIds = Follower::where('follower_id', $authId)->pluck('user_id')->toArray();

    //     // per user last approved post get subquery
    //     $latestPosts = Post::select('posts.*')
    //         ->join(
    //             DB::raw('(SELECT user_id, MAX(created_at) as latest_created FROM posts WHERE post_status = "approved" GROUP BY user_id) as latest'),
    //             function ($join) {
    //                 $join->on('posts.user_id', '=', 'latest.user_id')
    //                     ->on('posts.created_at', '=', 'latest.latest_created');
    //             }
    //         )
    //         ->orderByDesc('posts.love_reacts') // 🔥 trending post per user
    //         ->orderByDesc('posts.created_at') // fallback sort
    //         // ->orderBy('posts.created_at', 'desc')
    //         ->paginate($perPage);

    //     // ✅ Check if no post found
    //     if ($latestPosts->isEmpty()) {
    //         return response()->json([
    //             'status' => false,
    //             'message' => 'No discovery posts here',
    //         ]);
    //     }

    //     // Transform with status, decode
    //     $latestPosts->getCollection()->transform(function ($post) use ($authId, $followingIds) {
    //         $post->tagged = json_decode($post->tagged);
    //         $post->photo = json_decode($post->photo);

    //         if ($post->user_id == $authId) {
    //             $post->status = null;
    //         } elseif (in_array($post->user_id, $followingIds)) {
    //             $post->status = 'Following';
    //         } else {
    //             $post->status = 'Follow';
    //         }

    //         return $post;
    //     });

    //     return response()->json([
    //         'status' => true,
    //         'message' => 'Discovery',
    //         'data' => $latestPosts,
    //     ]);
    // }

    // public function discovery(Request $request)
    // {
    //     $authId = Auth::id();
    //     $perPage = $request->per_page ?? 10;

    //     $followingIds = Follower::where('follower_id', $authId)->pluck('user_id')->toArray();

    //     // Subquery to get each user's most loved approved post
    //     $latestPosts = Post::select('posts.*')
    //         ->join(
    //             DB::raw('(
    //             SELECT user_id, MAX(love_reacts) as max_love
    //             FROM posts
    //             WHERE post_status = "approved"
    //             GROUP BY user_id
    //         ) as loved'),
    //             function ($join) {
    //                 $join->on('posts.user_id', '=', 'loved.user_id')
    //                     ->on('posts.love_reacts', '=', 'loved.max_love');
    //             }
    //         )
    //         ->where('post_status', 'approved')
    //         ->orderByDesc('posts.love_reacts') // Sort by most loved
    //         ->paginate($perPage);

    //     if ($latestPosts->isEmpty()) {
    //         return response()->json([
    //             'status' => false,
    //             'message' => 'No discovery posts here',
    //         ]);
    //     }

    //     // Transform post data
    //     $latestPosts->getCollection()->transform(function ($post) use ($authId, $followingIds) {
    //         $post->tagged = json_decode($post->tagged);
    //         $post->photo = json_decode($post->photo);

    //         if ($post->user_id == $authId) {
    //             $post->status = null;
    //         } elseif (in_array($post->user_id, $followingIds)) {
    //             $post->status = 'Following';
    //         } else {
    //             $post->status = 'Follow';
    //         }

    //         return $post;
    //     });

    //     return response()->json([
    //         'status' => true,
    //         'message' => 'Discovery',
    //         'data' => $latestPosts,
    //     ]);
    // }

    // public function discovery(Request $request)
    // {
    //     $authId = Auth::id();
    //     $perPage = $request->per_page ?? 10;

    //     $followingIds = Follower::where('follower_id', $authId)->pluck('user_id')->toArray();

    //     // MySQL 8+ required for ROW_NUMBER()
    //     $latestPosts = Post::select('posts.*')
    //         ->join(DB::raw('(
    //         SELECT *
    //         FROM (
    //             SELECT *,
    //                 ROW_NUMBER() OVER (PARTITION BY user_id ORDER BY love_reacts DESC, created_at DESC) as rn
    //             FROM posts
    //             WHERE post_status = "approved"
    //         ) as ranked
    //         WHERE ranked.rn = 1
    //     ) as latest'), function ($join) {
    //             $join->on('posts.id', '=', 'latest.id');
    //         })
    //         ->orderByDesc('posts.love_reacts')
    //         ->orderByDesc('posts.created_at')
    //         ->paginate($perPage);

    //     if ($latestPosts->isEmpty()) {
    //         return response()->json([
    //             'status' => false,
    //             'message' => 'No discovery posts here',
    //         ]);
    //     }

    //     $latestPosts->getCollection()->transform(function ($post) use ($authId, $followingIds) {
    //         $post->tagged = json_decode($post->tagged);
    //         $post->photo = json_decode($post->photo);

    //         if ($post->user_id == $authId) {
    //             $post->status = null;
    //         } elseif (in_array($post->user_id, $followingIds)) {
    //             $post->status = 'Following';
    //         } else {
    //             $post->status = 'Follow';
    //         }

    //         return $post;
    //     });

    //     return response()->json([
    //         'status' => true,
    //         'message' => 'Discovery',
    //         'data' => $latestPosts,
    //     ]);
    // }

    public function discovery(Request $request)
    {
        $authId = Auth::id();
        $perPage = $request->per_page ?? 10;

        // // Subquery: ১ জন ইউজারের ক্ষেত্রে নিজের love_reacts বেশি অথবা latest post
        // // অন্যদের জন্য latest approved post
        // $latestPosts = Post::select('posts.*')
        //     ->join(DB::raw('(
        //     SELECT *
        //     FROM (
        //         SELECT *,
        //             ROW_NUMBER() OVER (
        //                 PARTITION BY user_id
        //                 ORDER BY
        //                     CASE
        //                         WHEN user_id = ' . $authId . ' AND love_reacts > 0 THEN 1
        //                         WHEN user_id = ' . $authId . ' THEN 2
        //                         ELSE 3
        //                     END,
        //                     love_reacts DESC,
        //                     created_at DESC
        //             ) as rn
        //         FROM posts
        //         WHERE post_status = "approved"
        //     ) as ranked
        //     WHERE ranked.rn = 1
        // ) as latest'), function ($join) {
        //         $join->on('posts.id', '=', 'latest.id');
        //     })
        //     ->orderByDesc('posts.created_at')
        //     ->paginate($perPage);

        $blockedUserIds = UserBlock::where('blocked_id', Auth::id())->pluck('blocker_id')->toArray();

        $latestPosts = Post::whereNotIn('user_id', $blockedUserIds)
            ->orderByDesc('love_reacts')
            ->orderByDesc('created_at') // fallback, if, love_reacts is equal
            ->paginate($perPage);

        if ($latestPosts->isEmpty()) {
            return response()->json([
                'status' => false,
                'message' => 'No discovery posts here',
            ]);
        }

        $followingIds = Follower::where('follower_id', $authId)->pluck('user_id')->toArray();

        // Transform
        $latestPosts->getCollection()->transform(function ($post) use ($authId, $followingIds) {
            $post->tagged = json_decode($post->tagged);
            $post->photo = json_decode($post->photo);

            if ($post->user_id == $authId) {
                $post->status = null;
            } elseif (in_array($post->user_id, $followingIds)) {
                $post->status = 'Following';
            } else {
                $post->status = 'Follow';
            }

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
            Follower::create([
                'user_id' => $userId,
                'follower_id' => $targetId,
            ]);
            return response()->json([
                'status' => true,
                'message' => 'followed'
            ]);
        }
    }

    public function userSearch(Request $request)
    {
        $users = User::where('name', 'like', '%' . $request->user_name . '%')
            ->select('id', 'name','avatar')
            ->get();

        return response()->json([
            'status' => true,
            'message' => 'Search your result',
            'data' => $users
        ]);
    }

    public function restaurantSearch(Request $request)
    {
        // $locationName = $request->location;

        // if (!$locationName) {
        //     return response()->json([
        //         'status' => false,
        //         'message' => 'Location name is required'
        //     ]);
        // }




        // $lat = $request->lat;
        // $lng = $request->lng;
        // $radius = $request->radius; // km radius

        // // Haversine formula
        // $restaurants = Post::select('*')
        //     ->selectRaw("(6371 * acos(cos(radians(?)) * cos(radians(lat)) * cos(radians(lng) - radians(?)) + sin(radians(?)) * sin(radians(lat)))) AS distance", [
        //         $lat,
        //         $lng,
        //         $lat
        //     ])
        //     ->where('have_it', 'Restaurant')
        //     ->having('distance', '<=', $radius)
        //     ->orderBy('distance')
        //     ->get();

        // return response()->json([
        //     'status' => true,
        //     'message' => 'Nearby Restaurants from: ' . $locationName,
        //     'coordinates' => ['lat' => $lat, 'lng' => $lng, 'radius' => $radius],
        //     'data' => $restaurants,
        // ]);

        // validation roles
        $validator = Validator::make($request->all(), [
            'location' => 'required|string',
            'lat' => 'required|string',
            'lng' => 'required|string',
            'radius' => 'sometimes|numeric',
        ]);

        // check validation
        if ($validator->fails()) {
            return response()->json([
                'status' => false,
                'message'   => $validator->errors()
            ], 422);
        }

        // Optional: Limit search range in kilometers
        $radiusInKm = $request->radius ?? 10;

        // Step 1: Get coordinates from location (from posts table)
        $centerPost = Post::where('location', 'like', "%{$request->location}%")
            ->whereNotNull('lat')
            ->whereNotNull('lng')
            ->first();

        if (!$centerPost) {
            return response()->json([
                'status' => false,
                'message' => 'No posts found in this location'
            ]);
        }

        $lat = $centerPost->lat;
        $lng = $centerPost->lng;

        // Step 2: Fetch nearby restaurants using Haversine Formula
        $restaurants = Post::select(
            'restaurant_name',
            'location',
            'lat',
            'lng',
            DB::raw('COUNT(*) as post_count'),
            DB::raw('AVG(rating) as average_rating'),
            DB::raw("(
                6371 * acos(
                    cos(radians($lat)) * cos(radians(lat)) *
                    cos(radians(lng) - radians($lng)) +
                    sin(radians($lat)) * sin(radians(lat))
                )
            ) AS distance")
        )
            ->whereNotNull('restaurant_name')
            ->where('have_it', 'Restaurant')
            ->where('post_status', 'approved')
            ->groupBy('restaurant_name', 'location', 'lat', 'lng')
            ->having('distance', '<=', $radiusInKm)
            ->orderBy('distance')
            ->get();

        return response()->json([
            'status' => true,
            'message' => "Nearby Restaurants from: " . $request->location,
            'center' => [
                'lat' => $lat,
                'lng' => $lng,
            ],
            'data' => $restaurants
        ]);
    }
}
