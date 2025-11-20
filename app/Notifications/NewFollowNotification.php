<?php

namespace App\Notifications;

use App\Models\Follower;
use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class NewFollowNotification extends Notification implements ShouldQueue
{
    use Queueable;

    protected $follower;

    public function __construct(Follower $follower)
    {
        $this->follower = $follower;
    }

    public function via($notifiable)
    {
        return ['database']; // optional: 'mail', 'broadcast', etc.
    }

    public function toDatabase($notifiable)
    {
        return [
            'post_id' => $this->follower->id,
            'user_id' => $this->follower->follower_id,
            'user_name' => User::where('id', $this->follower->follower_id)->first()->user_name,
            'message' => 'just followed you.',
            'redirect' => 'user_id'
        ];
    }
}
