<?php

namespace App\Notifications;

use App\Models\Comment;
use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class NewCommentNotification extends Notification implements ShouldQueue
{
    use Queueable;

    protected $comment;

    public function __construct(Comment $comment)
    {
        $this->comment = $comment;
    }

    public function via($notifiable)
    {
        return ['database']; // optional: 'mail', 'broadcast', etc.
    }

    public function toDatabase($notifiable)
    {
        return [
            'post_id' => $this->comment->id,
            'user_id' => $this->comment->user_id,
            'user_name' => User::where('id', $this->comment->user_id)->first()->user_name,
            'message' => 'add a comment in your post.',
            'redirect' => 'post_id'
        ];
    }
}
