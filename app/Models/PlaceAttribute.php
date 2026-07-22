<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PlaceAttribute extends Model
{
    use HasFactory;

    protected $fillable = [
        'place_id',
        'attribute_key',
        'attribute_value',
        'value_type',
        'is_system',
        'display_label',
        'display_group',
        'input_type',
        'unit',
        'sort',
    ];

    protected $casts = [
        'is_system' => 'boolean',
        'sort'      => 'integer',
    ];

    public function place(): BelongsTo
    {
        return $this->belongsTo(Place::class);
    }

    /**
     * 解码后的实际值 (按 value_type 转换)
     */
    public function getDecodedValueAttribute(): mixed
    {
        return match ($this->value_type) {
            'int'         => (int) $this->attribute_value,
            'float'       => (float) $this->attribute_value,
            'bool'        => (bool) $this->attribute_value,
            'json','array' => json_decode((string) $this->attribute_value, true),
            default       => $this->attribute_value,
        };
    }
}
