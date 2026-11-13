<?php

namespace App\Models;

use App\Enums\ApplicationStatus;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ProductApplication extends Model
{
    protected $table = 'products_application';

    protected $casts = [
        'status'           => ApplicationStatus::class,
        'checklist_seeded' => 'boolean',
    ];

    protected $fillable = [
        'product_id',
        'creator_id',
        'status',
        'message',
        'pitch_message',
        'checklist_seeded',
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
}
