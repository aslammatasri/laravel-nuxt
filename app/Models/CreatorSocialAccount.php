<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CreatorSocialAccount extends Model
{
    protected $fillable = [
        'creator_profile_id',
        'platform',
        'handle',
        'channel_id',
        'title',
        'thumbnail_url',
        'subscriber_count',
        'view_count',
        'video_count',
        'avg_recent_views',
        'avg_recent_likes',
        'avg_recent_comments',
        'engagement_rate',
        'last_synced_at',
        'sync_error',
    ];

    protected function casts(): array
    {
        return [
            'subscriber_count'     => 'integer',
            'view_count'           => 'integer',
            'video_count'          => 'integer',
            'avg_recent_views'     => 'integer',
            'avg_recent_likes'     => 'integer',
            'avg_recent_comments'  => 'integer',
            'engagement_rate'      => 'float',
            'last_synced_at'       => 'datetime',
        ];
    }

    public function creatorProfile(): BelongsTo
    {
        return $this->belongsTo(CreatorProfile::class);
    }
}
