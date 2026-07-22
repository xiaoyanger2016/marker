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

    // 8 大类 (前端 8 个类型菜单 + 搜索筛选 + admin tabs 全部用这套)
    // N°编号 1-8 与用户档案的 8 content types 一一对应
    public const PLACE_TYPES = [
        'self_drive'     => ['label' => '自驾线路', 'icon' => 'N°01', 'color' => '#114B5F', 'desc' => '公路旅行的路径和途经点'],
        'play_water'     => ['label' => '玩水点',   'icon' => 'N°02', 'color' => '#0D3A4A', 'desc' => '可下水游泳戏水的地点'],
        'hiking'         => ['label' => '徒步线路', 'icon' => 'N°03', 'color' => '#2D5F3F', 'desc' => '行走探索的路径'],
        'paddle'         => ['label' => '桨板点',   'icon' => 'N°04', 'color' => '#0D5C5C', 'desc' => '桨板 / SUP 适合的水域'],
        'photo'          => ['label' => '拍照点',   'icon' => 'N°05', 'color' => '#A1461E', 'desc' => '值得出片的取景地'],
        'food'           => ['label' => '美食探店', 'icon' => 'N°06', 'color' => '#C45626', 'desc' => '值得专程去吃的店'],
        'camping'        => ['label' => '露营点',   'icon' => 'N°07', 'color' => '#1A3A3A', 'desc' => '可以过夜的营地'],
        'sunrise_sunset' => ['label' => '日出日落', 'icon' => 'N°08', 'color' => '#7A4A1A', 'desc' => '专门看日出日落的位置'],
    ];

    /**
     * 每个 type 的可用 attribute 字段定义 (admin form 动态生成用)
     * key/label/group/type/unit/options 决定渲染哪个 Filament input
     */
    public const TYPE_ATTRIBUTES = [
        'self_drive' => [
            ['key' => 'distance_km',         'label' => '距离',         'group' => '基本信息', 'type' => 'number',  'unit' => 'km'],
            ['key' => 'duration_minutes',    'label' => '预计时长',     'group' => '基本信息', 'type' => 'number',  'unit' => '分钟'],
            ['key' => 'altitude_meters',     'label' => '最高海拔',     'group' => '基本信息', 'type' => 'number',  'unit' => 'm'],
            ['key' => 'difficulty',          'label' => '难度',         'group' => '基本信息', 'type' => 'select',  'options' => ['easy' => '轻松', 'moderate' => '中等', 'hard' => '困难']],
            ['key' => 'road_condition',      'label' => '路况',         'group' => '基本信息', 'type' => 'select',  'options' => ['paved' => '全程铺装', 'mostly_paved' => '大部分铺装', 'mixed' => '混合', 'offroad' => '越野']],
            ['key' => 'best_season',         'label' => '最佳季节',     'group' => '时间',     'type' => 'checkbox-list', 'options' => ['spring' => '春', 'summer' => '夏', 'autumn' => '秋', 'winter' => '冬']],
            ['key' => 'gas_stations',        'label' => '加油站',       'group' => '沿途',     'type' => 'repeater', 'placeholder' => '加油站名 + km 标记'],
            ['key' => 'waypoints',           'label' => '途经点',       'group' => '沿途',     'type' => 'repeater', 'placeholder' => '途经点名称 + 经纬度'],
            ['key' => 'gear_checklist',      'label' => '装备清单',     'group' => '装备',     'type' => 'repeater'],
            ['key' => 'safety_notes',        'label' => '安全提示',     'group' => '安全',     'type' => 'repeater'],
        ],
        'play_water' => [
            ['key' => 'water_type',          'label' => '水域',         'group' => '基本信息', 'type' => 'select',  'options' => ['lake' => '湖', 'river' => '河', 'sea' => '海', 'pool' => '潭', 'reservoir' => '水库']],
            ['key' => 'water_depth',         'label' => '水深',         'group' => '基本信息', 'type' => 'text',    'unit' => 'm'],
            ['key' => 'is_swimmable',        'label' => '可游泳',       'group' => '基本信息', 'type' => 'toggle'],
            ['key' => 'is_free',             'label' => '免费',         'group' => '基本信息', 'type' => 'toggle'],
            ['key' => 'parking',             'label' => '停车',         'group' => '基本信息', 'type' => 'select',  'options' => ['free' => '免费停车', 'paid' => '收费停车', 'limited' => '停车位有限', 'no' => '无停车']],
            ['key' => 'ticket',              'label' => '门票',         'group' => '基本信息', 'type' => 'text',    'unit' => '元/人'],
            ['key' => 'has_lifeguard',       'label' => '有救生员',     'group' => '安全',     'type' => 'toggle'],
            ['key' => 'best_season',         'label' => '最佳季节',     'group' => '时间',     'type' => 'checkbox-list'],
            ['key' => 'gear_checklist',      'label' => '装备清单',     'group' => '装备',     'type' => 'repeater'],
            ['key' => 'safety_notes',        'label' => '安全提示',     'group' => '安全',     'type' => 'repeater'],
        ],
        'hiking' => [
            ['key' => 'distance_km',         'label' => '距离',         'group' => '基本信息', 'type' => 'number',  'unit' => 'km'],
            ['key' => 'duration_minutes',    'label' => '预计时长',     'group' => '基本信息', 'type' => 'number',  'unit' => '分钟'],
            ['key' => 'altitude_meters',     'label' => '最高海拔',     'group' => '基本信息', 'type' => 'number',  'unit' => 'm'],
            ['key' => 'elevation_gain',      'label' => '累计爬升',     'group' => '基本信息', 'type' => 'number',  'unit' => 'm'],
            ['key' => 'difficulty',          'label' => '难度',         'group' => '基本信息', 'type' => 'select',  'options' => ['easy' => '轻松', 'moderate' => '中等', 'hard' => '困难', 'expert' => '专业']],
            ['key' => 'route_type',          'label' => '线路类型',     'group' => '基本信息', 'type' => 'select',  'options' => ['loop' => '环形', 'out_back' => '往返', 'one_way' => '单程']],
            ['key' => 'best_season',         'label' => '最佳季节',     'group' => '时间',     'type' => 'checkbox-list'],
            ['key' => 'waypoints',           'label' => '途经点',       'group' => '沿途',     'type' => 'repeater'],
            ['key' => 'gear_checklist',      'label' => '装备清单',     'group' => '装备',     'type' => 'repeater'],
            ['key' => 'safety_notes',        'label' => '安全提示',     'group' => '安全',     'type' => 'repeater'],
        ],
        'paddle' => [
            ['key' => 'water_depth',         'label' => '水深',         'group' => '基本信息', 'type' => 'text',    'unit' => 'm'],
            ['key' => 'water_current',       'label' => '水流情况',     'group' => '基本信息', 'type' => 'select',  'options' => ['calm' => '平静', 'mild' => '缓流', 'moderate' => '中流', 'strong' => '急流']],
            ['key' => 'difficulty',          'label' => '难度',         'group' => '基本信息', 'type' => 'select'],
            ['key' => 'rental_available',    'label' => '装备租赁',     'group' => '基本信息', 'type' => 'toggle'],
            ['key' => 'best_time',           'label' => '最佳时间',     'group' => '时间',     'type' => 'text',    'placeholder' => '上午 / 下午 / 黄昏'],
            ['key' => 'gear_checklist',      'label' => '装备清单',     'group' => '装备',     'type' => 'repeater'],
            ['key' => 'safety_notes',        'label' => '安全提示',     'group' => '安全',     'type' => 'repeater'],
        ],
        'photo' => [
            ['key' => 'best_time',           'label' => '最佳时间',     'group' => '时间',     'type' => 'text',    'placeholder' => '上午 / 黄昏 / 夜晚'],
            ['key' => 'best_light',          'label' => '最佳光影',     'group' => '时间',     'type' => 'text',    'placeholder' => '顺光 / 逆光 / 黄金时刻'],
            ['key' => 'viewpoint_count',     'label' => '机位数量',     'group' => '基本信息', 'type' => 'number'],
            ['key' => 'is_drone_allowed',    'label' => '可飞无人机',   'group' => '基本信息', 'type' => 'toggle'],
            ['key' => 'permit_required',     'label' => '需要许可',     'group' => '基本信息', 'type' => 'toggle'],
            ['key' => 'parking',             'label' => '停车',         'group' => '基本信息', 'type' => 'select'],
            ['key' => 'best_season',         'label' => '最佳季节',     'group' => '时间',     'type' => 'checkbox-list'],
            ['key' => 'gear_checklist',      'label' => '装备',         'group' => '装备',     'type' => 'repeater'],
        ],
        'food' => [
            ['key' => 'price_per_person',    'label' => '人均',         'group' => '基本信息', 'type' => 'number',  'unit' => '元'],
            ['key' => 'cuisine_type',        'label' => '菜系',         'group' => '基本信息', 'type' => 'text',    'placeholder' => '川菜 / 西餐 / 咖啡'],
            ['key' => 'business_hours',      'label' => '营业时间',     'group' => '时间',     'type' => 'text',    'placeholder' => '09:00-22:00'],
            ['key' => 'signature_dishes',    'label' => '招牌菜',       'group' => '菜品',     'type' => 'repeater'],
            ['key' => 'reservation',         'label' => '预订方式',     'group' => '服务',     'type' => 'text',    'placeholder' => '电话 / 微信 / 大众点评'],
            ['key' => 'parking',             'label' => '停车',         'group' => '基本信息', 'type' => 'select'],
            ['key' => 'contact',             'label' => '联系方式',     'group' => '服务',     'type' => 'text'],
        ],
        'camping' => [
            ['key' => 'altitude_meters',     'label' => '海拔',         'group' => '基本信息', 'type' => 'number',  'unit' => 'm'],
            ['key' => 'is_free',             'label' => '免费',         'group' => '基本信息', 'type' => 'toggle'],
            ['key' => 'has_water',           'label' => '有水源',       'group' => '设施',     'type' => 'toggle'],
            ['key' => 'has_toilet',          'label' => '有厕所',       'group' => '设施',     'type' => 'toggle'],
            ['key' => 'fire_allowed',        'label' => '可明火',       'group' => '设施',     'type' => 'toggle'],
            ['key' => 'has_signal',          'label' => '有信号',       'group' => '设施',     'type' => 'toggle'],
            ['key' => 'parking',             'label' => '停车',         'group' => '设施',     'type' => 'select'],
            ['key' => 'best_season',         'label' => '最佳季节',     'group' => '时间',     'type' => 'checkbox-list'],
            ['key' => 'gear_checklist',      'label' => '装备清单',     'group' => '装备',     'type' => 'repeater'],
            ['key' => 'safety_notes',        'label' => '安全提示',     'group' => '安全',     'type' => 'repeater'],
        ],
        'sunrise_sunset' => [
            ['key' => 'direction',           'label' => '方位',         'group' => '基本信息', 'type' => 'select',  'options' => ['east' => '东 (日出)', 'west' => '西 (日落)', 'both' => '都能看']],
            ['key' => 'best_time',           'label' => '最佳时间',     'group' => '时间',     'type' => 'text',    'placeholder' => '比日落早 30 分钟'],
            ['key' => 'viewpoint_count',     'label' => '机位数量',     'group' => '基本信息', 'type' => 'number'],
            ['key' => 'difficulty',          'label' => '抵达难度',     'group' => '基本信息', 'type' => 'select'],
            ['key' => 'is_drone_allowed',    'label' => '可飞无人机',   'group' => '基本信息', 'type' => 'toggle'],
            ['key' => 'parking',             'label' => '停车',         'group' => '基本信息', 'type' => 'select'],
            ['key' => 'best_season',         'label' => '最佳季节',     'group' => '时间',     'type' => 'checkbox-list'],
            ['key' => 'gear_checklist',      'label' => '装备',         'group' => '装备',     'type' => 'repeater'],
            ['key' => 'safety_notes',        'label' => '安全提示',     'group' => '安全',     'type' => 'repeater'],
        ],
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
              ->orWhereHas('attributes', fn ($a) => $a->where('attribute_value', 'ilike', $like));
        });
    }

    public function scopeOfType(Builder $query, ?string $type): Builder
    {
        return $query->when($type, fn ($q) => $q->where('place_type', $type));
    }

    public function scopeWithParking(Builder $query): Builder
    {
        return $query->whereHas('attributes', fn ($q) => $q->where('attribute_key', 'parking')
            ->where(function ($q) {
                $q->where('attribute_value', 'not like', '%无停车%');
            }));
    }

    public function scopeFree(Builder $query): Builder
    {
        return $query->where(function ($q) {
            $q->whereDoesntHave('attributes', fn ($a) => $a->where('attribute_key', 'ticket')
                ->where('attribute_value', 'not like', '%免费%'))
              ->orWhereDoesntHave('attributes');
        });
    }

    /**
     * type-specific 属性 (一对多关联到 place_attributes)
     */
    public function attributes(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(PlaceAttribute::class)->orderBy('sort');
    }

    /**
     * 取某个属性的值 (无则 null)
     */
    public function attr(string $key, mixed $default = null): mixed
    {
        $attr = $this->attributes->firstWhere('attribute_key', $key);
        if (! $attr) return $default;
        return match ($attr->value_type) {
            'int'     => (int) $attr->attribute_value,
            'float'   => (float) $attr->attribute_value,
            'bool'    => (bool) $attr->attribute_value,
            'json', 'array' => json_decode($attr->attribute_value, true),
            default   => $attr->attribute_value,
        };
    }

    /**
     * 按 group 分组的属性 (admin 详情页 / 前端详情页用)
     * 返回: ['基本信息' => [['key'=>'distance_km','label'=>'距离','value'=>120,'unit'=>'km'], ...], ...]
     */
    public function attributesByGroup(): array
    {
        $groups = [];
        foreach ($this->attributes as $a) {
            $group = $a->display_group ?? '其他';
            $groups[$group][] = [
                'key'   => $a->attribute_key,
                'label' => $a->display_label ?? $a->attribute_key,
                'value' => $this->attr($a->attribute_key),
                'unit'  => $a->unit,
                'raw'   => $a->attribute_value,
            ];
        }
        return $groups;
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
