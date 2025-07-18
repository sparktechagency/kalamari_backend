<?php

namespace App\Notifications;

use App\Models\User;
use App\Models\UserReport;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class NewReportCreationNotification extends Notification implements ShouldQueue
{
    use Queueable;

    protected $user_report;

    public function __construct(UserReport $user_report)
    {
        $this->user_report = $user_report;
    }

    public function via($notifiable)
    {
        return ['database']; // optional: 'mail', 'broadcast', etc.
    }

    public function toDatabase($notifiable)
    {
        return [
            'user_id' => $this->user_report->reporter_id,
            'report_id' => $this->user_report->id,
            'user_name' => User::where('id', $this->user_report->reporter_id)->first()->name,
            'message' => 'New report created.',
        ];
    }
}
