<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Notifications\DatabaseNotification;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;

class NotificationController extends Controller
{
    // get all notifications for user
    public function getNotifications()
    {
        // $notifications = Auth::user()->notifications;

        // paginate() apply করা হলো
        $notifications = Auth::user()->notifications()->latest()->paginate(10);

        // $formattedNotifications = $notifications->map(function ($notification) {
        //     return [
        //         'id' => $notification->id,
        //         'user_id' => $notification->data['user_id'],
        //         'user_name' => $notification->data['user_name'] ?? '',
        //         'message' => $notification->data['message'] ?? '',
        //         'created_at' => Carbon::parse($notification->created_at)->timezone('Asia/Dhaka')->format('h:i A'),
        //         'read_at' => $notification->read_at,
        //     ];
        // });

        $formattedNotifications = $notifications->transform(function ($notification) {
            return [
                'id' => $notification->id,
                'user_id' => $notification->data['user_id'] ?? null,
                'user_name' => $notification->data['user_name'] ?? '',
                'message' => $notification->data['message'] ?? '',
                'created_at' => Carbon::parse($notification->created_at)
                    ->timezone('Asia/Dhaka')
                    ->format('h:i A'),
                'read_at' => $notification->read_at,
            ];
        });

        $notifications->setCollection($formattedNotifications);

        return response()->json([
            'status' => true,
            'message' => 'Your all notifications',
            'data' => $notifications
        ]);
    }

    // only read
    public function read(Request $request)
    {
         // validation roles
        $validator = Validator::make($request->all(), [
            'notification_id'             => 'required|string|exists:notifications,id',
        ]);

        // check validation
        if ($validator->fails()) {
            return response()->json([
                'status' => false,
                'message'   => $validator->errors()
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
}
