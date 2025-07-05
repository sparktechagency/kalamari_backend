<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Twilio\Rest\Client;

class SendSms extends Controller
{
    public function sendSms(Request $request)
    {
        $sid = config('services.twilio.sid');
        $token = config('services.twilio.token');
        $from = config('services.twilio.from');

        $to = '+15076097853'; // উদাহরণ: +8801XXXXXXXXX
        $message = 'Hi, Mir Shifat Mahmud';

        try {
            $client = new Client($sid, $token);
            $client->messages->create($to, [
                'from' => $from,
                'body' => $message,
            ]);

            return response()->json([
                'status' => true,
                'message' => 'SMS sent successfully!',
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'status' => false,
                'message' => 'Failed to send SMS.',
                'error' => $e->getMessage(),
            ], 500);
        }
    }
}
