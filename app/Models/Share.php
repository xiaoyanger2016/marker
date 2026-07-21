<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Illuminate\Support\Str;

class Share extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'resource_type',
        'resource_id',
        'token',
        'password',
        'expires_at',
        'view_count',
        'max_views',
        'permissions',
    ];

    protected $casts = [
        'expires_at' => 'datetime',
        'view_count' => 'integer',
        'max_views' => 'integer',
        'permissions' => 'array',
    ];

    protected static function booted(): void
    {
        static::creating(function (self $share) {
            if (empty($share->token)) {
                $share->token = Str::random(48);
            }
        });
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function resource(): MorphTo
    {
        return $this->morphTo();
    }

    public function isExpired(): bool
    {
        if ($this->expires_at && $this->expires_at->isPast()) {
            return true;
        }
        if ($this->max_views && $this->view_count >= $this->max_views) {
            return true;
        }
        return false;
    }

    public function recordView(): void
    {
        $this->increment('view_count');
    }
}
