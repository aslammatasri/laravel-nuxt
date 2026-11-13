<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CollaborationTask extends Model
{
    public const TYPES = ['video', 'live', 'photo', 'story', 'post', 'sample', 'other'];

    protected $fillable = ['application_id', 'title', 'type', 'due_date', 'link', 'completed_at', 'position'];

    protected function casts(): array
    {
        return [
            'due_date'     => 'date',
            'completed_at' => 'datetime',
        ];
    }

    public function application(): BelongsTo
    {
        return $this->belongsTo(ProductApplication::class, 'application_id');
    }
}
