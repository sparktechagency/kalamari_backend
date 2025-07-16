<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Notifications\DatabaseNotification;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;

class NotificationController extends Controller
{

    // all notifications for Auth::id();
    public function getNotifications()
    {
        $notifications = Auth::user()->notifications()->latest()->take(10)->get();

        $formattedNotifications = $notifications->transform(function ($notification) {
            $user = User::find($notification->data['user_id']);

            return [
                'id' => $notification->id,
                'post_id' => $notification->data['post_id'] ?? null,
                'user_id' => $notification->data['user_id'] ?? null,
                'user_name' => $notification->data['user_name'] ?? '',
                'avatar' => $user->avatar ?? null,
                'message' => $notification->data['message'] ?? '',
                'created_at' => $notification->created_at,
                // 'created_at' => optional($notification->created_at)
                //     ->setTimezone('Asia/Dhaka')
                //     ->format('h:i A'),
                'read_at' => $notification->read_at,
                'redirect' => $notification->data['redirect'] ?? ''
            ];
        });

        return response()->json([
            'status' => true,
            'message' => 'Latest 10 notifications',
            'data' => $formattedNotifications
        ]);
    }

    // only read
    public function read(Request $request)
    {
        // validation roles
        $validator = Validator::make($request->all(), [
            'notification_id' => 'required|string|exists:notifications,id',
        ]);

        // check validation
        if ($validator->fails()) {
            return response()->json([
                'status' => false,
                'message' => $validator->errors()
            ], 422);
        }

        $notification = DatabaseNotification::find($request->notification_id);

        $notification->markAsRead();

        return response()->json([
            'status' => true,
            'message' => 'Read'
        ]);
    }

    // read all notification
    public function readAll(Request $request)
    {
        $ids = Auth::user()->unreadNotifications()->pluck('id')->toArray();
        DatabaseNotification::whereIn('id', $ids)->update(['read_at' => now()]);
        return response()->json([
            'status' => true,
            'message' => 'Read all'
        ]);
    }

    //for unread notification count
    public function status()
    {
        return response()->json([
            'status' => true,
            'unread_count' => Auth::user()->unreadNotifications()->count(),
        ]);
    }

    // public function deleteNotification(Request $request)
    // {
    //     $user = Auth::user();

    //     $notification = $user->notifications()->where('id', $request->notification_id)->first();

    //     if (!$notification) {
    //         return response()->json([
    //             'status' => false,
    //             'message' => 'Notification not found',
    //         ], 404);
    //     }

    //     $notification->delete();

    //     return response()->json([
    //         'status' => true,
    //         'message' => 'Notification deleted successfully',
    //     ]);
    // }

}
