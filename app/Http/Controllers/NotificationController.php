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
    public function getNotifications1(Request $request)
    {
        if (!Auth::check()) {
            return response()->json([
                'status' => false,
                'message' => 'Unauthorized',
            ], 401);
        }

        $user = Auth::user();

        if ($user->role === 'ADMIN') {
            // Admin – paginated notifications
            $notifications = $user->notifications()->latest()->paginate($request->per_page ?? 10);
            $notifications->getCollection()->transform(function ($notification) {
                $user = User::find($notification->data['user_id']);

                return [
                    'id' => $notification->id,
                    'post_id' => $notification->data['post_id'] ?? null,
                    'user_id' => $notification->data['user_id'] ?? null,
                    'report_id' => $notification->data['report_id'] ?? null,
                    'user_name' => $notification->data['user_name'] ?? '',
                    'avatar' => $user->avatar ?? null,
                    'message' => $notification->data['message'] ?? '',
                    'created_at' => $notification->created_at,
                    'read_at' => $notification->read_at,
                    'redirect' => $notification->data['redirect'] ?? ''
                ];
            });
        } else {
            // Regular user – only latest 10
            $notifications = $user->notifications()->latest()->paginate($request->per_page ?? 10);

            $notifications = $notifications->transform(function ($notification) {
                $user = User::find($notification->data['user_id']);

                return [
                    'id' => $notification->id,
                    'post_id' => $notification->data['post_id'] ?? null,
                    'user_id' => $notification->data['user_id'] ?? null,
                    'report_id' => $notification->data['report_id'] ?? null,
                    'user_name' => $notification->data['user_name'] ?? '',
                    'avatar' => $user->avatar ?? null,
                    'message' => $notification->data['message'] ?? '',
                    'created_at' => $notification->created_at,
                    'read_at' => $notification->read_at,
                    'redirect' => $notification->data['redirect'] ?? ''
                ];
            });
        }

        return response()->json([
            'status' => true,
            'message' => 'Latest notifications',
            'data' => $notifications
        ]);
    }
    public function getNotifications(Request $request)
    {
        if (!Auth::check()) {
            return response()->json([
                'status' => false,
                'message' => 'Unauthorized',
            ], 401);
        }

        $user = Auth::user();
        $perPage = $request->per_page ?? 10;

        // ✅ Notification query
        $notifications = $user->notifications()->latest()->paginate($perPage);

        // ✅ Transform each notification inside the paginated collection
        $notifications->getCollection()->transform(function ($notification) {
            $sender = User::find($notification->data['user_id']);

            return [
                'id' => $notification->id,
                'post_id' => $notification->data['post_id'] ?? null,
                'user_id' => $notification->data['user_id'] ?? null,
                'report_id' => $notification->data['report_id'] ?? null,
                'user_name' => $notification->data['user_name'] ?? '',
                'avatar' => $sender->avatar ?? null,
                'message' => $notification->data['message'] ?? '',
                'created_at' => $notification->created_at,
                'read_at' => $notification->read_at,
                'redirect' => $notification->data['redirect'] ?? ''
            ];
        });

        // ✅ Return default pagination response with meta
        return response()->json([
            'status' => true,
            'message' => 'Latest notifications',
            'data' => $notifications
        ]);
    }
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
            'message' => 'Notification readed'
        ]);
    }
    public function readAll(Request $request)
    {
        $ids = Auth::user()->unreadNotifications()->pluck('id')->toArray();
        DatabaseNotification::whereIn('id', $ids)->update(['read_at' => now()]);
        return response()->json([
            'status' => true,
            'message' => 'All Notifications are readed'
        ]);
    }
    public function status()
    {
        return response()->json([
            'status' => true,
            'message' => 'How much unreaded notifications',
            'unread_count' => Auth::user()->unreadNotifications()->count(),
        ]);
    }
}
