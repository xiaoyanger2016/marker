<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Support\Str;

class Tag extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'name',
        'slug',
        'color',
        'usage_count',
    ];

    protected $casts = [
        'usage_count' => 'integer',
    ];

    protected static function booted(): void
    {
        static::saving(function (self $tag) {
            if (empty($tag->slug)) {
                $tag->slug = Str::slug($tag->name);
            }
        });
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function places(): BelongsToMany
    {
        return $this->belongsToMany(Place::class, 'place_tag')
            ->withTimestamps();
    }

    /**
     * Phase 18.6: Tag 关联内容 (content_tags pivot)
     */
    public function contents(): BelongsToMany
    {
        return $this->belongsToMany(Content::class, 'content_tags')
            ->withTimestamps();
    }
}
