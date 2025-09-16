<?php

namespace App\Notifications;

use Illuminate\Auth\Notifications\ResetPassword;
use Illuminate\Contracts\Auth\CanResetPassword;
use Illuminate\Notifications\Messages\MailMessage;

class ResetPasswordEmail extends ResetPassword
{
    /**
     * Build the mail representation of the notification.
     *
     * @param CanResetPassword $notifiable
     */
    public function toMail($notifiable): MailMessage
    {
        /** @var CanResetPassword $notifiable */
        $url = url(route('password.reset', [
            'password_callback' => $this->password_callback,
            'email' => $notifiable->getEmailForPasswordReset(),
        ], false));

        $configuredPasswords = config('auth.defaults.passwords', 'users');
        $passwords = is_string($configuredPasswords) ? $configuredPasswords : 'users';

        $expireConfig = config("auth.passwords.$passwords.expire", 60);
        $expire = is_int($expireConfig)
            ? $expireConfig
            : (is_string($expireConfig) && ctype_digit($expireConfig) ? (int) $expireConfig : 60);

        return (new MailMessage)
            ->subject('Reset Password Notification')
            ->line('You are receiving this email because we received a password reset request for your account.')
            ->action('Reset Password', $url)
            ->line(__('This password reset link will expire in :count minutes.', ['count' => $expire]))
            ->line('If you did not request a password reset, no further action is required.')
            ->view('emails.password-reset', ['url' => $url]);
    }
}
