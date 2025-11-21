<?php

use App\Http\Controllers\Admin\MyProfileController;
use App\Http\Controllers\Admin\PostManageController;
use App\Http\Controllers\Admin\SettingsController;
use App\Http\Controllers\Admin\UserAnalyticsController;
use App\Http\Controllers\Admin\UserManageController;
use App\Http\Controllers\Admin\UserReportController;
use App\Http\Controllers\Auth\AuthController;
use App\Http\Controllers\NotificationController;
use App\Http\Controllers\PushNotificationController;
use App\Http\Controllers\User\BookmarkController;
use App\Http\Controllers\User\CommentController;
use App\Http\Controllers\User\HeartController;
use App\Http\Controllers\User\PostController;
use App\Http\Controllers\User\ProfileController;
use Illuminate\Support\Facades\Route;

// public route for user
Route::post('/register', [AuthController::class, 'register']);
Route::get('/search-user-name', [AuthController::class, 'searchUserName']);
Route::get('/search-user-email', [AuthController::class, 'searchUserEmail']);
Route::post('/verify-otp', [AuthController::class, 'verifyOtp']);
Route::post('/resend-otp', [AuthController::class, 'resendOtp']);
Route::post('/login', [AuthController::class, 'login']);
Route::post('/forgot-password', [AuthController::class, 'forgotPassword']);
Route::get('/check-token', [AuthController::class, 'checkToken']);

Route::post('/push/send', [PushNotificationController::class, 'sendPush']);

Route::middleware('auth:api')->group(function () {
    // private route for user
    Route::post('/logout', [AuthController::class, 'logout']);
    Route::post('/change-password', [AuthController::class, 'changePassword']);
    Route::get('/profile', [AuthController::class, 'profile']);
    Route::post('/update-password', [AuthController::class, 'updatePassword']);
    Route::post('/store-contact', [AuthController::class, 'storeContact']);
    Route::get('/search-contact', [AuthController::class, 'searchContact']);
    Route::get('/syn-contacts', [AuthController::class, 'synContacts']);
    Route::delete('/delete-account', [AuthController::class, 'deleteAccount']);
    Route::patch('/device-token', [AuthController::class, 'deviceToken']);

    // notification
    Route::get('/get-notifications', [NotificationController::class, 'getNotifications']);
    Route::post('/read', [NotificationController::class, 'read']);
    Route::post('/read-all', [NotificationController::class, 'readAll']);
    Route::get('/notification-status', [NotificationController::class, 'status']);

    // admin
    Route::middleware('admin')->prefix('admin')->group(function () {
        Route::get('/user-analytics', [UserAnalyticsController::class, 'userAnalytics']);
        Route::get('/get-settings', [SettingsController::class, 'getSettings']);
        Route::post('/update-settings', [SettingsController::class, 'updateSettings']);

        // manage posts
        Route::get('/get-posts', [PostManageController::class, 'getPosts']);
        Route::get('/get-post', [PostManageController::class, 'getPost']);
        Route::delete('/delete-post', [PostManageController::class, 'deletePost']);

        // manage users
        Route::get('/get-users', [UserManageController::class, 'getUsers']);
        Route::get('/get-user', [UserManageController::class, 'getUser']);
        Route::delete('/delete-user', [UserManageController::class, 'deleteUser']);

        // profile
        Route::post('/update-admin-profile', [MyProfileController::class, 'updateAdminProfile']);
        Route::get('/get-admin-profile', [MyProfileController::class, 'getAdminProfile']);

        // report
        Route::get('/get-reports', [UserReportController::class, 'getReports']);
        Route::get('/get-report', [UserReportController::class, 'getReport']);
    });

    // user
    Route::middleware('user')->group(function () {

        // heart
        Route::post('/toggle-heart', [HeartController::class, 'toggleHeart']);

        // message
        Route::post('/create-comment', [CommentController::class, 'createComment']);
        Route::delete('/delete-comment', [CommentController::class, 'deleteComment']);
        Route::get('/get-comments', [CommentController::class, 'getComments']);
        Route::post('/replay', [CommentController::class, 'replay']);
        Route::post('/like', [CommentController::class, 'like']);
        Route::get('/get-comments-with-replay-like', [CommentController::class, 'getCommentWithReplayLike']);

        // bookmark
        Route::post('/toggle-bookmark', [BookmarkController::class, 'toggleBookmark']);
        Route::get('/get-bookmarks', [BookmarkController::class, 'getBookmarks']);
        Route::get('/view-post', [BookmarkController::class, 'viewPost']);
        Route::get('/search-have_it', [BookmarkController::class, 'getSearchHave_it']);
        Route::delete('/delete-have_it', [BookmarkController::class, 'deleteHave_it']);

        // home
        Route::get('/discovery', [PostController::class, 'discovery']);
        Route::post('/discovery-toggle-follow', [PostController::class, 'discoveryToggleFollow']);
        Route::get('/following', [PostController::class, 'following']);
        Route::get('/user-search', [PostController::class, 'userSearch']);
        Route::get('/restaurant-search', [PostController::class, 'restaurantSearch']);
        Route::get('/view-restaurant/{id?}', [PostController::class, 'viewRestaurant']);

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
        Route::delete('/delete-recent', [ProfileController::class, 'deleteRecent']);

    });
});
