<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class CreatorProfile extends Model
{
    protected $fillable = [
        'user_id',
        'niches',
        'tiktok_handle',
        'followers',
        'avg_views',
    ];

    protected function casts(): array
    {
        return [
            'niches'        => 'array',
            'tiktok_handle' => 'array',
            'followers'     => 'array',
            'avg_views'     => 'array',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function socialAccounts(): HasMany
    {
        return $this->hasMany(CreatorSocialAccount::class);
    }
}