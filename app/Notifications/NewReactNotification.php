<?php

namespace App\Notifications;

use App\Models\Heart;
use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class NewReactNotification extends Notification implements ShouldQueue
{
    use Queueable;

    protected $heart;

    public function __construct(Heart $heart)
    {
        $this->heart = $heart;
    }

    public function via($notifiable)
    {
        return ['database']; // optional: 'mail', 'broadcast', etc.
    }

    public function toDatabase($notifiable)
    {
        return [
            'post_id' => $this->heart->id,
            'user_id' => $this->heart->user_id,
            'user_name' => User::where('id', $this->heart->user_id)->first()->user_name,
            'message' => 'reacted on your post.',
            'redirect' => 'post_id'
        ];
    }
}
