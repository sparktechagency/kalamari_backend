<?php

use App\Http\Controllers\Admin\MyProfileController;
use App\Http\Controllers\Admin\PostManageController;
use App\Http\Controllers\Admin\SettingsController;
use App\Http\Controllers\Admin\UserAnalyticsController;
use App\Http\Controllers\Admin\UserManageController;
use App\Http\Controllers\Admin\UserReportController;
use App\Http\Controllers\Auth\AuthController;
use App\Http\Controllers\NotificationController;
use App\Http\Controllers\SendSms;
use App\Http\Controllers\User\BookmarkController;
use App\Http\Controllers\User\CommentController;
use App\Http\Controllers\User\HeartController;
use App\Http\Controllers\User\PostController;
use App\Http\Controllers\User\ProfileController;
use Illuminate\Support\Facades\Route;

// Route::get('send-sms', [SendSms::class, 'sendSms']);

// public route for user
Route::post('/register', [AuthController::class, 'register']);
Route::get('/search-user-name', [AuthController::class, 'searchUserName']);
Route::get('/search-user-email', [AuthController::class, 'searchUserEmail']);

Route::post('/verify-otp', [AuthController::class, 'verifyOtp']);
Route::post('/resend-otp', [AuthController::class, 'resendOtp']);
Route::post('/login', [AuthController::class, 'login']);
Route::post('/forgot-password', [AuthController::class, 'forgotPassword']);

// private route for user
Route::middleware('auth:api')->group(function () {
    Route::post('/logout', [AuthController::class, 'logout']);
    Route::post('/change-password', [AuthController::class, 'changePassword']);
    Route::get('/profile', [AuthController::class, 'profile']);
    Route::post('/update-password', [AuthController::class, 'updatePassword']);

    // admin
    Route::middleware('admin')->group(function () {
        Route::get('/user-analytics', [UserAnalyticsController::class, 'userAnalytics']);
        Route::get('/get-settings', [SettingsController::class, 'getSettings']);
        Route::post('/update-settings', [SettingsController::class, 'updateSettings']);


        // manage posts
        Route::get('/get-posts', [PostManageController::class, 'getPosts']);
        Route::delete('/delete-post', [PostManageController::class, 'deletePost']);

        // manage users
        Route::get('/get-users', [UserManageController::class, 'getUsers']);
        Route::delete('/delete-user', [UserManageController::class, 'deleteUser']);

        // profile
        Route::post('/update-admin-profile', [MyProfileController::class, 'updateAdminProfile']);

        // report
        Route::get('/get-user-report', [UserReportController::class, 'userReport']);
    });


    // user
    Route::middleware('user')->group(function () {

        // heart
        Route::get('/toggle-heart', [HeartController::class, 'toggleHeart']);

        // message
        Route::post('/create-comment', [CommentController::class, 'createComment']);
        Route::get('/get-comments', [CommentController::class, 'getComments']);
        Route::post('/replay', [CommentController::class, 'replay']);
        Route::get('/like', [CommentController::class, 'like']);
        Route::get('/get-comments-with-replay-like', [CommentController::class, 'getCommentWithReplayLike']);

        // notification
        Route::get('/get-notifications', [NotificationController::class, 'getNotifications']);
        Route::get('/read', [NotificationController::class, 'read']);
        Route::get('/read-all', [NotificationController::class, 'readAll']);

        // bookmark
        Route::get('/toggle-bookmark', [BookmarkController::class, 'toggleBookmark']);
        Route::get('/get-bookmarks', [BookmarkController::class, 'getBookmarks']);
        Route::get('/view-post', [BookmarkController::class, 'viewPost']);
        Route::get('/search-have_it', [BookmarkController::class, 'getSearchHave_it']);

        // home
        Route::get('/discovery', [PostController::class, 'discovery']);
        Route::post('/discovery-toggle-follow', [PostController::class, 'discoveryToggleFollow']);
        Route::get('/following', [PostController::class, 'following']);
        Route::get('/user-search', [PostController::class, 'userSearch']);
        Route::get('/restaurant-search', [PostController::class, 'restaurantSearch']);


        // post
        Route::post('/create-post', [PostController::class, 'createPost']);
        Route::get('/search-follower', [PostController::class, 'searchFollower']);

        // profile
        Route::post('/update-user-profile', [ProfileController::class, 'updateUserProfile']);
        Route::get('/get-following', [ProfileController::class, 'getFollowing']);
        Route::get('/get-follower', [ProfileController::class, 'getFollower']);
        Route::get('/recent-post', [ProfileController::class, 'recentPost']);
        Route::get('/get-recent-post', [ProfileController::class, 'getRecentPost']);
        Route::get('/get-my-posts', [ProfileController::class, 'getMyPosts']);
        Route::post('/user-block', [ProfileController::class, 'userBlock']);
        Route::post('/user-report', [ProfileController::class, 'userReport']);
    });
});
