<?php

namespace App\Models;

use App\Enums\ApplicationStatus;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ProductApplication extends Model
{
    protected $table = 'products_application';

    protected $casts = [
        'status'           => ApplicationStatus::class,
        'checklist_seeded' => 'boolean',
        'paid_at'          => 'datetime',
    ];

    protected $fillable = [
        'product_id',
        'creator_id',
        'status',
        'message',
        'pitch_message',
        'checklist_seeded',
        'tracking_code',
        'paid_at',
        'payment_ref',
    ];

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'creator_id');
    }

    public function tasks(): HasMany
    {
        return $this->hasMany(CollaborationTask::class, 'application_id')->orderBy('position')->orderBy('id');
    }

    public function clicks(): HasMany
    {
        return $this->hasMany(LinkClick::class, 'application_id');
    }

    public function sales(): HasMany
    {
        return $this->hasMany(Sale::class, 'application_id');
    }

    // Unique short code for the creator's tracked link; created on first use
    public function ensureTrackingCode(): string
    {
        if (! $this->tracking_code) {
            do {
                $code = Str::lower(Str::random(8));
            } while (self::where('tracking_code', $code)->exists());

            $this->update(['tracking_code' => $code]);
        }

        return $this->tracking_code;
    }

    public function trackingUrl(): string
    {
        return url('/r/' . $this->ensureTrackingCode());
    }

    // Totals for sales that still count (not reversed)
    public function salesSummary(): array
    {
        $live = $this->sales->where('status', '!=', Sale::REVERSED);

        return [
            'count'   => $live->count(),
            'revenue' => round((float) $live->sum('amount'), 2),
            'owed'    => round((float) $live->where('status', Sale::APPROVED)->sum('commission'), 2),
            'paid'    => round((float) $live->where('status', Sale::PAID)->sum('commission'), 2),
        ];
    }

    // Flat fee status: none (no fee or no tasks), pending (deliverables not all approved), due, paid
    public function feeStatus(): string
    {
        if ($this->paid_at) {
            return 'paid';
        }
        if ((float) $this->product->flat_fee <= 0) {
            return 'none';
        }

        $deliverables = $this->tasks->filter(fn ($t) => $t->needsReview());

        return $deliverables->isNotEmpty() && $deliverables->every(fn ($t) => $t->review_status === 'approved')
            ? 'due'
            : 'pending';
    }

    public function feeSummary(): array
    {
        $deliverables = $this->tasks->filter(fn ($t) => $t->needsReview());

        return [
            'amount'    => (float) $this->product->flat_fee,
            'status'    => $this->feeStatus(),
            'paid_at'   => $this->paid_at,
            'reference' => $this->payment_ref,
            'approved'  => $deliverables->where('review_status', 'approved')->count(),
            'total'     => $deliverables->count(),
        ];
    }
}
