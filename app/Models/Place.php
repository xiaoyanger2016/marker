<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Str;

class Place extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'user_id',
        'category_id',
        'place_type',
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
        'rating',
        'visited_at',
        'visit_count',
        'view_count',
        'is_visited',
        'is_wishlist',
        'is_public',
        'poi_source',
        'poi_id',
        'poi_type',
        'metadata',
        // 停车
        'has_parking',
        'parking_fee_type',
        'parking_fee',
        'parking_notes',
        'parking_capacity',
        // 门票
        'has_ticket',
        'ticket_price',
        'ticket_unit',
        'ticket_notes',
        // 游玩信息
        'best_season',
        'suitable_for',
        'recommended_duration_minutes',
        'difficulty',
        'altitude_meters',
        'gear_checklist',
        'safety_notes',
        // 联系
        'booking_url',
        'wechat_id',
    ];

    protected $casts = [
        'latitude' => 'float',
        'longitude' => 'float',
        'price_range' => 'decimal:2',
        'parking_fee' => 'decimal:2',
        'ticket_price' => 'decimal:2',
        'rating' => 'integer',
        'view_count' => 'integer',
        'visit_count' => 'integer',
        'parking_capacity' => 'integer',
        'altitude_meters' => 'integer',
        'recommended_duration_minutes' => 'integer',
        'is_visited' => 'boolean',
        'is_wishlist' => 'boolean',
        'is_public' => 'boolean',
        'has_parking' => 'boolean',
        'has_ticket' => 'boolean',
        'visited_at' => 'date',
        'metadata' => 'array',
        'gear_checklist' => 'array',
        'safety_notes' => 'array',
    ];

    // 地点细类（POI Type）
    // 编辑感版：去 emoji，用 N°编号。前端不显示 icon，靠编号 + 渐变色 + 大字标签。
    public const PLACE_TYPES = [
        'camping' => ['label' => '露营点', 'icon' => 'N°01', 'color' => '#1A3A3A'],
        'mountain' => ['label' => '山峰', 'icon' => 'N°02', 'color' => '#1A1814'],
        'village' => ['label' => '村庄', 'icon' => 'N°03', 'color' => '#847E72'],
        'scenic' => ['label' => '景区/景点', 'icon' => 'N°04', 'color' => '#2D5F3F'],
        'river' => ['label' => '河流/溪流', 'icon' => 'N°05', 'color' => '#0D3A4A'],
        'lake' => ['label' => '湖泊', 'icon' => 'N°06', 'color' => '#114B5F'],
        'beach' => ['label' => '海滩', 'icon' => 'N°07', 'color' => '#A1461E'],
        'waterfall' => ['label' => '瀑布', 'icon' => 'N°08', 'color' => '#0D3A4A'],
        'farm' => ['label' => '农场/采摘', 'icon' => 'N°09', 'color' => '#2D5F3F'],
        'park' => ['label' => '公园', 'icon' => 'N°10', 'color' => '#2D5F3F'],
        'cafe' => ['label' => '咖啡店', 'icon' => 'N°11', 'color' => '#847E72'],
        'restaurant' => ['label' => '餐厅/美食', 'icon' => 'N°12', 'color' => '#C45626'],
        'hotel' => ['label' => '民宿/酒店', 'icon' => 'N°13', 'color' => '#1A1814'],
        'gas_station' => ['label' => '加油站', 'icon' => 'N°14', 'color' => '#847E72'],
        'service_area' => ['label' => '服务区', 'icon' => 'N°15', 'color' => '#847E72'],
        'viewpoint' => ['label' => '观景点', 'icon' => 'N°16', 'color' => '#A1461E'],
        'play_water' => ['label' => '玩水点', 'icon' => 'N°17', 'color' => '#0D3A4A'],
        'ancient_town' => ['label' => '古镇/古村', 'icon' => 'N°18', 'color' => '#1A1814'],
        'temple' => ['label' => '寺庙/古迹', 'icon' => 'N°19', 'color' => '#847E72'],
        'museum' => ['label' => '博物馆', 'icon' => 'N°20', 'color' => '#1A1814'],
        'other' => ['label' => '其他', 'icon' => 'N°21', 'color' => '#4A4640'],
    ];

    public const PARKING_FEE_TYPES = [
        'free' => '免费',
        'per_time' => '按次收费',
        'per_hour' => '按小时收费',
        'per_day' => '按天收费',
        'unknown' => '未知',
    ];

    public const DIFFICULTY_LEVELS = [
        'easy' => ['label' => '简单', 'color' => 'success'],
        'moderate' => ['label' => '中等', 'color' => 'warning'],
        'hard' => ['label' => '困难', 'color' => 'danger'],
    ];

    public const SEASONS = [
        'spring' => '春',
        'summer' => '夏',
        'autumn' => '秋',
        'winter' => '冬',
        'all' => '四季',
    ];

    public const RATING_LABELS = [
        'terrible' => ['label' => '拉垮', 'color' => '#7f1d1d', 'icon' => ''],
        'npc' => ['label' => 'NPC', 'color' => '#6b7280', 'icon' => ''],
        'nice' => ['label' => 'NICE', 'color' => '#0ea5e9', 'icon' => ''],
        'great' => ['label' => '超值', 'color' => '#10b981', 'icon' => ''],
        'amazing' => ['label' => '夯', 'color' => '#dc2626', 'icon' => ''],
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

    public function category(): BelongsTo
    {
        return $this->belongsTo(Category::class);
    }

    public function tags(): BelongsToMany
    {
        return $this->belongsToMany(Tag::class, 'place_tag')->withTimestamps();
    }

    public function media(): HasMany
    {
        return $this->hasMany(Media::class)->orderBy('sort');
    }

    public function coverMedia(): BelongsTo
    {
        return $this->belongsTo(Media::class, 'id', 'place_id')
            ->where('is_cover', true)
            ->withDefault();
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

    // ---- 作用域 ----
    public function scopeOwnedBy(Builder $query, User $user): Builder
    {
        return $query->where('user_id', $user->id);
    }

    public function scopePublic(Builder $query): Builder
    {
        return $query->where('is_public', true);
    }

    public function scopeWishlist(Builder $query): Builder
    {
        return $query->where('is_wishlist', true);
    }

    public function scopeVisited(Builder $query): Builder
    {
        return $query->where('is_visited', true);
    }

    public function scopeInCity(Builder $query, ?string $city): Builder
    {
        return $query->when($city, fn ($q) => $q->where('city', $city));
    }

    /**
     * 雷达模式：附近 N 公里内的地点
     * 使用 PostGIS ST_DWithin（geography 类型，单位米）
     */
    public function scopeNearby(Builder $query, float $lat, float $lng, int $radiusMeters = 5000): Builder
    {
        return $query->whereRaw(
            'ST_DWithin(geog, ST_SetSRID(ST_MakePoint(?, ?), 4326)::geography, ?)',
            [$lng, $lat, $radiusMeters]
        )->orderByRaw(
            'ST_Distance(geog, ST_SetSRID(ST_MakePoint(?, ?), 4326)::geography) ASC',
            [$lng, $lat]
        );
    }

    public function scopeSearch(Builder $query, ?string $keyword): Builder
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
              ->orWhere('poi_type', 'ilike', $like)
              ->orWhere('parking_notes', 'ilike', $like)
              ->orWhere('ticket_notes', 'ilike', $like);
        });
    }

    public function scopeOfType(Builder $query, ?string $type): Builder
    {
        return $query->when($type, fn ($q) => $q->where('place_type', $type));
    }

    public function scopeWithParking(Builder $query): Builder
    {
        return $query->where('has_parking', true);
    }

    public function scopeFree(Builder $query): Builder
    {
        return $query->where('has_ticket', false);
    }

    public function getDistanceTo(float $lat, float $lng): ?float
    {
        if (! $this->latitude || ! $this->longitude) {
            return null;
        }

        // Haversine 公式，返回米
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
