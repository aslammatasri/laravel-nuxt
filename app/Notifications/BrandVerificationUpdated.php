<?php

namespace App\Notifications;

use App\Models\BrandProfile;
use Illuminate\Notifications\Notification;

class BrandVerificationUpdated extends Notification
{
    public function __construct(
        private BrandProfile $profile
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
        $verified = $this->profile->verification_status === 'verified';

        return [
            'title'   => $verified ? 'Brand verified' : 'Verification rejected',
            'message' => $verified
                ? 'Your brand is now verified. A verified badge shows on your products.'
                : 'Your verification was rejected: ' . ($this->profile->verification_note ?: 'no reason given') . '. You can update your details and resubmit.',
            'status'  => $this->profile->verification_status,
            'url'     => '/brand/verification',
        ];
    }
}
