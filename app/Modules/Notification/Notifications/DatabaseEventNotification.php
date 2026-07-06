<?php

namespace App\Modules\Notification\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;

class DatabaseEventNotification extends Notification
{
    use Queueable;

    public function __construct(
        private string $eventType,
        private string $title,
        private string $message,
        private array $context = []
    ) {
    }

    public function via(object $notifiable): array
    {
        return ['database'];
    }

    public function toArray(object $notifiable): array
    {
        return [
            'event_type' => $this->eventType,
            'title' => $this->title,
            'message' => $this->message,
            'context' => $this->context,
        ];
    }
}
