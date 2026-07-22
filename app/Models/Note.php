<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class Note extends Model
{
    use HasFactory;

    protected $fillable = [
        'place_id',
        'user_id',
        'title',
        'source',
        'source_url',
        'author',
        'content',
        'xhs_note_id',
        'xhs_xsec_token',
        'xhs_meta',
        'cover_url',
        'cover_media_id',
        'image_urls',
        'video_urls',
        'published_at',
    ];

    protected $casts = [
        'xhs_meta' => 'array',
        'image_urls' => 'array',
        'video_urls' => 'array',
        'published_at' => 'datetime',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function place(): BelongsTo
    {
        return $this->belongsTo(Place::class);
    }

    public function coverMedia(): BelongsTo
    {
        return $this->belongsTo(Media::class, 'cover_media_id');
    }

    /**
     * Phase 19：被哪些内容关联
     */
    public function contents(): BelongsToMany
    {
        return $this->belongsToMany(Content::class, 'content_notes')
            ->withPivot('sequence', 'role')
            ->orderBy('content_notes.sequence');
    }

    public function scopeFromXiaohongshu(Builder $query): Builder
    {
        return $query->where('source', 'xiaohongshu');
    }

    public function scopeForPlace(Builder $query, Place $place): Builder
    {
        return $query->where('place_id', $place->id);
    }
}
