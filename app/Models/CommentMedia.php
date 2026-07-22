<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Phase 17：评论图片/视频关联 (PIVOT 实体，承载 kind + sequence)
 *  - 一条评论可挂 N 张图 + M 个视频
 *  - kind 冗余自 media.type (image/video) — 加速按 type 查询
 */
class CommentMedia extends Model
{
    protected $table = 'comment_media';

    protected $fillable = [
        'comment_id',
        'media_id',
        'kind',
        'sequence',
    ];

    protected $casts = [
        'sequence' => 'integer',
    ];

    public function comment(): BelongsTo
    {
        return $this->belongsTo(Comment::class);
    }

    public function media(): BelongsTo
    {
        return $this->belongsTo(Media::class);
    }
}
