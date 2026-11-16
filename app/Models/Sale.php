<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Sale extends Model
{
    public const APPROVED = 'approved';
    public const PAID = 'paid';
    public const REVERSED = 'reversed';

    protected $fillable = ['application_id', 'order_ref', 'amount', 'commission', 'status', 'sold_at', 'paid_at'];

    protected function casts(): array
    {
        return [
            'amount'     => 'decimal:2',
            'commission' => 'decimal:2',
            'sold_at'    => 'date',
            'paid_at'    => 'datetime',
        ];
    }

    public function application(): BelongsTo
    {
        return $this->belongsTo(ProductApplication::class, 'application_id');
    }
}
