<?php

namespace App\Notifications;

use App\Models\ProductApplication;
use Illuminate\Notifications\Notification;

class NewApplicationReceived extends Notification
{
    public function __construct(
        private ProductApplication $application
    ) {}

    /**
     * @return array<int, string>
     */
    public function via(object $notifiable): array
    {
        return ['database'];
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(object $notifiable): array
    {
        return [
            'title'          => 'New Application',
            'message'        => "{$this->application->creator->name} applied to \"{$this->application->product->name}\".",
            'application_id' => $this->application->id,
            'url'            => '/brand/applications',
        ];
    }
}
