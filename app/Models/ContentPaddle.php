<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * 桨板点 1:1 子表
 * 单地点
 */
class ContentPaddle extends Model
{
    use HasFactory;

    protected $table = 'content_paddle';

    protected $fillable = [
        'content_id',
        'water_depth',
        'water_current',
        'difficulty',
        'rental_available',
        'best_time',
        'gear_checklist',
        'safety_notes',
    ];

    protected $casts = [
        'rental_available' => 'boolean',
        'gear_checklist'   => 'array',
        'safety_notes'     => 'array',
    ];

    public function content(): BelongsTo
    {
        return $this->belongsTo(Content::class);
    }
}
