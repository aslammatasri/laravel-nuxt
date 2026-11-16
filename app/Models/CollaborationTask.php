<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CollaborationTask extends Model
{
    public const TYPES = ['video', 'live', 'photo', 'story', 'post', 'sample', 'other'];
    public const REVIEWS = ['pending', 'approved', 'changes_requested'];

    protected $fillable = ['application_id', 'title', 'type', 'due_date', 'link', 'completed_at', 'position', 'review_status', 'review_note', 'reviewed_at'];

    protected function casts(): array
    {
        return [
            'due_date'     => 'date',
            'completed_at' => 'datetime',
            'reviewed_at'  => 'datetime',
        ];
    }

    // The sample task is sent by the brand; everything else is content the brand reviews
    public function needsReview(): bool
    {
        return $this->type !== 'sample';
    }

    public function application(): BelongsTo
    {
        return $this->belongsTo(ProductApplication::class, 'application_id');
    }
}
