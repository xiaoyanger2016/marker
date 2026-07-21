<?php

namespace App\Http\Controllers\Frontend;

use App\Http\Controllers\Controller;
use App\Models\Place;
use App\Models\Route as TravelRoute;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class HomeController extends Controller
{
    /**
     * 6 个资料类型（按用户需求固定）
     */
    public const TYPES = [
        [
            'key' => 'self_drive',
            'kind' => 'route',
            'label' => '自驾线路',
            'icon' => '🚗',
            'color' => '#ef4444',
            'gradient' => 'from-red-500 to-orange-500',
            'desc' => '多地点串联，按顺序走',
        ],
        [
            'key' => 'play_water',
            'kind' => 'place',
            'place_type' => 'play_water',
            'label' => '玩水点',
            'icon' => '🏊',
            'color' => '#06b6d4',
            'gradient' => 'from-cyan-500 to-blue-500',
            'desc' => '溯溪、漂流、玩水',
        ],
        [
            'key' => 'hiking',
            'kind' => 'route',
            'label' => '徒步线路',
            'icon' => '🥾',
            'color' => '#10b981',
            'gradient' => 'from-emerald-500 to-teal-500',
            'desc' => '单条线路，无序串联',
        ],
        [
            'key' => 'sup',
            'kind' => 'place',
            'place_type' => 'play_water', // 暂复用，可加 sup 类型
            'label' => '桨板点',
            'icon' => '🏄',
            'color' => '#3b82f6',
            'gradient' => 'from-blue-500 to-indigo-500',
            'desc' => 'SUP/桨板水域',
        ],
        [
            'key' => 'photo',
            'kind' => 'place',
            'place_type' => 'viewpoint',
            'label' => '拍照点',
            'icon' => '📸',
            'color' => '#ec4899',
            'gradient' => 'from-pink-500 to-rose-500',
            'desc' => '出片率高的机位',
        ],
        [
            'key' => 'food',
            'kind' => 'place',
            'place_type' => 'restaurant',
            'label' => '美食探店',
            'icon' => '🍔',
            'color' => '#f59e0b',
            'gradient' => 'from-amber-500 to-orange-500',
            'desc' => '本地人才知道的店',
        ],
    ];

    public function home()
    {
        // 推荐内容：每种类型取热度 top 3
        $recommendations = [];
        foreach (self::TYPES as $type) {
            $items = $this->getItemsByType($type, 3);
            if ($items->isNotEmpty()) {
                $recommendations[] = [
                    'type' => $type,
                    'items' => $items,
                ];
            }
        }

        // 全站热度榜
        $hotRoutes = TravelRoute::public()
            ->orderedByHeat('desc')
            ->limit(6)
            ->get()
            ->map(fn ($r) => $this->presentRoute($r));

        $hotPlaces = Place::public()
            ->with(['category', 'media'])
            ->withCount('media')
            ->orderBy('created_at', 'desc')
            ->limit(12)
            ->get()
            ->map(fn ($p) => $this->presentPlace($p));

        return view('frontend.home', [
            'types' => self::TYPES,
            'recommendations' => $recommendations,
            'hotRoutes' => $hotRoutes,
            'hotPlaces' => $hotPlaces,
        ]);
    }

    public function type(string $key)
    {
        $type = collect(self::TYPES)->firstWhere('key', $key);
        if (! $type) {
            abort(404);
        }

        $items = $this->getItemsByType($type, 30);

        return view('frontend.type', [
            'type' => $type,
            'items' => $items,
        ]);
    }

    public function place(int $id)
    {
        $place = Place::with(['category', 'tags', 'media', 'notes', 'user'])
            ->where('is_public', true)
            ->findOrFail($id);

        $place->increment('view_count');

        return view('frontend.place', [
            'place' => $place,
            'cover' => $place->media->firstWhere('is_cover', true) ?? $place->media->first(),
            'gallery' => $place->media->where('type', 'image'),
            'videos' => $place->media->where('type', 'video'),
        ]);
    }

    public function routeShow(int $id)
    {
        $route = TravelRoute::with(['user', 'places' => function ($q) {
            $q->with(['category', 'media'])->orderBy('route_place.order');
        }, 'media'])
            ->where('is_public', true)
            ->findOrFail($id);

        $route->increment('view_count');

        return view('frontend.route_show', [
            'route' => $route,
            'type' => TravelRoute::TYPES[$route->type] ?? null,
            'rating' => TravelRoute::RATING_LABELS[$route->rating_label] ?? null,
        ]);
    }

    public function map()
    {
        $places = Place::public()
            ->select(['id', 'name', 'latitude', 'longitude', 'category_id', 'city', 'place_type', 'rating', 'rating_label', 'is_wishlist'])
            ->with(['category', 'media' => fn ($q) => $q->where('is_cover', true)])
            ->get();

        return view('frontend.map', ['places' => $places]);
    }

    public function radar(Request $request)
    {
        return view('frontend.radar');
    }

    // ---- helpers ----
    protected function getItemsByType(array $type, int $limit = 10)
    {
        if ($type['kind'] === 'route') {
            return TravelRoute::public()
                ->ofType($type['key'])
                ->orderedByHeat('desc')
                ->limit($limit)
                ->get()
                ->map(fn ($r) => $this->presentRoute($r));
        }

        // place type
        $query = Place::public()
            ->with(['category', 'media' => fn ($q) => $q->where('is_cover', true)])
            ->withCount('media');

        if (! empty($type['place_type'])) {
            $query->where('place_type', $type['place_type']);
        }

        return $query
            ->orderBy('created_at', 'desc')
            ->limit($limit)
            ->get()
            ->map(fn ($p) => $this->presentPlace($p));
    }

    protected function presentPlace(Place $p): array
    {
        $cover = $p->media->firstWhere('is_cover', true) ?? $p->media->first();
        return [
            'kind' => 'place',
            'id' => $p->id,
            'name' => $p->name,
            'summary' => $p->description ? mb_substr(strip_tags($p->description), 0, 80) : '',
            'cover' => $cover?->url ?? url('/images/placeholder.png'),
            'rating' => $p->rating,
            'rating_label' => $p->rating_label,
            'rating_label_text' => $p->rating_label ? (Place::RATING_LABELS[$p->rating_label]['label'] ?? null) : null,
            'city' => $p->city,
            'place_type' => $p->place_type,
            'place_type_label' => $p->place_type ? (Place::PLACE_TYPES[$p->place_type]['label'] ?? null) : null,
            'place_type_icon' => $p->place_type ? (Place::PLACE_TYPES[$p->place_type]['icon'] ?? null) : null,
            'is_wishlist' => $p->is_wishlist,
            'url' => url("/place/{$p->id}"),
        ];
    }

    protected function presentRoute(TravelRoute $r): array
    {
        $cover = $r->media?->firstWhere('is_cover', true) ?? $r->media?->first();
        return [
            'kind' => 'route',
            'id' => $r->id,
            'type' => $r->type,
            'type_label' => $r->typeMeta()['label'],
            'type_icon' => $r->typeMeta()['icon'],
            'type_color' => $r->typeMeta()['color'],
            'name' => $r->name,
            'subtitle' => $r->subtitle,
            'summary' => $r->summary,
            'cover' => $cover?->url ?? url('/images/placeholder.png'),
            'rating_label' => $r->rating_label,
            'rating_meta' => $r->ratingMeta(),
            'city' => $r->city,
            'distance_km' => $r->distance_km,
            'duration_hours' => $r->duration_hours,
            'places_count' => $r->places_count ?? 0,
            'view_count' => $r->view_count,
            'like_count' => $r->like_count,
            'url' => url("/route/{$r->id}"),
        ];
    }
}
