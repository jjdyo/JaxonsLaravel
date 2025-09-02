<?php

namespace App\Notifications;

use Illuminate\Auth\Notifications\VerifyEmail;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Support\Facades\Lang;

class VerifyEmailNotification extends VerifyEmail
{
    /**
     * Build the mail representation of the notification.
     *
     * @param mixed $notifiable
     * @return \Illuminate\Notifications\Messages\MailMessage
     */
    public function toMail($notifiable)
    {
        $verificationUrl = $this->verificationUrl($notifiable);

        if (static::$toMailCallback) {
            return call_user_func(static::$toMailCallback, $notifiable, $verificationUrl);
        }

        return $this->buildMailMessage($verificationUrl);
    }

    /**
     * Get the verify email notification mail message for the given URL.
     *
     * @param string $url
     * @return \Illuminate\Notifications\Messages\MailMessage
     */
    protected function buildMailMessage($url)
    {
        // Get localized strings and ensure they are strings
        /** @var string|array<string> $subject */
        $subject = Lang::get('Verify Email Address');
        $subjectStr = $this->ensureString($subject, 'Verify Email Address');

        /** @var string|array<string> $line */
        $line = Lang::get('Please click the button below to verify your email address.');
        $lineStr = $this->ensureString($line, 'Please click the button below to verify your email address.');

        /** @var string|array<string> $action */
        $action = Lang::get('Verify Email Address');
        $actionStr = $this->ensureString($action, 'Verify Email Address');

        return (new MailMessage)
            ->subject($subjectStr)
            ->line($lineStr)
            ->action($actionStr, $url)
            ->view('emails.verify-email', ['url' => $url]); // Use custom blade template
    }

    /**
     * Ensure a value is a string.
     *
     * @param string|array<string> $value
     * @param string $default
     * @return string
     */
    private function ensureString($value, string $default): string
    {
        if (is_string($value)) {
            return $value;
        }

        // For arrays, join the elements
        return implode(' ', $value);
    }
}
