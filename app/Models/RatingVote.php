<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Phase 17：内容评分投票 (一人对一内容一票)
 *  - content.rating_label 是 cache 字段
 *  - 投票时 updateOrCreate 写入本表 + 调用 Content::recomputeRating() 聚合
 *  - 聚合规则：众数 (mode) → Content::RATING_LABELS 找对应 key
 *           平均值 (avg) 作为辅助指标 (rating_avg)
 */
class RatingVote extends Model
{
    protected $fillable = [
        'content_id',
        'user_id',
        'rating_value',
        'rating_label',
    ];

    protected $casts = [
        'rating_value' => 'integer',
    ];

    public function content(): BelongsTo
    {
        return $this->belongsTo(Content::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
