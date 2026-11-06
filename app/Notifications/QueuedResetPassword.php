<?php

namespace App\Notifications;

use Illuminate\Auth\Notifications\ResetPassword;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;

class QueuedResetPassword extends ResetPassword implements ShouldQueue
{
    use Queueable;

    public function toMail($notifiable): MailMessage
    {
        $resetUrl = $this->resetUrl($notifiable);

        return (new MailMessage)
            ->from(config('mail.from.address'), 'Sistem RAPI')
            ->subject('🔐 Atur Ulang Kata Sandi RAPI')
            ->view('emails.password-reset', [
                'name' => $notifiable->fullname ?: $notifiable->email,
                'resetUrl' => $resetUrl,
                'expirationMinutes' => config('auth.passwords.'.config('auth.defaults.passwords').'.expire'),
            ]);
    }
}
