<?php

namespace Bkwld\Decoy\Notifications;

// Deps
use Decoy;
use Illuminate\Auth\Notifications\ResetPassword as LaravelResetPassword;
use Illuminate\Notifications\Messages\MailMessage;

/**
 * Subclass the Laravel reset password so we can send admin to the /admin
 */
class ResetPassword extends LaravelResetPassword
{

    /**
     * Build the mail representation of the notification.
     *
     * @param  mixed  $notifiable
     * @return \Illuminate\Notifications\Messages\MailMessage
     */
    public function toMail($notifiable)
    {
        // Make the URL
        $dir = config('decoy.core.dir');
        $url = url($dir.'/password/reset', $this->token);

        // Send the message
        return (new MailMessage)
            ->subject('Recover access to '.Decoy::site())
            ->line('You are receiving this email because we received a password reset request for your account.')
            ->action('Reset Password', $url)
            ->line('If you did not request a password reset, no further action is required.');
    }

}
