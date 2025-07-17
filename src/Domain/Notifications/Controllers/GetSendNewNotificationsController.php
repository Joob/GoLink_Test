<?php
namespace Domain\Notifications\Controllers;

use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use App\Http\Models\SentNotification;

class SendNewNotificationsController extends Notification implements ShouldQueue
{
    use Queueable;

    public function via(): array
    {
        return ['mail', 'database', 'broadcast'];
    }

    public function toMail(): MailMessage
    {
        return (new MailMessage)
            ->subject(__t('withdrawal_failed_long'))
            ->greeting(__t('hello'))
            ->line(__t('withdrawal_failed_long_note'))
            ->action(__t('fund_your_account'), url('/user/settings/billing'));
    }

    /**
     * Get the array representation of the notification.
     */
    public function toArray(): array
    {
        $sentNotification = SentNotification::latest()->first(); // Retrieve the latest sent notification

        return [
            'category'    => 'send-new-notifications',
            'title'       => __t('New Update'),
            'description' => $sentNotification ? $sentNotification->message : '',
        ];
    }
}
