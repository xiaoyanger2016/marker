<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * 自驾线路 1:1 子表
 * 多地点带顺序 (content_places.sequence)
 */
class ContentSelfDrive extends Model
{
    use HasFactory;

    protected $table = 'content_self_drive';

    protected $fillable = [
        'content_id',
        'distance_km',
        'duration_minutes',
        'altitude_meters',
        'difficulty',
        'road_condition',
        'best_season',
        'gas_stations',
        'waypoints',
        'gear_checklist',
        'safety_notes',
        'two_foot_route_id',
    ];

    protected $casts = [
        'distance_km'      => 'decimal:2',
        'duration_minutes' => 'integer',
        'altitude_meters'  => 'integer',
        'best_season'      => 'array',
        'gas_stations'     => 'array',
        'waypoints'        => 'array',
        'gear_checklist'   => 'array',
        'safety_notes'     => 'array',
    ];

    public function content(): BelongsTo
    {
        return $this->belongsTo(Content::class);
    }
}
