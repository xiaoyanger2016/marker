<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Str;

class Route extends Model
{
    use HasFactory, SoftDeletes;

    protected $table = 'routes';

    protected $fillable = [
        'user_id',
        'cover_media_id',
        'category_id',
        'type',
        'name',
        'slug',
        'subtitle',
        'summary',
        'description',
        'rating_label',
        'difficulty',
        'distance_km',
        'duration_hours',
        'city',
        'province',
        'start_point',
        'end_point',
        'best_season',
        'suitable_for',
        'is_public',
        'is_featured',
        'requires_order',
        'view_count',
        'like_count',
        'save_count',
        'heat_score',
        'metadata',
        'gear_checklist',
        'safety_notes',
    ];

    protected $casts = [
        'distance_km' => 'decimal:2',
        'duration_hours' => 'integer',
        'is_public' => 'boolean',
        'is_featured' => 'boolean',
        'requires_order' => 'boolean',
        'view_count' => 'integer',
        'like_count' => 'integer',
        'save_count' => 'integer',
        'heat_score' => 'float',
        'metadata' => 'array',
        'gear_checklist' => 'array',
        'safety_notes' => 'array',
    ];

    // Route.type 直接复用 Place::PLACE_TYPES (8 大类)
    // 现实中 Route 主要用 self_drive / hiking，其他 6 类是 Place 的子集
    public const TYPES = [
        'self_drive'     => ['label' => '自驾线路', 'icon' => 'N°01', 'color' => '#114B5F'],
        'play_water'     => ['label' => '玩水点',   'icon' => 'N°02', 'color' => '#0D3A4A'],
        'hiking'         => ['label' => '徒步线路', 'icon' => 'N°03', 'color' => '#2D5F3F'],
        'paddle'         => ['label' => '桨板点',   'icon' => 'N°04', 'color' => '#0D5C5C'],
        'photo'          => ['label' => '拍照点',   'icon' => 'N°05', 'color' => '#A1461E'],
        'food'           => ['label' => '美食探店', 'icon' => 'N°06', 'color' => '#C45626'],
        'camping'        => ['label' => '露营点',   'icon' => 'N°07', 'color' => '#1A3A3A'],
        'sunrise_sunset' => ['label' => '日出日落', 'icon' => 'N°08', 'color' => '#7A4A1A'],
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
        static::saving(function (self $route) {
            if (empty($route->slug)) {
                $base = Str::slug($route->name);
                $route->slug = $route->user_id . '-' . ($base ?: (string) $route->id);
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

    /**
     * 线路中的地点
     * - 自驾：按 order 升序
     * - 徒步：order=0 无序，但按 id 稳定
     */
    public function places(): BelongsToMany
    {
        return $this->belongsToMany(Place::class, 'route_place')
            ->withPivot('order', 'stay_minutes', 'eta_minutes', 'notes')
            ->withTimestamps();
    }

    public function orderedPlaces(): BelongsToMany
    {
        return $this->places()->orderBy('route_place.order');
    }

    public function media(): HasMany
    {
        return $this->hasMany(Media::class, 'collection_id', 'id')
            ->where('place_id', null);
    }

    public function notes(): HasMany
    {
        return $this->morphMany(Note::class, 'noteable');
    }

    // ---- 作用域 ----
    public function scopePublic($query)
    {
        return $query->where('is_public', true);
    }

    public function scopeFeatured($query)
    {
        return $query->where('is_featured', true);
    }

    public function scopeOfType($query, string $type)
    {
        return $query->where('type', $type);
    }

    public function scopeOrderedByHeat($query, string $direction = 'desc')
    {
        return $query->orderBy('heat_score', $direction)->orderBy('view_count', $direction);
    }

    public function typeMeta(): array
    {
        return self::TYPES[$this->type] ?? ['label' => $this->type, 'icon' => 'N°00', 'color' => '#4A4640'];
    }

    public function ratingMeta(): array
    {
        return self::RATING_LABELS[$this->rating_label] ?? null;
    }

    /**
     * 重新计算热度：综合 view/like/save + 时间衰减
     * heat = (view*1 + like*3 + save*5) / pow(hours_since_create/24 + 2, 1.5)
     */
    public function recalculateHeat(): void
    {
        $hoursSinceCreate = max(1, $this->created_at?->diffInHours(now()) ?? 1);
        $raw = $this->view_count * 1 + $this->like_count * 3 + $this->save_count * 5;
        $heat = $raw / pow(($hoursSinceCreate / 24) + 2, 1.5);
        $this->heat_score = round($heat, 4);
        $this->save();
    }
}
