<?php

namespace App\Notifications;

use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class NewUserCreationNotification extends Notification implements ShouldQueue
{
    use Queueable;

    protected $user;

    public function __construct(User $user)
    {
        $this->user = $user;
    }

    public function via($notifiable)
    {
        return ['database']; // optional: 'mail', 'broadcast', etc.
    }

    public function toDatabase($notifiable)
    {
        return [
            'user_id' => $this->user->id,
            'user_name' => User::where('id', $this->user->id)->first()->name,
            'message' => 'New user created.',
        ];
    }
}
