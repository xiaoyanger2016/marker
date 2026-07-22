<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * 美食探店 1:1 子表
 * 单地点
 */
class ContentFood extends Model
{
    use HasFactory;

    protected $table = 'content_food';

    protected $fillable = [
        'content_id',
        'price_per_person',
        'cuisine_type',
        'business_hours',
        'signature_dishes',
        'reservation',
        'parking',
        'contact',
    ];

    protected $casts = [
        'price_per_person' => 'decimal:2',
        'signature_dishes' => 'array',
    ];

    public function content(): BelongsTo
    {
        return $this->belongsTo(Content::class);
    }
}
