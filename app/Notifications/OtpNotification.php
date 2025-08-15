<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class OtpNotification extends Notification
{
    use Queueable;

    public string $otp;

    public function __construct(string $otp)
    {
        $this->otp = $otp;
    }

    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject('Your OTP Code')
            ->greeting('Hello ' . ($notifiable->name ?? 'User') . ',')
            ->line('Use the following OTP to complete your registration:')
            ->line('**' . $this->otp . '**')
            ->line('This OTP will expire in 10 minutes.')
            ->line('If you did not request this, you can ignore this email.');
    }
}
