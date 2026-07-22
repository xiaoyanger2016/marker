<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * 数据迁移:
 *   1. 把现有 38 places 灌成 contents (type 来自 place_type)
 *   2. 把现有 6 routes 灌成 contents (type=self_drive/hiking)
 *   3. content_places pivot 自动建立 (places → 各自对应的 content)
 *   4. content_type_definitions 预填 8 大类
 *   5. content_self_drive / content_hiking / etc 子表灌入 type-specific 数据
 *
 * 之后:
 *   6. 删 places 表的 place_type / category_id / is_visited / is_wishlist / is_public / visit_count / visited_at / rating
 *   7. 删 place_attributes 表 (type-specific 数据已迁到 content_*)
 *   8. 删 routes 表 (合并进 contents)
 */
return new class extends Migration
{
    public function up(): void
    {
        $now = now();

        // ===== 1. 预填 content_type_definitions =====
        $typeDefs = [
            ['code' => 'self_drive',     'label' => '自驾线路', 'icon' => 'N°01', 'color' => '#114B5F', 'description' => '公路旅行的路径和途经点', 'place_binding' => 'multiple', 'subtable' => 'content_self_drive',     'sort' => 1],
            ['code' => 'play_water',     'label' => '玩水点',   'icon' => 'N°02', 'color' => '#0D3A4A', 'description' => '可下水游泳戏水的地点',     'place_binding' => 'single',   'subtable' => 'content_play_water',     'sort' => 2],
            ['code' => 'hiking',         'label' => '徒步线路', 'icon' => 'N°03', 'color' => '#2D5F3F', 'description' => '行走探索的路径',           'place_binding' => 'multiple', 'subtable' => 'content_hiking',         'sort' => 3],
            ['code' => 'paddle',         'label' => '桨板点',   'icon' => 'N°04', 'color' => '#0D5C5C', 'description' => '桨板 / SUP 适合的水域',     'place_binding' => 'single',   'subtable' => 'content_paddle',         'sort' => 4],
            ['code' => 'photo',          'label' => '拍照点',   'icon' => 'N°05', 'color' => '#A1461E', 'description' => '值得出片的取景地',         'place_binding' => 'single',   'subtable' => 'content_photo',          'sort' => 5],
            ['code' => 'food',           'label' => '美食探店', 'icon' => 'N°06', 'color' => '#C45626', 'description' => '值得专程去吃的店',         'place_binding' => 'single',   'subtable' => 'content_food',           'sort' => 6],
            ['code' => 'camping',        'label' => '露营点',   'icon' => 'N°07', 'color' => '#1A3A3A', 'description' => '可以过夜的营地',           'place_binding' => 'single',   'subtable' => 'content_camping',        'sort' => 7],
            ['code' => 'sunrise_sunset', 'label' => '日出日落', 'icon' => 'N°08', 'color' => '#7A4A1A', 'description' => '专门看日出日落的位置',     'place_binding' => 'single',   'subtable' => 'content_sunrise_sunset', 'sort' => 8],
        ];
        DB::table('content_type_definitions')->insert($typeDefs);

        // ===== 2. places → contents =====
        $places = DB::table('places')->get();
        $placeToContentMap = [];  // place_id => content_id (用于 content_places pivot)

        foreach ($places as $p) {
            $type = $p->place_type ?: 'photo';  // 没 type 的归到 photo (旧数据 fallback)

            $contentId = DB::table('contents')->insertGetId([
                'user_id'         => $p->user_id,
                'type'            => $type,
                'title'           => $p->name,
                'slug'            => $p->slug,
                'subtitle'        => null,
                'summary'         => null,
                'description'     => $p->description,
                'rating_label'    => $p->rating ? $this->ratingToLabel((int) $p->rating) : null,
                'visit_count'     => $p->visit_count ?? 0,
                'view_count'      => $p->view_count ?? 0,
                'is_visited'      => (bool) ($p->is_visited ?? false),
                'is_wishlist'     => (bool) ($p->is_wishlist ?? false),
                'is_public'       => (bool) ($p->is_public ?? true),
                'published_at'    => $p->created_at,
                'visited_at'      => $p->visited_at,
                'created_at'      => $p->created_at,
                'updated_at'      => $p->updated_at ?? $p->created_at,
            ]);
            $placeToContentMap[$p->id] = $contentId;

            // content_places pivot (sequence=0 单地点)
            DB::table('content_places')->insert([
                'content_id' => $contentId,
                'place_id'   => $p->id,
                'sequence'   => 0,
                'notes'      => null,
                'created_at' => $now,
                'updated_at' => $now,
            ]);

            // 灌子表
            $this->insertSubTable($contentId, $type, $p);
        }

        // ===== 3. routes → contents (type=self_drive/hiking) =====
        $routes = DB::table('routes')->get();
        foreach ($routes as $r) {
            $type = $r->type === 'hiking' ? 'hiking' : 'self_drive';

            $contentId = DB::table('contents')->insertGetId([
                'user_id'         => $r->user_id,
                'cover_media_id'  => $r->cover_media_id,
                'type'            => $type,
                'title'           => $r->name,
                'slug'            => $r->slug,
                'subtitle'        => $r->subtitle,
                'summary'         => $r->summary,
                'description'     => $r->description,
                'rating_label'    => $r->rating_label,
                'visit_count'     => $r->visit_count ?? 0,
                'view_count'      => $r->view_count ?? 0,
                'is_visited'      => false,
                'is_wishlist'     => false,
                'is_public'       => true,
                'published_at'    => $r->created_at,
                'created_at'      => $r->created_at,
                'updated_at'      => $r->updated_at ?? $r->created_at,
            ]);

            // route_place pivot → content_places (按 order)
            $routePlaces = DB::table('route_place')
                ->where('route_id', $r->id)
                ->orderBy('order')
                ->get();
            foreach ($routePlaces as $rp) {
                DB::table('content_places')->insert([
                    'content_id' => $contentId,
                    'place_id'   => $rp->place_id,
                    'sequence'   => $rp->order,
                    'notes'      => $rp->notes ?? null,
                    'created_at' => $now,
                    'updated_at' => $now,
                ]);
            }

            // 子表
            $this->insertRouteSubTable($contentId, $type, $r);
        }

        // ===== 4. 旧 place_attributes 灌子表 (按 attribute_key 映射) =====
        // 已经从 place_attributes 拉到 place.id 灌过, 上面 places loop 里就处理了。
        // 但因为 places loop 用的是 $p (来自 places 表, 没 attribute)
        // 这里要单独再走一次 place_attributes 把 attributes 灌子表
        $attrs = DB::table('place_attributes')->get();
        foreach ($attrs as $a) {
            $contentId = $placeToContentMap[$a->place_id] ?? null;
            if (! $contentId) continue;
            $type = DB::table('contents')->where('id', $contentId)->value('type');

            $this->mergeAttribute($contentId, $type, $a);
        }
    }

    public function down(): void
    {
        // 复杂回滚不在范围 (这是单向重构)
    }

    /**
     * 把 1-5 数字 rating 映射到 5 档 label
     */
    private function ratingToLabel(int $rating): ?string
    {
        return match ($rating) {
            1 => 'terrible',
            2 => 'npc',
            3 => 'nice',
            4 => 'great',
            5 => 'legendary',
            default => null,
        };
    }

    /**
     * 从 place 行直接灌子表 (只灌 place 行里有的字段)
     */
    private function insertSubTable(int $contentId, string $type, object $p): void
    {
        $subtableMap = [
            'self_drive'     => 'content_self_drive',
            'hiking'         => 'content_hiking',
            'play_water'     => 'content_play_water',
            'paddle'         => 'content_paddle',
            'photo'          => 'content_photo',
            'food'           => 'content_food',
            'camping'        => 'content_camping',
            'sunrise_sunset' => 'content_sunrise_sunset',
        ];
        $table = $subtableMap[$type] ?? null;
        if (! $table) return;

        $row = ['content_id' => $contentId];

        // places 表的通用字段 (name/phone/website/business_hours/price_range/...)
        // 跟子表字段有部分重合, 这里先灌子表自己的字段
        if ($type === 'play_water' || $type === 'camping') {
            $row['is_free'] = (bool) ($p->is_free ?? false);
        }
        if ($type === 'play_water') {
            $row['parking'] = $p->parking ?? null;
            $row['ticket']  = $p->ticket ?? null;
        }
        if ($type === 'food') {
            $row['price_per_person'] = $p->price_range;
            $row['business_hours']   = $p->business_hours;
        }
        if ($type === 'camping') {
            $row['parking'] = $p->parking ?? null;
        }
        if ($type === 'photo') {
            $row['parking'] = $p->parking ?? null;
        }
        if ($type === 'sunrise_sunset') {
            $row['parking'] = $p->parking ?? null;
        }
        if ($type === 'hiking' || $type === 'self_drive') {
            $row['distance_km']      = $p->distance_km;
            $row['duration_minutes'] = $p->duration_minutes;
            $row['altitude_meters']  = $p->altitude_meters;
            $row['difficulty']       = $p->difficulty;
        }
        if ($type === 'hiking') {
            $row['elevation_gain'] = $p->elevation_gain;
        }

        DB::table($table)->insert($row);
    }

    /**
     * 从 place_attributes 灌子表 (key-value 形式)
     */
    private function mergeAttribute(int $contentId, string $type, object $a): void
    {
        $subtableMap = [
            'self_drive'     => 'content_self_drive',
            'hiking'         => 'content_hiking',
            'play_water'     => 'content_play_water',
            'paddle'         => 'content_paddle',
            'photo'          => 'content_photo',
            'food'           => 'content_food',
            'camping'        => 'content_camping',
            'sunrise_sunset' => 'content_sunrise_sunset',
        ];
        $table = $subtableMap[$type] ?? null;
        if (! $table) return;

        // 已经有值就不覆盖
        $existing = DB::table($table)->where('content_id', $contentId)->first();
        if (! $existing) return;

        $col = $this->mapAttributeToColumn($a->attribute_key, $type);
        if (! $col) return;

        $value = $a->attribute_value;
        // json/array 字段需要反序列化
        if (in_array($a->value_type, ['json', 'array'], true)) {
            $value = $a->attribute_value;  // 已经是 JSON 字符串, 直接存
        }
        // best_season 之类如果是 string 编码 (CSV / JSON), 转成 jsonb
        if (in_array($col, ['best_season', 'gear_checklist', 'safety_notes', 'waypoints', 'gas_stations', 'signature_dishes'], true)) {
            if ($a->value_type === 'string') {
                // 旧数据是 CSV 形式 'spring,summer' 之类的，转 jsonb
                $value = json_encode(array_filter(array_map('trim', explode(',', (string) $value))), JSON_UNESCAPED_UNICODE);
            } elseif ($a->value_type === 'json' || $a->value_type === 'array') {
                $decoded = json_decode((string) $value, true);
                $value = is_array($decoded) ? json_encode($decoded, JSON_UNESCAPED_UNICODE) : $value;
            }
        }
        // 数字字段
        if (in_array($col, ['distance_km', 'duration_minutes', 'altitude_meters', 'elevation_gain', 'viewpoint_count'], true)) {
            $value = is_numeric($value) ? (float) $value : null;
        }
        // 布尔字段
        if (in_array($col, ['is_free', 'is_swimmable', 'has_lifeguard', 'has_water', 'has_toilet', 'fire_allowed', 'has_signal', 'rental_available', 'is_drone_allowed', 'permit_required'], true)) {
            $value = (bool) $value;
        }

        DB::table($table)->where('content_id', $contentId)->update([$col => $value]);
    }

    /**
     * 把 place_attributes.attribute_key 映射到对应子表字段
     */
    private function mapAttributeToColumn(string $key, string $type): ?string
    {
        $map = [
            'self_drive' => [
                'distance_km' => 'distance_km',
                'duration_minutes' => 'duration_minutes',
                'altitude_meters' => 'altitude_meters',
                'difficulty' => 'difficulty',
                'road_condition' => 'road_condition',
                'best_season' => 'best_season',
                'gas_stations' => 'gas_stations',
                'waypoints' => 'waypoints',
                'gear_checklist' => 'gear_checklist',
                'safety_notes' => 'safety_notes',
            ],
            'hiking' => [
                'distance_km' => 'distance_km',
                'duration_minutes' => 'duration_minutes',
                'altitude_meters' => 'altitude_meters',
                'elevation_gain' => 'elevation_gain',
                'difficulty' => 'difficulty',
                'route_type' => 'route_type',
                'best_season' => 'best_season',
                'waypoints' => 'waypoints',
                'gear_checklist' => 'gear_checklist',
                'safety_notes' => 'safety_notes',
            ],
            'play_water' => [
                'water_type' => 'water_type',
                'water_depth' => 'water_depth',
                'is_swimmable' => 'is_swimmable',
                'is_free' => 'is_free',
                'parking' => 'parking',
                'ticket' => 'ticket',
                'has_lifeguard' => 'has_lifeguard',
                'best_season' => 'best_season',
                'gear_checklist' => 'gear_checklist',
                'safety_notes' => 'safety_notes',
            ],
            'paddle' => [
                'water_depth' => 'water_depth',
                'water_current' => 'water_current',
                'difficulty' => 'difficulty',
                'rental_available' => 'rental_available',
                'best_time' => 'best_time',
                'gear_checklist' => 'gear_checklist',
                'safety_notes' => 'safety_notes',
            ],
            'photo' => [
                'best_time' => 'best_time',
                'best_light' => 'best_light',
                'viewpoint_count' => 'viewpoint_count',
                'is_drone_allowed' => 'is_drone_allowed',
                'permit_required' => 'permit_required',
                'parking' => 'parking',
                'best_season' => 'best_season',
                'gear_checklist' => 'gear_checklist',
            ],
            'food' => [
                'price_per_person' => 'price_per_person',
                'cuisine_type' => 'cuisine_type',
                'business_hours' => 'business_hours',
                'signature_dishes' => 'signature_dishes',
                'reservation' => 'reservation',
                'parking' => 'parking',
                'contact' => 'contact',
            ],
            'camping' => [
                'altitude_meters' => 'altitude_meters',
                'is_free' => 'is_free',
                'has_water' => 'has_water',
                'has_toilet' => 'has_toilet',
                'fire_allowed' => 'fire_allowed',
                'has_signal' => 'has_signal',
                'parking' => 'parking',
                'best_season' => 'best_season',
                'gear_checklist' => 'gear_checklist',
                'safety_notes' => 'safety_notes',
            ],
            'sunrise_sunset' => [
                'direction' => 'direction',
                'best_time' => 'best_time',
                'viewpoint_count' => 'viewpoint_count',
                'difficulty' => 'difficulty',
                'is_drone_allowed' => 'is_drone_allowed',
                'parking' => 'parking',
                'best_season' => 'best_season',
                'gear_checklist' => 'gear_checklist',
                'safety_notes' => 'safety_notes',
            ],
        ];
        return $map[$type][$key] ?? null;
    }

    /**
     * 灌 route → content_self_drive / content_hiking
     */
    private function insertRouteSubTable(int $contentId, string $type, object $r): void
    {
        $table = $type === 'hiking' ? 'content_hiking' : 'content_self_drive';
        // routes 表字段: distance_km / duration_hours / difficulty / best_season(CSV) / gear_checklist(jsonb) / safety_notes(jsonb)
        // 转换 best_season CSV → jsonb
        $bestSeason = $r->best_season ?? null;
        if ($bestSeason && is_string($bestSeason) && ! str_starts_with($bestSeason, '[')) {
            $bestSeason = json_encode(array_filter(array_map('trim', explode(',', $bestSeason))), JSON_UNESCAPED_UNICODE);
        } elseif ($bestSeason && is_string($bestSeason)) {
            $bestSeason = $bestSeason;  // 已经是 JSON
        }

        $row = [
            'content_id'        => $contentId,
            'distance_km'       => $r->distance_km,
            'duration_minutes'  => $r->duration_hours ? ((int) $r->duration_hours * 60) : null,
            'altitude_meters'   => null,  // routes 表没这字段
            'difficulty'        => $r->difficulty,
            'best_season'       => $bestSeason,
            'gear_checklist'    => $r->gear_checklist,
            'safety_notes'      => $r->safety_notes,
            'waypoints'         => $r->start_point && $r->end_point ? json_encode([
                ['name' => $r->start_point, 'role' => 'start'],
                ['name' => $r->end_point, 'role' => 'end'],
            ], JSON_UNESCAPED_UNICODE) : null,
        ];
        if ($type === 'hiking') {
            $row['elevation_gain'] = null;  // routes 表没这字段
            $row['route_type']     = 'one_way';  // 默认
        } else {
            // self_drive 才有 gas_stations
            $row['gas_stations']   = null;
        }
        DB::table($table)->insert($row);
    }
};
