<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Str;

class Collection extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'cover_media_id',
        'name',
        'slug',
        'description',
        'is_public',
        'share_token',
        'share_password',
        'share_expires_at',
        'share_view_count',
        'sort',
    ];

    protected $casts = [
        'is_public' => 'boolean',
        'share_expires_at' => 'datetime',
        'share_view_count' => 'integer',
        'sort' => 'integer',
    ];

    protected static function booted(): void
    {
        static::saving(function (self $collection) {
            if (empty($collection->share_token)) {
                $collection->share_token = Str::random(32);
            }
            if (empty($collection->slug)) {
                $base = Str::slug($collection->name);
                $collection->slug = $collection->user_id . '-' . ($base ?: (string) $collection->id);
            }
        });
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function places(): BelongsToMany
    {
        return $this->belongsToMany(Place::class, 'collection_place')
            ->withPivot('sort', 'note')
            ->withTimestamps()
            ->orderBy('collection_place.sort');
    }

    public function media(): HasMany
    {
        return $this->hasMany(Media::class);
    }

    public function getShareUrlAttribute(): ?string
    {
        if (! $this->share_token) {
            return null;
        }
        return url("/share/collection/{$this->share_token}");
    }

    public function isShareExpired(): bool
    {
        return $this->share_expires_at && $this->share_expires_at->isPast();
    }
}
