<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * 日出日落 1:1 子表
 * 单地点
 */
class ContentSunriseSunset extends Model
{
    use HasFactory;

    protected $table = 'content_sunrise_sunset';

    protected $fillable = [
        'content_id',
        'direction',
        'best_time',
        'viewpoint_count',
        'difficulty',
        'is_drone_allowed',
        'parking',
        'best_season',
        'gear_checklist',
        'safety_notes',
    ];

    protected $casts = [
        'viewpoint_count'  => 'integer',
        'is_drone_allowed' => 'boolean',
        'best_season'      => 'array',
        'gear_checklist'   => 'array',
        'safety_notes'     => 'array',
    ];

    public function content(): BelongsTo
    {
        return $this->belongsTo(Content::class);
    }
}
