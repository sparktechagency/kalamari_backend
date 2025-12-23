<?php

namespace App\Http\Controllers;

use App\Services\PushNotificationService;
use Illuminate\Http\Request;

class PushNotificationController extends Controller
{
    public function sendPush(Request $request, PushNotificationService $firebase)
    {
        $request->validate([
            'token' => 'required',
            'title' => 'required',
            'body' => 'required',
        ]);

        $firebase->sendNotification(
            $request->token,
            $request->title,
            $request->body,
            $request->data ?? []
        );

        return response()->json(['message' => 'Push notification sent successfully']);
    }
}
