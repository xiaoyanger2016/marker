<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Str;

/**
 * Place — 纯 location 子表
 *
 * 8 大类内容贴 (Content) 通过 content_places 关联到这里。
 * 自身不持有 type/category/状态, 那些都属于 Content 维度。
 */
class Place extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'user_id',
        'name',
        'slug',
        'address',
        'city',
        'province',
        'country',
        'district',
        'latitude',
        'longitude',
        'description',
        'phone',
        'website',
        'business_hours',
        'price_range',
        'poi_source',
        'poi_id',
        'poi_type',
        'metadata',
        'booking_url',
        'wechat_id',
        'rating_label',
    ];

    protected $casts = [
        'latitude'      => 'float',
        'longitude'     => 'float',
        'price_range'   => 'decimal:2',
        'metadata'      => 'array',
    ];

    protected static function booted(): void
    {
        static::saving(function (self $place) {
            if (empty($place->slug)) {
                $base = Str::slug($place->name);
                $place->slug = $place->user_id . '-' . ($base ?: (string) $place->id);
            }
        });
    }

    // ---- 关联 ----
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function tags(): BelongsToMany
    {
        return $this->belongsToMany(Tag::class, 'place_tag')->withTimestamps();
    }

    public function media(): HasMany
    {
        return $this->hasMany(Media::class)->orderBy('sort');
    }

    public function notes(): HasMany
    {
        return $this->hasMany(Note::class);
    }

    public function collections(): BelongsToMany
    {
        return $this->belongsToMany(Collection::class, 'collection_place')
            ->withPivot('sort', 'note')
            ->withTimestamps();
    }

    /**
     * 出现在哪些 Content 里 (自驾/徒步等多地点)
     */
    public function contents(): BelongsToMany
    {
        return $this->belongsToMany(Content::class, 'content_places')
            ->withPivot('sequence', 'notes')
            ->orderBy('content_places.sequence');
    }

    // ---- 作用域 ----
    public function scopeOwnedBy($query, User $user)
    {
        return $query->where('user_id', $user->id);
    }

    public function scopeInCity($query, ?string $city)
    {
        return $query->when($city, fn ($q) => $q->where('city', $city));
    }

    /**
     * 雷达模式：附近 N 公里内的地点
     */
    public function scopeNearby($query, float $lat, float $lng, int $radiusMeters = 5000)
    {
        return $query->whereRaw(
            'ST_DWithin(geog, ST_SetSRID(ST_MakePoint(?, ?), 4326)::geography, ?)',
            [$lng, $lat, $radiusMeters]
        )->orderByRaw(
            'ST_Distance(geog, ST_SetSRID(ST_MakePoint(?, ?), 4326)::geography) ASC',
            [$lng, $lat]
        );
    }

    public function scopeSearch($query, ?string $keyword)
    {
        if (! $keyword) {
            return $query;
        }
        $like = '%' . $keyword . '%';
        return $query->where(function ($q) use ($like) {
            $q->where('name', 'ilike', $like)
              ->orWhere('address', 'ilike', $like)
              ->orWhere('description', 'ilike', $like)
              ->orWhere('city', 'ilike', $like)
              ->orWhere('poi_type', 'ilike', $like);
        });
    }

    public function getDistanceTo(float $lat, float $lng): ?float
    {
        if (! $this->latitude || ! $this->longitude) {
            return null;
        }

        $earthRadius = 6371000;
        $latFromRad = deg2rad($this->latitude);
        $latToRad = deg2rad($lat);
        $latDelta = deg2rad($lat - $this->latitude);
        $lonDelta = deg2rad($lng - $this->longitude);

        $a = sin($latDelta / 2) * sin($latDelta / 2)
            + cos($latFromRad) * cos($latToRad)
            * sin($lonDelta / 2) * sin($lonDelta / 2);
        $c = 2 * atan2(sqrt($a), sqrt(1 - $a));

        return $earthRadius * $c;
    }
}
