<?php

namespace App\Users\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class AuthOtpCode extends Notification
{
    use Queueable;

    /**
     * Get the notification's delivery channels.
     *
     * @param  mixed  $notifiable
     * @return array
     */
    public function via($notifiable)
    {
        return ['mail'];
    }

    /**
     * Get the mail representation of the notification.
     *
     * @param  mixed  $notifiable
     * @return \Illuminate\Notifications\Messages\MailMessage
     */
    public function toMail($notifiable)
    {
        $app_name = get_settings('app_title') ?? 'GoLink.co - Decentralized Storage';

        return (new MailMessage)
            ->subject(__t('send_otp_code_subject') . $app_name)
            ->view('mails.notifications.otp-code-mail', [
                'name' => $notifiable->name,
                'code' => $notifiable->otp_code,
                'expiration' => $notifiable->otp_expiration,
            ]);
    }

    /**
     * Get the array representation of the notification.
     *
     * @param  mixed  $notifiable
     * @return array
     */
    public function toArray($notifiable)
    {
        return [
            //
        ];
    }
}
