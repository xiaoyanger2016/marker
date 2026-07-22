<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * 玩水点 1:1 子表
 * 单地点
 */
class ContentPlayWater extends Model
{
    use HasFactory;

    protected $table = 'content_play_water';

    protected $fillable = [
        'content_id',
        'water_type',
        'water_depth',
        'is_swimmable',
        'is_free',
        'parking',
        'ticket',
        'has_lifeguard',
        'best_season',
        'gear_checklist',
        'safety_notes',
    ];

    protected $casts = [
        'is_swimmable'   => 'boolean',
        'is_free'        => 'boolean',
        'has_lifeguard'  => 'boolean',
        'best_season'    => 'array',
        'gear_checklist' => 'array',
        'safety_notes'   => 'array',
    ];

    public function content(): BelongsTo
    {
        return $this->belongsTo(Content::class);
    }
}
