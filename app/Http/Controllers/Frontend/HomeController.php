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
            'icon' => 'N°01',
            'color' => '#114B5F',
            'gradient' => 'from-red-500 to-orange-500',
            'desc' => '多地点串联，按顺序走',
        ],
        [
            'key' => 'play_water',
            'kind' => 'place',
            'place_type' => 'play_water',
            'label' => '玩水点',
            'icon' => 'N°02',
            'color' => '#0D3A4A',
            'gradient' => 'from-cyan-500 to-blue-500',
            'desc' => '溯溪、漂流、玩水',
        ],
        [
            'key' => 'hiking',
            'kind' => 'route',
            'label' => '徒步线路',
            'icon' => 'N°03',
            'color' => '#2D5F3F',
            'gradient' => 'from-emerald-500 to-teal-500',
            'desc' => '单条线路，无序串联',
        ],
        [
            'key' => 'sup',
            'kind' => 'place',
            'place_type' => 'play_water',
            'label' => '桨板点',
            'icon' => 'N°04',
            'color' => '#114B5F',
            'gradient' => 'from-blue-500 to-indigo-500',
            'desc' => 'SUP/桨板水域',
        ],
        [
            'key' => 'photo',
            'kind' => 'place',
            'place_type' => 'viewpoint',
            'label' => '拍照点',
            'icon' => 'N°05',
            'color' => '#847E72',
            'gradient' => 'from-pink-500 to-rose-500',
            'desc' => '出片率高的机位',
        ],
        [
            'key' => 'food',
            'kind' => 'place',
            'place_type' => 'restaurant',
            'label' => '美食探店',
            'icon' => 'N°06',
            'color' => '#C45626',
            'gradient' => 'from-amber-500 to-orange-500',
            'desc' => '本地人才知道的店',
        ],
        [
            'key' => 'camping',
            'kind' => 'place',
            'place_type' => 'camping',
            'label' => '露营点',
            'icon' => 'N°07',
            'color' => '#1A3A3A',
            'gradient' => 'from-green-500 to-emerald-500',
            'desc' => '帐篷、星空、夜晚',
        ],
        [
            'key' => 'sunrise_sunset',
            'kind' => 'place',
            'place_type' => 'viewpoint',
            'label' => '日出日落',
            'icon' => 'N°08',
            'color' => '#A1461E',
            'gradient' => 'from-orange-400 to-rose-400',
            'desc' => '日出/日落机位',
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

        $activities = \App\Models\Activity::where('is_public', true)
            ->where('place_id', $id)
            ->whereIn('status', ['open', 'full'])
            ->where('start_at', '>=', now())
            ->withCount('joinedParticipants')
            ->orderBy('start_at')
            ->limit(5)
            ->get();

        return view('frontend.place', [
            'place' => $place,
            'cover' => $place->media->firstWhere('is_cover', true) ?? $place->media->first(),
            'gallery' => $place->media->where('type', 'image'),
            'videos' => $place->media->where('type', 'video'),
            'activities' => $activities,
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

        $activities = \App\Models\Activity::where('is_public', true)
            ->where('route_id', $id)
            ->whereIn('status', ['open', 'full'])
            ->where('start_at', '>=', now())
            ->withCount('joinedParticipants')
            ->orderBy('start_at')
            ->limit(5)
            ->get();

        return view('frontend.route_show', [
            'route' => $route,
            'type' => TravelRoute::TYPES[$route->type] ?? null,
            'rating' => TravelRoute::RATING_LABELS[$route->rating_label] ?? null,
            'activities' => $activities,
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

    /**
     * 「我的」主页
     */
    public function me(Request $request)
    {
        $user = auth()->user();
        if (! $user) {
            return redirect('/login');
        }

        $stats = [
            'places_total' => $user->places()->count(),
            'places_wishlist' => $user->places()->where('is_wishlist', true)->count(),
            'places_visited' => $user->places()->where('is_visited', true)->count(),
            'routes_total' => $user->routes()->count(),
            'collections_total' => $user->collections()->count(),
            'notes_total' => $user->notes()->count(),
        ];

        $recentPlaces = $user->places()->latest()->limit(6)->get();
        $recentRoutes = $user->routes()->latest()->limit(3)->get();
        $collections = $user->collections()->withCount('places')->latest()->limit(5)->get();

        return view('frontend.me', [
            'user' => $user,
            'stats' => $stats,
            'recentPlaces' => $recentPlaces,
            'recentRoutes' => $recentRoutes,
            'collections' => $collections,
        ]);
    }

    public function myPlaces(Request $request)
    {
        $user = auth()->user();
        if (! $user) {
            return redirect('/login');
        }

        $filter = $request->query('filter', 'all');
        $query = $user->places()->with(['category', 'media' => fn ($q) => $q->where('is_cover', true)]);
        if ($filter === 'wishlist') {
            $query->where('is_wishlist', true);
        } elseif ($filter === 'visited') {
            $query->where('is_visited', true);
        } elseif ($filter === 'unpublic') {
            $query->where('is_public', false);
        }
        $places = $query->latest()->paginate(20);

        return view('frontend.my_places', ['places' => $places, 'filter' => $filter]);
    }

    public function myRoutes(Request $request)
    {
        $user = auth()->user();
        if (! $user) {
            return redirect('/login');
        }
        $routes = $user->routes()->withCount('places')->latest()->paginate(20);
        return view('frontend.my_routes', ['routes' => $routes]);
    }

    public function myCollections(Request $request)
    {
        $user = auth()->user();
        if (! $user) {
            return redirect('/login');
        }
        $collections = $user->collections()->withCount('places')->latest()->get();
        return view('frontend.my_collections', ['collections' => $collections]);
    }

    public function myActivities(Request $request)
    {
        $user = auth()->user();
        if (! $user) {
            return redirect('/login');
        }
        $myCreated = collect();
        $myJoined = collect();
        if (class_exists(\App\Models\Activity::class)) {
            $myCreated = \App\Models\Activity::where('user_id', $user->id)
                ->withCount('participants')
                ->with('place', 'route')
                ->latest()->get();
            $myJoined = \App\Models\ActivityParticipant::where('user_id', $user->id)
                ->with('activity')
                ->latest()->get();
        }
        return view('frontend.my_activities', compact('myCreated', 'myJoined'));
    }

    // ====== 活动 ======

    public function activities(Request $request)
    {
        $filters = array_filter([
            'upcoming' => $request->query('upcoming', 1),
            'region_code' => $request->query('region'),
        ]);
        $page = \App\Models\Activity::where('is_public', true)
            ->whereIn('status', ['open', 'full'])
            ->when(! empty($filters['upcoming']), fn ($q) => $q->where('start_at', '>=', now()))
            ->when(! empty($filters['region_code']), fn ($q) => $q->where('region_code', $filters['region_code']))
            ->with(['user', 'place', 'route'])
            ->withCount('joinedParticipants')
            ->latest('start_at')
            ->paginate(20);

        return view('frontend.activities_index', [
            'activities' => $page,
            'regionCode' => $filters['region_code'] ?? null,
        ]);
    }

    public function activityCreate(Request $request)
    {
        if (! auth()->check()) {
            return redirect('/login');
        }
        $placeId = $request->query('place_id');
        $routeId = $request->query('route_id');
        $preset = [
            'place' => null,
            'route' => null,
        ];
        if ($placeId) {
            $preset['place'] = Place::public()->find($placeId);
        }
        if ($routeId) {
            $preset['route'] = TravelRoute::public()->find($routeId);
        }
        return view('frontend.activity_create', $preset);
    }

    public function activityStore(Request $request)
    {
        if (! auth()->check()) {
            return redirect('/login');
        }
        $data = $request->validate([
            'title' => 'required|string|max:200',
            'description' => 'nullable|string|max:5000',
            'start_at' => 'required|date',
            'end_at' => 'nullable|date|after:start_at',
            'signup_deadline' => 'nullable|date|before:start_at',
            'meeting_point' => 'nullable|string|max:200',
            'latitude' => 'nullable|numeric|between:-90,90',
            'longitude' => 'nullable|numeric|between:-180,180',
            'max_participants' => 'nullable|integer|min:0',
            'transport' => 'nullable|string|max:50',
            'fee' => 'nullable|numeric|min:0',
            'fee_includes' => 'nullable|string|max:500',
            'fee_excludes' => 'nullable|string|max:500',
            'region_code' => 'nullable|string|max:20',
            'region_name' => 'nullable|string|max:50',
            'place_id' => 'nullable|integer|exists:places,id',
            'route_id' => 'nullable|integer|exists:routes,id',
        ]);
        $data['user_id'] = auth()->id();
        $data['status'] = 'open';
        \App\Models\Activity::create($data);
        return redirect('/activities')->with('ok', '活动已发布');
    }

    public function activityShow(int $id)
    {
        $activity = \App\Models\Activity::with(['user', 'place', 'route'])
            ->withCount('joinedParticipants')
            ->findOrFail($id);
        $participants = $activity->participants()
            ->whereIn('status', ['joined', 'pending'])
            ->with('user:id,name,avatar')
            ->get();
        $activity->increment('view_count');
        $isJoined = auth()->check() && $activity->participants()
            ->where('user_id', auth()->id())
            ->whereIn('status', ['joined', 'pending'])
            ->exists();
        return view('frontend.activity_show', [
            'activity' => $activity,
            'participants' => $participants,
            'isJoined' => $isJoined,
        ]);
    }

    public function activityJoin(Request $request, int $id)
    {
        if (! auth()->check()) {
            return redirect('/login');
        }
        $activity = \App\Models\Activity::findOrFail($id);
        if ($activity->is_expired) {
            return back()->with('error', '活动已截止/取消');
        }
        $people = max(1, (int) $request->input('people_count', 1));
        \App\Models\ActivityParticipant::updateOrCreate(
            ['activity_id' => $id, 'user_id' => auth()->id()],
            ['status' => 'joined', 'people_count' => $people]
        );
        return back()->with('ok', '报名成功');
    }

    public function activityLeave(Request $request, int $id)
    {
        if (! auth()->check()) {
            return redirect('/login');
        }
        \App\Models\ActivityParticipant::where('activity_id', $id)
            ->where('user_id', auth()->id())
            ->update(['status' => 'cancelled']);
        return back()->with('ok', '已取消报名');
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
