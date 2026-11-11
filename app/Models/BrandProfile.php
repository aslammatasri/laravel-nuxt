<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class BrandProfile extends Model
{
    protected $fillable = [
        'user_id',
        'company_name',
        'industry',
        'ssm_no',
        'is_verified',
        'verification_status',
        'verification_note',
        'submitted_at',
        'verified_at',
    ];

    protected function casts(): array
    {
        return [
            'is_verified'  => 'boolean',
            'submitted_at' => 'datetime',
            'verified_at'  => 'datetime',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}