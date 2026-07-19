<?php

namespace App\Notifications;

use App\Enums\NotificationCategory;
use App\Enums\NotificationPriority;
use App\Notifications\Channels\EnterpriseDatabaseChannel;
use App\Notifications\Channels\PushNotificationChannel;
use App\Notifications\Channels\SmsNotificationChannel;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class ErpNotification extends Notification implements ShouldQueue
{
    use Queueable;

    /**
     * @param  array<string, mixed>  $meta
     */
    public function __construct(
        public string $event,
        public string $title,
        public ?string $body = null,
        public NotificationCategory $category = NotificationCategory::Information,
        public NotificationPriority $priority = NotificationPriority::Medium,
        public string $module = 'system',
        public ?string $actionUrl = null,
        public array $meta = [],
    ) {}

    /**
     * @return list<string|class-string>
     */
    public function via(object $notifiable): array
    {
        $channels = [EnterpriseDatabaseChannel::class];

        if (config('notifications.channels.mail')) {
            $channels[] = 'mail';
        }

        if (config('notifications.channels.sms')) {
            $channels[] = SmsNotificationChannel::class;
        }

        if (config('notifications.channels.push')) {
            $channels[] = PushNotificationChannel::class;
        }

        return $channels;
    }

    /**
     * @return array<string, mixed>
     */
    public function toDatabase(object $notifiable): array
    {
        return [
            'event' => $this->event,
            'title' => $this->title,
            'body' => $this->body,
            'category' => $this->category->value,
            'priority' => $this->priority->value,
            'module' => $this->module,
            'action_url' => $this->actionUrl,
            'meta' => $this->meta,
        ];
    }

    /**
     * Architecture hook for future email delivery.
     */
    public function toMail(object $notifiable): MailMessage
    {
        $mail = (new MailMessage)
            ->subject($this->title)
            ->line($this->body ?? $this->title);

        if ($this->actionUrl) {
            $mail->action(__('View'), $this->actionUrl);
        }

        return $mail;
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(object $notifiable): array
    {
        return $this->toDatabase($notifiable);
    }
}
