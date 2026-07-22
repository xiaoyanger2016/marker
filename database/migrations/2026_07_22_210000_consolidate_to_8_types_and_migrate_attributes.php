<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * 21 type → 8 type 合并 + 旧 type-specific 字段 → place_attributes 关联表
 *
 * 旧 place 表上散落的 type-specific 字段 (gear_checklist/safety_notes/difficulty/...) 
 * 全部迁移到 place_attributes 关联表。
 */
return new class extends Migration
{
    /**
     * 21 → 8 映射 (按物理属性聚类)
     */
    private const TYPE_MAP = [
        // camping
        'camping'   => 'camping',
        'village'   => 'camping',   // 村庄常带露营
        'hotel'     => 'camping',   // 民宿/酒店跟露营同属住宿

        // play_water
        'play_water'=> 'play_water',
        'river'     => 'play_water',
        'lake'      => 'play_water',
        'beach'     => 'play_water',
        'waterfall' => 'play_water',

        // photo (拍照 / 景区 / 文化古迹)
        'photo'     => 'photo',     // 保留兼容
        'scenic'    => 'photo',     // 景区 = 拍照
        'viewpoint' => 'photo',     // 观景点
        'farm'      => 'photo',     // 农场/采摘
        'park'      => 'photo',     // 公园
        'ancient_town' => 'photo',  // 古镇/古村
        'temple'    => 'photo',     // 寺庙/古迹
        'museum'    => 'photo',     // 博物馆

        // food
        'cafe'      => 'food',
        'restaurant'=> 'food',

        // self_drive
        'self_drive'=> 'self_drive', // 兼容 Route 来的 (虽然 Place 不会直接有)
        'gas_station'  => 'self_drive',
        'service_area' => 'self_drive',

        // sunrise_sunset (山岳/云海类归此)
        'mountain'  => 'sunrise_sunset',

        // null 保留
        'other'     => null,
    ];

    /**
     * 系统预定义的 8 大类 + 每类的可用 attribute keys
     */
    public const ATTRIBUTE_DEFINITIONS = [
        'self_drive' => [
            ['key' => 'distance_km',         'label' => '距离',         'group' => '基本信息', 'type' => 'float',    'unit' => 'km'],
            ['key' => 'duration_minutes',    'label' => '预计时长',     'group' => '基本信息', 'type' => 'int',      'unit' => '分钟'],
            ['key' => 'altitude_meters',     'label' => '最高海拔',     'group' => '基本信息', 'type' => 'int',      'unit' => 'm'],
            ['key' => 'difficulty',          'label' => '难度',         'group' => '基本信息', 'type' => 'select',   'options' => ['easy' => '轻松', 'moderate' => '中等', 'hard' => '困难']],
            ['key' => 'road_condition',      'label' => '路况',         'group' => '基本信息', 'type' => 'select',   'options' => ['paved' => '全程铺装', 'mostly_paved' => '大部分铺装', 'mixed' => '混合', 'offroad' => '越野']],
            ['key' => 'best_season',         'label' => '最佳季节',     'group' => '时间',     'type' => 'multiselect', 'options' => ['spring' => '春', 'summer' => '夏', 'autumn' => '秋', 'winter' => '冬']],
            ['key' => 'gas_stations',        'label' => '加油站位置',   'group' => '沿途',     'type' => 'repeater', 'placeholder' => '服务区/加油站名 + km 标记'],
            ['key' => 'service_areas',       'label' => '服务区',       'group' => '沿途',     'type' => 'repeater'],
            ['key' => 'waypoints',           'label' => '途经点',       'group' => '沿途',     'type' => 'repeater', 'placeholder' => '途经点名称 + 经纬度'],
            ['key' => 'gear_checklist',      'label' => '装备清单',     'group' => '装备',     'type' => 'repeater'],
            ['key' => 'safety_notes',        'label' => '安全提示',     'group' => '安全',     'type' => 'repeater'],
        ],

        'play_water' => [
            ['key' => 'water_type',          'label' => '水域',         'group' => '基本信息', 'type' => 'select', 'options' => ['lake' => '湖', 'river' => '河', 'sea' => '海', 'pool' => '潭', 'reservoir' => '水库']],
            ['key' => 'water_depth',         'label' => '水深',         'group' => '基本信息', 'type' => 'text',     'unit' => 'm'],
            ['key' => 'is_swimmable',        'label' => '可游泳',       'group' => '基本信息', 'type' => 'bool'],
            ['key' => 'is_free',             'label' => '免费',         'group' => '基本信息', 'type' => 'bool'],
            ['key' => 'parking',             'label' => '停车',         'group' => '基本信息', 'type' => 'select', 'options' => ['free' => '免费停车', 'paid' => '收费停车', 'limited' => '停车位有限', 'no' => '无停车']],
            ['key' => 'ticket',              'label' => '门票',         'group' => '基本信息', 'type' => 'text',     'unit' => '元/人'],
            ['key' => 'has_lifeguard',       'label' => '有救生员',     'group' => '安全',     'type' => 'bool'],
            ['key' => 'best_season',         'label' => '最佳季节',     'group' => '时间',     'type' => 'multiselect'],
            ['key' => 'gear_checklist',      'label' => '装备清单',     'group' => '装备',     'type' => 'repeater'],
            ['key' => 'safety_notes',        'label' => '安全提示',     'group' => '安全',     'type' => 'repeater'],
        ],

        'hiking' => [
            ['key' => 'distance_km',         'label' => '距离',         'group' => '基本信息', 'type' => 'float',    'unit' => 'km'],
            ['key' => 'duration_minutes',    'label' => '预计时长',     'group' => '基本信息', 'type' => 'int',      'unit' => '分钟'],
            ['key' => 'altitude_meters',     'label' => '最高海拔',     'group' => '基本信息', 'type' => 'int',      'unit' => 'm'],
            ['key' => 'elevation_gain',      'label' => '累计爬升',     'group' => '基本信息', 'type' => 'int',      'unit' => 'm'],
            ['key' => 'difficulty',          'label' => '难度',         'group' => '基本信息', 'type' => 'select',   'options' => ['easy' => '轻松', 'moderate' => '中等', 'hard' => '困难', 'expert' => '专业']],
            ['key' => 'route_type',          'label' => '线路类型',     'group' => '基本信息', 'type' => 'select',   'options' => ['loop' => '环形', 'out_back' => '往返', 'one_way' => '单程']],
            ['key' => 'best_season',         'label' => '最佳季节',     'group' => '时间',     'type' => 'multiselect'],
            ['key' => 'waypoints',           'label' => '途经点',       'group' => '沿途',     'type' => 'repeater'],
            ['key' => 'gear_checklist',      'label' => '装备清单',     'group' => '装备',     'type' => 'repeater'],
            ['key' => 'safety_notes',        'label' => '安全提示',     'group' => '安全',     'type' => 'repeater'],
        ],

        'paddle' => [
            ['key' => 'water_depth',         'label' => '水深',         'group' => '基本信息', 'type' => 'text',     'unit' => 'm'],
            ['key' => 'water_current',       'label' => '水流情况',     'group' => '基本信息', 'type' => 'select',   'options' => ['calm' => '平静', 'mild' => '缓流', 'moderate' => '中流', 'strong' => '急流']],
            ['key' => 'difficulty',          'label' => '难度',         'group' => '基本信息', 'type' => 'select'],
            ['key' => 'rental_available',    'label' => '装备租赁',     'group' => '基本信息', 'type' => 'bool'],
            ['key' => 'best_time',           'label' => '最佳时间',     'group' => '时间',     'type' => 'text',     'placeholder' => '上午 / 下午 / 黄昏'],
            ['key' => 'gear_checklist',      'label' => '装备清单',     'group' => '装备',     'type' => 'repeater'],
            ['key' => 'safety_notes',        'label' => '安全提示',     'group' => '安全',     'type' => 'repeater'],
        ],

        'photo' => [
            ['key' => 'best_time',           'label' => '最佳时间',     'group' => '时间',     'type' => 'text',     'placeholder' => '上午 / 黄昏 / 夜晚'],
            ['key' => 'best_light',          'label' => '最佳光影',     'group' => '时间',     'type' => 'text',     'placeholder' => '顺光 / 逆光 / 黄金时刻'],
            ['key' => 'viewpoint_count',     'label' => '机位数量',     'group' => '基本信息', 'type' => 'int'],
            ['key' => 'is_drone_allowed',    'label' => '可飞无人机',   'group' => '基本信息', 'type' => 'bool'],
            ['key' => 'permit_required',     'label' => '需要许可',     'group' => '基本信息', 'type' => 'bool'],
            ['key' => 'parking',             'label' => '停车',         'group' => '基本信息', 'type' => 'select'],
            ['key' => 'best_season',         'label' => '最佳季节',     'group' => '时间',     'type' => 'multiselect'],
            ['key' => 'gear_checklist',      'label' => '装备',         'group' => '装备',     'type' => 'repeater'],
        ],

        'food' => [
            ['key' => 'price_per_person',    'label' => '人均',         'group' => '基本信息', 'type' => 'float',    'unit' => '元'],
            ['key' => 'cuisine_type',        'label' => '菜系',         'group' => '基本信息', 'type' => 'text',     'placeholder' => '川菜 / 西餐 / 咖啡'],
            ['key' => 'business_hours',      'label' => '营业时间',     'group' => '时间',     'type' => 'text',     'placeholder' => '09:00-22:00'],
            ['key' => 'signature_dishes',    'label' => '招牌菜',       'group' => '菜品',     'type' => 'repeater'],
            ['key' => 'reservation',         'label' => '预订方式',     'group' => '服务',     'type' => 'text',     'placeholder' => '电话 / 微信 / 大众点评'],
            ['key' => 'parking',             'label' => '停车',         'group' => '基本信息', 'type' => 'select'],
            ['key' => 'contact',             'label' => '联系方式',     'group' => '服务',     'type' => 'text'],
        ],

        'camping' => [
            ['key' => 'altitude_meters',     'label' => '海拔',         'group' => '基本信息', 'type' => 'int',      'unit' => 'm'],
            ['key' => 'is_free',             'label' => '免费',         'group' => '基本信息', 'type' => 'bool'],
            ['key' => 'has_water',           'label' => '有水源',       'group' => '设施',     'type' => 'bool'],
            ['key' => 'has_toilet',          'label' => '有厕所',       'group' => '设施',     'type' => 'bool'],
            ['key' => 'fire_allowed',        'label' => '可明火',       'group' => '设施',     'type' => 'bool'],
            ['key' => 'has_signal',          'label' => '有信号',       'group' => '设施',     'type' => 'bool'],
            ['key' => 'parking',             'label' => '停车',         'group' => '设施',     'type' => 'select'],
            ['key' => 'best_season',         'label' => '最佳季节',     'group' => '时间',     'type' => 'multiselect'],
            ['key' => 'gear_checklist',      'label' => '装备清单',     'group' => '装备',     'type' => 'repeater'],
            ['key' => 'safety_notes',        'label' => '安全提示',     'group' => '安全',     'type' => 'repeater'],
        ],

        'sunrise_sunset' => [
            ['key' => 'direction',           'label' => '方位',         'group' => '基本信息', 'type' => 'select',   'options' => ['east' => '东 (日出)', 'west' => '西 (日落)', 'both' => '都看']],
            ['key' => 'best_time',           'label' => '最佳时间',     'group' => '时间',     'type' => 'text',     'placeholder' => '比日落早 30 分钟'],
            ['key' => 'viewpoint_count',     'label' => '机位数量',     'group' => '基本信息', 'type' => 'int'],
            ['key' => 'difficulty',          'label' => '抵达难度',     'group' => '基本信息', 'type' => 'select'],
            ['key' => 'is_drone_allowed',    'label' => '可飞无人机',   'group' => '基本信息', 'type' => 'bool'],
            ['key' => 'parking',             'label' => '停车',         'group' => '基本信息', 'type' => 'select'],
            ['key' => 'best_season',         'label' => '最佳季节',     'group' => '时间',     'type' => 'multiselect'],
            ['key' => 'gear_checklist',      'label' => '装备',         'group' => '装备',     'type' => 'repeater'],
            ['key' => 'safety_notes',        'label' => '安全提示',     'group' => '安全',     'type' => 'repeater'],
        ],
    ];

    public function up(): void
    {
        // 1. place_type: 21 → 8
        foreach (self::TYPE_MAP as $old => $new) {
            if ($old === 'other') continue;
            DB::table('places')
                ->where('place_type', $old)
                ->update(['place_type' => $new]);
        }

        // 2. 旧 type-specific 字段 → place_attributes
        $places = DB::table('places')->select('id', 'place_type',
            'has_parking', 'parking_fee_type', 'parking_fee', 'parking_notes', 'parking_capacity',
            'has_ticket', 'ticket_price', 'ticket_unit', 'ticket_notes',
            'best_season', 'suitable_for', 'recommended_duration_minutes',
            'difficulty', 'altitude_meters', 'gear_checklist', 'safety_notes',
        )->get();

        $now = now();
        $rows = [];

        foreach ($places as $p) {
            $sort = 0;
            $push = function ($key, $value, $valueType, $label, $group, $inputType = 'text', $unit = null) use (&$rows, $p, $now, &$sort) {
                if ($value === null) return;
                $rows[] = [
                    'place_id'        => $p->id,
                    'attribute_key'   => $key,
                    'attribute_value' => is_array($value) || is_object($value) ? json_encode($value, JSON_UNESCAPED_UNICODE) : (string) $value,
                    'value_type'      => $valueType,
                    'is_system'       => true,
                    'display_label'   => $label,
                    'display_group'   => $group,
                    'input_type'      => $inputType,
                    'unit'            => $unit,
                    'sort'            => $sort++ * 10,
                    'created_at'      => $now,
                    'updated_at'      => $now,
                ];
            };

            // 停车
            if ($p->has_parking !== null) {
                $parkingLabel = match (true) {
                    $p->has_parking === true => '停车: ' . match((string) $p->parking_fee_type) {
                        'free' => '免费', 'paid' => '收费', 'per_time' => '按次收费', default => '有'
                    } . ($p->parking_fee ? ' ¥' . $p->parking_fee : '') . ($p->parking_capacity ? ' (' . $p->parking_capacity . '位)' : '') . ($p->parking_notes ? ' - ' . $p->parking_notes : ''),
                    default => '无停车',
                };
                $push('parking', $parkingLabel, 'string', '停车', '设施', 'select');
            }
            // 门票
            if ($p->has_ticket !== null) {
                $ticketLabel = match (true) {
                    $p->has_ticket === true => '¥' . ($p->ticket_price ?? 0) . '/' . ($p->ticket_unit ?? '人') . ($p->ticket_notes ? ' - ' . $p->ticket_notes : ''),
                    default => '免费',
                };
                $push('ticket', $ticketLabel, 'string', '门票', '基本信息');
            }
            // 季节
            if ($p->best_season) {
                $push('best_season', $p->best_season, 'string', '最佳季节', '时间', 'multiselect');
            }
            // 适合人群
            if ($p->suitable_for) {
                $push('suitable_for', $p->suitable_for, 'string', '适合人群', '基本信息');
            }
            // 时长
            if ($p->recommended_duration_minutes) {
                $push('duration_minutes', (int) $p->recommended_duration_minutes, 'int', '建议游玩时长', '基本信息', 'int', '分钟');
            }
            // 难度
            if ($p->difficulty) {
                $push('difficulty', $p->difficulty, 'string', '难度', '基本信息', 'select');
            }
            // 海拔
            if ($p->altitude_meters) {
                $push('altitude_meters', (int) $p->altitude_meters, 'int', '海拔', '基本信息', 'int', 'm');
            }
            // 装备清单 (jsonb)
            if ($p->gear_checklist && $p->gear_checklist !== '[]') {
                $push('gear_checklist', $p->gear_checklist, 'json', '装备清单', '装备', 'repeater');
            }
            // 安全提示 (jsonb)
            if ($p->safety_notes && $p->safety_notes !== '[]') {
                $push('safety_notes', $p->safety_notes, 'json', '安全提示', '安全', 'repeater');
            }
        }

        // 批量插入 (每 100 一批)
        foreach (array_chunk($rows, 100) as $chunk) {
            DB::table('place_attributes')->insert($chunk);
        }

        // 3. 删掉旧字段
        Schema::table('places', function (Blueprint $table) {
            $table->dropColumn([
                'has_parking', 'parking_fee_type', 'parking_fee', 'parking_notes', 'parking_capacity',
                'has_ticket', 'ticket_price', 'ticket_unit', 'ticket_notes',
                'best_season', 'suitable_for', 'recommended_duration_minutes',
                'difficulty', 'altitude_meters',
                'gear_checklist', 'safety_notes',
            ]);
        });
    }

    public function down(): void
    {
        // 加回字段
        Schema::table('places', function (Blueprint $table) {
            $table->boolean('has_parking')->default(false);
            $table->string('parking_fee_type', 20)->nullable();
            $table->decimal('parking_fee', 8, 2)->nullable();
            $table->text('parking_notes')->nullable();
            $table->unsignedInteger('parking_capacity')->nullable();
            $table->boolean('has_ticket')->default(false);
            $table->decimal('ticket_price', 8, 2)->nullable();
            $table->string('ticket_unit', 20)->nullable();
            $table->text('ticket_notes')->nullable();
            $table->string('best_season', 50)->nullable();
            $table->string('suitable_for', 100)->nullable();
            $table->unsignedInteger('recommended_duration_minutes')->nullable();
            $table->string('difficulty', 20)->nullable();
            $table->unsignedInteger('altitude_meters')->nullable();
            $table->jsonb('gear_checklist')->nullable();
            $table->jsonb('safety_notes')->nullable();
        });

        // 8 → 21 (反向)
        $reverseMap = [];
        foreach (self::TYPE_MAP as $old => $new) {
            $reverseMap[$new][] = $old;
        }
        foreach ($reverseMap as $new => $olds) {
            DB::table('places')
                ->where('place_type', $new)
                ->update(['place_type' => $olds[0]]);
        }

        DB::table('place_attributes')->truncate();
    }
};
