<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
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

    public function scopePublic($q)
    {
        return $q->where('is_public', true);
    }

    public function scopeRoots($q)
    {
        return $q->whereNull('parent_id');
    }
}
