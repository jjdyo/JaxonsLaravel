<?php

namespace App\Notifications;

use App\Models\PendingPasswordChange;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;
use Illuminate\Support\Facades\Lang;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\Carbon; // for type hints only

class VerifyPasswordChangeNotification extends Notification
{
    use Queueable;

    public function __construct(public PendingPasswordChange $pending)
    {
    }

    /**
     * @param object $notifiable
     * @return array<int, string>
     */
    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $url = URL::temporarySignedRoute(
            'password-change.verify',
            now()->addMinutes(20),
            ['id' => $this->pending->id]
        );

        /** @var string|array<string> $subject */
        $subject = Lang::get('Confirm Your Password Change');
        $subjectStr = is_array($subject) ? implode(' ', $subject) : $subject;

        /** @var string|array<string> $line */
        $line = Lang::get('You requested to change your password. For security, please confirm this change by clicking the button below.');
        $lineStr = is_array($line) ? implode(' ', $line) : $line;

        /** @var string|array<string> $action */
        $action = Lang::get('Confirm Password Change');
        $actionStr = is_array($action) ? implode(' ', $action) : $action;

        return (new MailMessage)
            ->subject($subjectStr)
            ->line($lineStr)
            ->action($actionStr, $url)
            ->view('emails.verify-password-change', ['url' => $url]);
    }
}
