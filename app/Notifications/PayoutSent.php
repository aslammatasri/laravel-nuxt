<?php

namespace App\Notifications;

use App\Models\ProductApplication;
use Illuminate\Notifications\Notification;

class PayoutSent extends Notification
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
        $product = $this->application->product;
        $amount = number_format((float) $product->flat_fee, 2);
        $ref = $this->application->payment_ref;

        return [
            'title'          => 'Payment sent',
            'message'        => "The brand marked \"{$product->name}\" as paid (RM {$amount})."
                . ($ref ? " Ref: {$ref}." : ''),
            'application_id' => $this->application->id,
            'status'         => 'paid',
            'url'            => '/creator/earnings',
        ];
    }
}
