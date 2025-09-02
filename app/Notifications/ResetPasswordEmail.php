<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;
use Illuminate\Support\Facades\Lang;

class ResetPasswordEmail extends Notification
{
    use Queueable;

    /**
     * The password reset token.
     *
     * @var string
     */
    public string $token;

    /**
     * The callback that should be used to create the reset password URL.
     *
     * @var callable|null
     */
    public static $createUrlCallback;

    /**
     * The callback that should be used to build the mail message.
     *
     * @var callable|null
     */
    public static $toMailCallback;

    /**
     * Create a new notification instance.
     *
     * @param string $token The password reset token
     */
    public function __construct(string $token)
    {
        $this->token = $token;
    }

    /**
     * Get the notification's delivery channels.
     *
     * @return array<int, string>
     */
    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    /**
     * Get the mail representation of the notification.
     *
     * @param object $notifiable The notifiable entity
     * @return \Illuminate\Notifications\Messages\MailMessage
     */
    public function toMail(object $notifiable): MailMessage
    {
        if (static::$toMailCallback) {
            /** @var MailMessage */
            return call_user_func(static::$toMailCallback, $notifiable, $this->token);
        }

        return $this->buildMailMessage($this->resetUrl($notifiable));
    }

    /**
     * Get the reset password URL for the given notifiable.
     *
     * @param object $notifiable The notifiable entity
     * @return string
     */
    protected function resetUrl(object $notifiable): string
    {
        if (static::$createUrlCallback) {
            /** @var string */
            return call_user_func(static::$createUrlCallback, $notifiable, $this->token);
        }

        // Ensure $notifiable has the required method
        if (!method_exists($notifiable, 'getEmailForPasswordReset')) {
            throw new \InvalidArgumentException('Notifiable must implement getEmailForPasswordReset() method');
        }

        // Get the email for password reset
        $email = $notifiable->getEmailForPasswordReset();

        // Ensure email is a string
        if (!is_string($email)) {
            $email = '';
        }

        return url(route('password.reset', [
            'token' => $this->token,
            'email' => $email,
        ], false));
    }

    /**
     * Build the mail message.
     *
     * @param string $url The reset password URL
     * @return \Illuminate\Notifications\Messages\MailMessage
     */
    protected function buildMailMessage(string $url): MailMessage
    {
        // Get localized strings and ensure they are strings
        /** @var string|array<string> $subject */
        $subject = Lang::get('Reset Password Notification');
        $subjectStr = $this->ensureString($subject, 'Reset Password Notification');

        /** @var string|array<string> $line */
        $line = Lang::get('You are receiving this email because we received a password reset request for your account.');
        $lineStr = $this->ensureString($line, 'You are receiving this email because we received a password reset request for your account.');

        /** @var string|array<string> $action */
        $action = Lang::get('Reset Password');
        $actionStr = $this->ensureString($action, 'Reset Password');

        return (new MailMessage)
            ->subject($subjectStr)
            ->line($lineStr)
            ->action($actionStr, $url)
            ->view('emails.password-reset', ['url' => $url]); // Use custom blade template
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

    /**
     * Set a callback that should be used when creating the reset password button URL.
     */
    public static function createUrlUsing(?callable $callback): void
    {
        static::$createUrlCallback = $callback;
    }

    /**
     * Set a callback that should be used when building the notification mail message.
     */
    public static function toMailUsing(?callable $callback): void
    {
        static::$toMailCallback = $callback;
    }
}
