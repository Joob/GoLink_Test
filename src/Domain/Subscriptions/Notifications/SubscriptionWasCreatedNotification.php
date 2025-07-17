<?php
namespace Domain\Subscriptions\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use VueFileManager\Subscription\Domain\Subscriptions\Models\Subscription;

class SubscriptionWasCreatedNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(
        public Subscription $subscription,
    ) {
    }

    public function via(mixed $notifiable): array
    {
        //return ['mail', 'database', 'broadcast'];
        return ['mail'];
    }

    public function toMail(mixed $notifiable): MailMessage
    {
        \App::setLocale(get_settings('language'));
        
        return (new MailMessage)
            ->subject(__t('subscription_created_short', ['plan' => $this->subscription->plan->name]))
            ->view('mails.notifications.SubscriptionWasCreatedNotificationEmail', [
                'url' => url('/user/settings/billing')
            ]);
            //->greeting(__t('hello'))
            //->line(__t('subscription_created_long_note', ['plan' => $this->subscription->plan->name]))
            //->action(__t('go_to_subscription'), url('/user/settings/billing'));
    }

    public function toArray(mixed $notifiable): array
    {
        return [
            'category'    => 'subscription-created',
            'title'       => __t('subscription_created_short'),
            'description' => __t('subscription_created_short_note', ['plan' => $this->subscription->plan->name]),
            'action'      => [
                'type'   => 'route',
                'params' => [
                    'route'  => __t('billing'),
                    'button' => __t('show_billing'),
                ],
            ],
        ];
    }
}
