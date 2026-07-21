<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Region extends Model
{
    use HasFactory;

    protected $fillable = [
        'code', 'name', 'level', 'parent_code',
        'pinyin', 'short_name', 'latitude', 'longitude',
        'is_hot', 'sort',
    ];

    protected $casts = [
        'latitude' => 'float',
        'longitude' => 'float',
        'is_hot' => 'boolean',
    ];

    public function children(): HasMany
    {
        return $this->hasMany(Region::class, 'parent_code', 'code');
    }

    public function parent(): \Illuminate\Database\Eloquent\Relations\BelongsTo
    {
        return $this->belongsTo(Region::class, 'parent_code', 'code');
    }

    /**
     * 给前台用的简略字段
     */
    public function toOption(): array
    {
        return [
            'code' => $this->code,
            'name' => $this->name,
            'short' => $this->short_name ?? $this->name,
            'lat' => $this->latitude ? (float) $this->latitude : null,
            'lng' => $this->longitude ? (float) $this->longitude : null,
        ];
    }
}
