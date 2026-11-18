<?php

namespace App\Notifications;

use App\Models\BrandReview;
use Illuminate\Notifications\Notification;

class BrandReviewed extends Notification
{
    public function __construct(
        private BrandReview $review
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
        $product = $this->review->application->product->name;

        return [
            'title'          => 'New review',
            'message'        => "A creator rated \"{$product}\" {$this->review->rating}/5.",
            'application_id' => $this->review->application_id,
            'rating'         => $this->review->rating,
            'url'            => '/brand/reviews',
        ];
    }
}
