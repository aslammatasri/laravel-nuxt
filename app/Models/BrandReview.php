<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class BrandReview extends Model
{
    protected $fillable = ['application_id', 'creator_id', 'brand_id', 'rating', 'body'];

    public function application(): BelongsTo
    {
        return $this->belongsTo(ProductApplication::class, 'application_id');
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'creator_id');
    }

    public function brand(): BelongsTo
    {
        return $this->belongsTo(User::class, 'brand_id');
    }
}
