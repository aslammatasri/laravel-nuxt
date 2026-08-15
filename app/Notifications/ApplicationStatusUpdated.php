<?php

namespace App\Notifications;

use App\Models\ProductApplication;
use Illuminate\Notifications\Notification;

class ApplicationStatusUpdated extends Notification
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
        $status = $this->application->status->value;

        return [
            'title'          => 'Application ' . ucfirst($status),
            'message'        => "Your application for \"{$this->application->product->name}\" was {$status}.",
            'application_id' => $this->application->id,
            'status'         => $status,
            'url'            => '/creator/applications',
        ];
    }
}
