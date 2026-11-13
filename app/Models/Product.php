<?php

namespace App\Models;

use App\Enums\ApplicationStatus;
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
        'platforms',
        'content_types',
        'posts_required',
        'brief',
        'hashtags',
        'product_url',
        'min_subscribers',
        'free_sample',
        'flat_fee',
        'application_deadline',
    ];

    protected function casts(): array
    {
        return [
            'price' => 'decimal:2',
            'commission_rate' => 'decimal:2',
            'campaign_start' => 'date',
            'campaign_end' => 'date',
            'application_deadline' => 'date',
            'platforms' => 'array',
            'content_types' => 'array',
            'target_niches' => 'array',
            'free_sample' => 'boolean',
            'flat_fee' => 'decimal:2',
        ];
    }

    public function getImagesAttribute($value): array
    {
        $images = $value ? json_decode($value, true) : [];
        if (empty($images) && $this->image) {
            return [$this->image];
        }
        return $images;
    }

    public function setImagesAttribute(array $value): void
    {
        $this->attributes['images'] = json_encode($value);
    }

    // Remaining affiliate slots (approved creators count); null = unlimited
    public function getSpotsLeftAttribute(): ?int
    {
        if (! $this->max_affiliates) {
            return null;
        }

        $approved = $this->applications()
            ->where('status', ApplicationStatus::APPROVED->value)
            ->count();

        return max(0, $this->max_affiliates - $approved);
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