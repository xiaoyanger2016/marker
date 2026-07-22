<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * 多态评论
 *  - commentable_type + commentable_id (如 Content/Place/Activity)
 *  - parent_id 支持二级回复
 *  - rating_label/rating_value 评分 (可选)
 */
class Comment extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'commentable_type',
        'commentable_id',
        'user_id',
        'parent_id',
        'body',
        'rating_label',
        'rating_value',
        'is_public',
    ];

    protected $casts = [
        'rating_value' => 'integer',
        'is_public'    => 'boolean',
    ];

    public function commentable(): MorphTo
    {
        return $this->morphTo();
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function parent(): BelongsTo
    {
        return $this->belongsTo(Comment::class, 'parent_id');
    }

    public function replies(): HasMany
    {
        return $this->hasMany(Comment::class, 'parent_id')->latest();
    }

    /**
     * Phase 17：评论图片/视频 (多模态)
     *  - 中间表 comment_media
     *  - 复用 media 表 (统一管理 storage + 缩略图)
     */
    public function media(): BelongsToMany
    {
        return $this->belongsToMany(Media::class, 'comment_media')
            ->withPivot('kind', 'sequence')
            ->orderBy('comment_media.sequence');
    }

    public function images()
    {
        return $this->media()->wherePivot('kind', 'image');
    }

    public function videos()
    {
        return $this->media()->wherePivot('kind', 'video');
    }

    public function scopePublic($q)
    {
        return $q->where('is_public', true);
    }

    public function scopeRoots($q)
    {
        return $q->whereNull('parent_id');
    }
}
