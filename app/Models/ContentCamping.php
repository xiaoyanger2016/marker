<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * 露营点 1:1 子表
 * 单地点
 */
class ContentCamping extends Model
{
    use HasFactory;

    protected $table = 'content_camping';

    protected $fillable = [
        'content_id',
        'altitude_meters',
        'is_free',
        'has_water',
        'has_toilet',
        'fire_allowed',
        'has_signal',
        'parking',
        'best_season',
        'gear_checklist',
        'safety_notes',
    ];

    protected $casts = [
        'altitude_meters' => 'integer',
        'is_free'         => 'boolean',
        'has_water'       => 'boolean',
        'has_toilet'      => 'boolean',
        'fire_allowed'    => 'boolean',
        'has_signal'      => 'boolean',
        'best_season'     => 'array',
        'gear_checklist'  => 'array',
        'safety_notes'    => 'array',
    ];

    public function content(): BelongsTo
    {
        return $this->belongsTo(Content::class);
    }
}
