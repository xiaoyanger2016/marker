<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Phase 18 · Bug 1: 首页 "§ 02 — 本期精选" 人工 pinned
 *  - 一条 content 只能被 pick 一次 (unique content_id)
 *  - sort 升序 = 排序
 *  - 没有 picked 时 fallback 随机 N 条
 */
class ContentPick extends Model
{
    protected $table = 'content_picks';

    protected $fillable = [
        'content_id',
        'picked_by',
        'sort',
        'note',
    ];

    protected $casts = [
        'sort' => 'integer',
    ];

    public function content(): BelongsTo
    {
        return $this->belongsTo(Content::class);
    }

    public function picker(): BelongsTo
    {
        return $this->belongsTo(User::class, 'picked_by');
    }
}
