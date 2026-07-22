<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * 拍照点 1:1 子表
 * 单地点
 */
class ContentPhoto extends Model
{
    use HasFactory;

    protected $table = 'content_photo';

    protected $fillable = [
        'content_id',
        'best_time',
        'best_light',
        'viewpoint_count',
        'is_drone_allowed',
        'permit_required',
        'parking',
        'best_season',
        'gear_checklist',
    ];

    protected $casts = [
        'viewpoint_count'  => 'integer',
        'is_drone_allowed' => 'boolean',
        'permit_required'  => 'boolean',
        'best_season'      => 'array',
        'gear_checklist'   => 'array',
    ];

    public function content(): BelongsTo
    {
        return $this->belongsTo(Content::class);
    }
}
