<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Product extends Model
{
    protected $fillable = [
        'brand_id',
        'name',
        'description',
        'image',
        'images',
        'price',
        'category',
        'commission_rate',
        'commission_type',
        'status',
        'campaign_start',
        'campaign_end',
        'max_affiliates',
        'target_niches',
    ];

    protected function casts(): array
    {
        return [
            'price' => 'decimal:2',
            'commission_rate' => 'decimal:2',
            'campaign_start' => 'date',
            'campaign_end' => 'date',
        ];
    }

    public function getImagesAttribute($value): array
    {
        $images = json_decode($value, true) ?? [];
        if (empty($images) && $this->image) {
            return [$this->image];
        }
        return $images;
    }

    public function setImagesAttribute(array $value): void
    {
        $this->attributes['images'] = json_encode($value);
    }

    public function brand(): BelongsTo
    {
        return $this->belongsTo(User::class, 'brand_id');
    }

    public function applications(): HasMany
    {
        return $this->hasMany(ProductApplication::class);
    }

    public function variations(): HasMany
    {
        return $this->hasMany(ProductVariation::class);
    }
}