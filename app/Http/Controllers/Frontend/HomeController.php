<?php

namespace App\Http\Controllers\Frontend;

use App\Http\Controllers\Controller;
use App\Models\Activity;
use App\Models\Comment;
use App\Models\Content;
use App\Models\Place;
use Illuminate\Http\Request;

class HomeController extends Controller
{
    /**
     * 8 大类内容类型 (与 Content::TYPES / content_type_definitions 表同源)
     * 用于前端 8 个类型菜单 + 搜索筛选
     * 标签走 __() 翻译，5 语种自动切换
     */
    public const TYPES = [
        ['key' => 'self_drive',     'label_key' => 'ui.type_self_drive',     'icon' => 'N°01', 'color' => '#114B5F', 'desc_key' => 'ui.type_desc_self_drive',     'en_key' => 'ui.type_en_self_drive'],
        ['key' => 'play_water',     'label_key' => 'ui.type_play_water',     'icon' => 'N°02', 'color' => '#0D3A4A', 'desc_key' => 'ui.type_desc_play_water',     'en_key' => 'ui.type_en_play_water'],
        ['key' => 'hiking',         'label_key' => 'ui.type_hiking',         'icon' => 'N°03', 'color' => '#2D5F3F', 'desc_key' => 'ui.type_desc_hiking',         'en_key' => 'ui.type_en_hiking'],
        ['key' => 'paddle',         'label_key' => 'ui.type_paddle',         'icon' => 'N°04', 'color' => '#0D5C5C', 'desc_key' => 'ui.type_desc_paddle',         'en_key' => 'ui.type_en_paddle'],
        ['key' => 'photo',          'label_key' => 'ui.type_photo',          'icon' => 'N°05', 'color' => '#A1461E', 'desc_key' => 'ui.type_desc_photo',          'en_key' => 'ui.type_en_photo'],
        ['key' => 'food',           'label_key' => 'ui.type_food',           'icon' => 'N°06', 'color' => '#C45626', 'desc_key' => 'ui.type_desc_food',           'en_key' => 'ui.type_en_food'],
        ['key' => 'camping',        'label_key' => 'ui.type_camping',        'icon' => 'N°07', 'color' => '#1A3A3A', 'desc_key' => 'ui.type_desc_camping',        'en_key' => 'ui.type_en_camping'],
        ['key' => 'sunrise_sunset', 'label_key' => 'ui.type_sunrise_sunset', 'icon' => 'N°08', 'color' => '#7A4A1A', 'desc_key' => 'ui.type_desc_sunrise_sunset', 'en_key' => 'ui.type_en_sunrise_sunset'],
    ];

    public function home(Request $request)
    {
        // 翻译 8 大类标签 (5 语种)
        $types = collect(self::TYPES)->map(function ($t) {
            $t['label'] = __($t['label_key']);
            $t['desc'] = __($t['desc_key']);
            $t['en'] = __($t['en_key']);
            return $t;
        })->all();

        // Phase 18 · Bug 1: "§ 02 — 本期精选"
        //  1. 先看 content_picks 表 (admin 人工 set, 按 sort 排)
        //  2. 没有任何 pick → 随机取 10 条 (限制 is_public)
        $picks = Content::public()
            ->with(['user', 'coverMedia', 'gallery', 'places', 'pick'])
            ->whereHas('pick')
            ->orderBy(
                \App\Models\ContentPick::select('sort')
                    ->whereColumn('content_picks.content_id', 'contents.id')
                    ->orderBy('sort')
                    ->limit(1)
            )
            ->get()
            ->map(fn ($c) => $this->presentContent($c));

        if ($picks->isEmpty()) {
            // fallback 随机 10
            $picks = Content::public()
                ->with(['user', 'coverMedia', 'gallery', 'places'])
                ->inRandomOrder()
                ->limit(10)
                ->get()
                ->map(fn ($c) => $this->presentContent($c));
        }
        $picksRandom = $picks->isEmpty() && Content::public()->count() === 0; // mark "random" for view badge

        // ALL FEED (server-rendered 30 条) — 支持 ?feed=type key 过滤
        $feedType = $request->query('feed');
        $feedQuery = Content::public()->with(['user', 'coverMedia', 'gallery', 'places']);
        if ($feedType && in_array($feedType, array_column($types, 'key'), true)) {
            $feedQuery->where('type', $feedType);
        }
        $feedItems = $feedQuery
            ->orderByDesc('view_count')
            ->limit(30)
            ->get()
            ->map(fn ($c) => $this->presentContent($c));

        // 全站热度榜
        $hotContents = Content::public()
            ->with(['user', 'coverMedia', 'gallery', 'places'])
            ->orderByDesc('view_count')
            ->limit(12)
            ->get()
            ->map(fn ($c) => $this->presentContent($c));

        $recentPlaces = Place::with('media')
            ->orderBy('created_at', 'desc')
            ->limit(12)
            ->get()
            ->map(fn ($p) => $this->presentPlace($p));

        return view('frontend.home', [
            'types' => $types,
            'picks' => $picks,
            'picksRandom' => $picksRandom, // true=fallback 随机, false=admin 手动
            'feedItems' => $feedItems,
            'feedType' => $feedType,
            'hotContents' => $hotContents,
            'recentPlaces' => $recentPlaces,
        ]);
    }

    /**
     * Phase 18 · Bug 1: "查看更多" 页面
     *  显示全部 picks (有 picked 的) 或全部内容 (fallback 情况)
     */
    public function picks(Request $request)
    {
        $picks = Content::public()
            ->with(['user', 'coverMedia', 'gallery', 'places', 'pick'])
            ->whereHas('pick')
            ->orderBy(
                \App\Models\ContentPick::select('sort')
                    ->whereColumn('content_picks.content_id', 'contents.id')
                    ->orderBy('sort')
                    ->limit(1)
            )
            ->paginate(30);

        return view('frontend.picks', [
            'picks' => $picks,
            'isPickedMode' => true,
        ]);
    }

    public function type(string $key)
    {
        $typeRaw = collect(self::TYPES)->firstWhere('key', $key);
        if (! $typeRaw) {
            abort(404);
        }
        $type = $typeRaw;
        $type['label'] = __($type['label_key']);
        $type['desc'] = __($type['desc_key']);
        $type['en'] = __($type['en_key']);

        $items = $this->getItemsByType($key, 30);

        return view('frontend.type', [
            'type' => $type,
            'items' => $items,
        ]);
    }

    public function place(int $id)
    {
        $place = Place::with(['tags', 'media', 'notes', 'user', 'contents'])
            ->findOrFail($id);

        $activities = Activity::where('is_public', true)
            ->where(function ($q) use ($id, $place) {
                $q->where('place_id', $id)
                  ->orWhereIn('content_id', $place->contents->pluck('id'));
            })
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

    /**
     * 内容贴详情 — 8 大类
     */
    public function contentShow(int $id)
    {
        $content = Content::with([
            'user',
            'coverMedia',
            'gallery', 'videos',
            'places', 'tags',
            'publicComments.user',
            'publicComments.media',
        ])
            ->public()
            ->findOrFail($id);

        $content->increment('view_count');

        $activities = Activity::where('is_public', true)
            ->where('content_id', $id)
            ->whereIn('status', ['open', 'full'])
            ->where('start_at', '>=', now())
            ->withCount('joinedParticipants')
            ->orderBy('start_at')
            ->limit(5)
            ->get();

        $userVote = auth()->check() ? $content->userVote(auth()->user()) : null;

        return view('frontend.content_show', [
            'content' => $content,
            'cover' => $content->coverMedia ?: $content->gallery->first(),
            'gallery' => $content->gallery,
            'videos' => $content->videos,
            'type' => $content->typeMeta(),
            'rating' => $content->ratingMeta(),
            'vote_count' => $content->vote_count,
            'vote_avg' => $content->vote_avg,
            'vote_distribution' => $content->vote_distribution,
            'user_vote' => $userVote,
            'activities' => $activities,
        ]);
    }

    public function map(Request $request)
    {
        $filterType = $request->query('type');

        // 内容贴 markers (取每个 content 的第一个 place 的坐标)
        $contents = Content::public()
            ->with(['places', 'coverMedia', 'user'])
            ->whereHas('places', function ($q) {
                $q->whereNotNull('latitude')->whereNotNull('longitude');
            })
            ->when($filterType && in_array($filterType, array_keys(Content::TYPES), true), fn ($q) => $q->where('type', $filterType))
            ->latest('published_at')
            ->limit(200)
            ->get()
            ->map(function ($c) {
                $place = $c->places->firstWhere(fn ($p) => $p->latitude && $p->longitude);
                if (! $place) return null;
                $cover = $c->coverMedia ?: $c->gallery->first();
                return [
                    'id'         => $c->id,
                    'title'      => $c->title,
                    'type'       => $c->type,
                    'type_label' => __('ui.type_' . $c->type),
                    'type_icon'  => $c->typeMeta()['icon'],
                    'type_color' => $c->typeMeta()['color'],
                    'rating'     => $c->rating_label,
                    'city'       => $place->city,
                    'lat'        => (float) $place->latitude,
                    'lng'        => (float) $place->longitude,
                    'cover'      => $cover?->url,
                    'url'        => url('/content/' . $c->id),
                    'user'       => $c->user?->name,
                ];
            })->filter()->values();

        // 单独 places (没有 content 的孤儿 place)
        $placeIds = Content::public()->with('places')->get()->pluck('places.*.id')->flatten()->unique();
        $standalonePlaces = Place::query()
            ->whereNotIn('id', $placeIds)
            ->whereNotNull('latitude')->whereNotNull('longitude')
            ->select(['id', 'name', 'latitude', 'longitude', 'city'])
            ->limit(200)
            ->get()
            ->map(fn ($p) => [
                'id'    => $p->id,
                'title' => $p->name,
                'type'  => 'place',
                'lat'   => (float) $p->latitude,
                'lng'   => (float) $p->longitude,
                'city'  => $p->city,
                'url'   => url('/place/' . $p->id),
            ]);

        $types = collect(self::TYPES)->map(function ($t) {
            $t['label'] = __($t['label_key']);
            return $t;
        })->all();

        return view('frontend.map', [
            'contents' => $contents,
            'places'   => $standalonePlaces,
            'types'    => $types,
            'filterType' => $filterType,
        ]);
    }

    public function radar(Request $request)
    {
        $defaultCity = $request->query('city');
        return view('frontend.radar', compact('defaultCity'));
    }

    /**
     * Phase 18.3: 全文搜索 (Postgres FTS)
     *  - 搜 contents + places
     *  - 用 websearch_to_tsquery 支持 "千岛湖 自驾" 语法
     *  - 返回 results 分组 (内容/地点)
     */
    public function search(Request $request)
    {
        $q = trim((string) $request->query('q', ''));
        $type = $request->query('type'); // content / place / null=both
        $results = ['contents' => collect(), 'places' => collect()];

        if (mb_strlen($q) >= 1) {
            $tsQuery = \DB::raw("plainto_tsquery('simple', ?)");

            if (! $type || $type === 'content') {
                $contents = Content::public()
                    ->with(['user', 'coverMedia', 'places'])
                    ->whereRaw("search_vector @@ plainto_tsquery('simple', ?)", [$q])
                    ->orderByRaw("ts_rank(search_vector, plainto_tsquery('simple', ?)) DESC", [$q])
                    ->limit(20)
                    ->get();
                $results['contents'] = $contents->map(fn ($c) => $this->presentContent($c));
            }
            if (! $type || $type === 'place') {
                $places = Place::query()
                    ->whereRaw("search_vector @@ plainto_tsquery('simple', ?)", [$q])
                    ->orderByRaw("ts_rank(search_vector, plainto_tsquery('simple', ?)) DESC", [$q])
                    ->limit(20)
                    ->get();
                $results['places'] = $places->map(fn ($p) => $this->presentPlace($p));
            }
        }

        $types = collect(self::TYPES)->map(function ($t) {
            $t['label'] = __($t['label_key']);
            return $t;
        })->all();

        return view('frontend.search', [
            'q' => $q,
            'type' => $type,
            'results' => $results,
            'types' => $types,
        ]);
    }

    /**
     * Phase 18.2: Radar 附近内容 (用 PostGIS 计算距离)
     *  - lat/lng/radius (m) 必填
     *  - 不用 PostGIS 时: 用 Haversine 公式在 PHP 算 (fallback)
     */
    public function radarNearby(Request $request)
    {
        $lat = (float) $request->query('lat', 0);
        $lng = (float) $request->query('lng', 0);
        $radius = (int) $request->query('radius', 5000); // 米
        $limit = min(50, (int) $request->query('limit', 20));

        if (! $lat || ! $lng) {
            return response()->json(['data' => [], 'error' => 'lat/lng required'], 400);
        }

        // Haversine (PHP) - 不依赖 PostGIS extension
        // 包成子查询，因为 PostgreSQL 不允许在 HAVING 里直接引用 SELECT 别名
        $haversine = "(6371000 * acos(
            least(1.0, cos(radians(?)) * cos(radians(places.latitude)) *
            cos(radians(places.longitude) - radians(?)) +
            sin(radians(?)) * sin(radians(places.latitude))
        )))";

        $places = Place::from(\DB::raw('places'))
            ->select('places.*')
            ->selectRaw("$haversine AS distance_meters", [$lat, $lng, $lat])
            ->whereNotNull('places.latitude')
            ->whereNotNull('places.longitude')
            ->whereRaw("$haversine <= ?", [$lat, $lng, $lat, $radius])
            ->orderBy('distance_meters')
            ->limit($limit)
            ->get();

        return response()->json([
            'data' => $places->map(fn ($p) => [
                'id' => $p->id,
                'name' => $p->name,
                'address' => $p->address,
                'city' => $p->city,
                'latitude' => (float) $p->latitude,
                'longitude' => (float) $p->longitude,
                'distance_meters' => (int) $p->distance_meters,
                'url' => url('/place/' . $p->id),
            ]),
            'meta' => [
                'center' => ['lat' => $lat, 'lng' => $lng],
                'radius' => $radius,
                'count' => $places->count(),
            ],
        ]);
    }

    public function me(Request $request)
    {
        $user = auth()->user();
        if (! $user) {
            return redirect('/login');
        }

        $stats = [
            'contents_total'    => Content::where('user_id', $user->id)->count(),
            'contents_public'   => Content::where('user_id', $user->id)->where('is_public', true)->count(),
            'contents_wishlist' => Content::where('user_id', $user->id)->where('is_wishlist', true)->count(),
            'contents_visited'  => Content::where('user_id', $user->id)->where('is_visited', true)->count(),
            'places_total'      => Place::where('user_id', $user->id)->count(),
            'collections_total' => $user->collections()->count(),
            'notes_total'       => $user->notes()->count(),
            'activities_total'  => $user->activities()->count(),
            'comments_total'    => $user->comments()->count(),
        ];

        $recentContents = Content::with('coverMedia')
            ->where('user_id', $user->id)
            ->latest()
            ->limit(6)
            ->get();

        $recentPlaces = Place::with('media')
            ->where('user_id', $user->id)
            ->latest()
            ->limit(6)
            ->get();

        $collections = $user->collections()->withCount('places')->latest()->limit(5)->get();

        return view('frontend.me', [
            'user' => $user,
            'stats' => $stats,
            'recentContents' => $recentContents,
            'recentPlaces' => $recentPlaces,
            'collections' => $collections,
        ]);
    }

    public function myContents(Request $request)
    {
        $user = auth()->user();
        if (! $user) {
            return redirect('/login');
        }

        $filter = $request->query('filter', 'all');
        $query = Content::with(['coverMedia', 'places', 'gallery'])
            ->where('user_id', $user->id);

        if ($filter === 'wishlist') {
            $query->where('is_wishlist', true);
        } elseif ($filter === 'visited') {
            $query->where('is_visited', true);
        } elseif ($filter === 'unpublic') {
            $query->where('is_public', false);
        }

        $contents = $query->latest()->paginate(20);

        return view('frontend.my_contents', ['contents' => $contents, 'filter' => $filter]);
    }

    public function myPlaces(Request $request)
    {
        $user = auth()->user();
        if (! $user) {
            return redirect('/login');
        }

        $places = $user->places()->with('media')->latest()->paginate(20);
        return view('frontend.my_places', ['places' => $places]);
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
        $myCreated = Activity::where('user_id', $user->id)
            ->withCount('participants')
            ->with('place', 'content')
            ->latest()->get();
        $myJoined = \App\Models\ActivityParticipant::where('user_id', $user->id)
            ->with('activity')
            ->latest()->get();
        return view('frontend.my_activities', compact('myCreated', 'myJoined'));
    }

    // ====== 活动 ======

    public function activities(Request $request)
    {
        $filters = array_filter([
            'upcoming'    => $request->query('upcoming', 1),
            'region_code' => $request->query('region'),
        ]);
        $page = Activity::where('is_public', true)
            ->whereIn('status', ['open', 'full'])
            ->when(! empty($filters['upcoming']), fn ($q) => $q->where('start_at', '>=', now()))
            ->when(! empty($filters['region_code']), fn ($q) => $q->where('region_code', $filters['region_code']))
            ->with(['user', 'place', 'content'])
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
        $contentId = $request->query('content_id');
        $preset = [
            'place' => null,
            'content' => null,
        ];
        if ($placeId) {
            $preset['place'] = Place::find($placeId);
        }
        if ($contentId) {
            $preset['content'] = Content::public()->find($contentId);
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
            'place_id'   => 'nullable|integer|exists:places,id',
            'content_id' => 'nullable|integer|exists:contents,id',
        ]);
        $data['user_id'] = auth()->id();
        $data['status'] = 'open';
        Activity::create($data);
        return redirect('/activities')->with('ok', '活动已发布');
    }

    public function activityShow(int $id)
    {
        $activity = Activity::with(['user', 'place', 'content'])
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
        $activity = Activity::findOrFail($id);
        if ($activity->is_expired) {
            return back()->with('error', '活动已截止/取消');
        }
        $people = max(1, (int) $request->input('people_count', 1));
        \App\Models\ActivityParticipant::updateOrCreate(
            ['activity_id' => $id, 'user_id' => auth()->id()],
            ['status' => 'joined', 'people_count' => $people]
        );
        // Phase 18.4: 通知活动发起人
        if ($activity->user && $activity->user->id !== auth()->id()) {
            $activity->user->notify(new \App\Notifications\ContentInteractionNotification(
                type: 'activity_joined',
                title: '有人报名了你的活动「' . mb_substr($activity->title, 0, 20) . '」',
                message: $people . ' 人 · ' . auth()->user()->name,
                url: url('/activities/' . $activity->id),
                activityId: $activity->id,
            ));
        }
        return back()->with('ok', '报名成功');
    }

    /**
     * Phase 18.4: 通知列表
     */
    public function notifications(Request $request)
    {
        $user = auth()->user();
        if (! $user) return redirect('/login');

        $notifs = $user->notifications()->latest()->paginate(30);
        $unreadCount = $user->unreadNotifications()->count();

        return view('frontend.notifications', [
            'notifs' => $notifs,
            'unreadCount' => $unreadCount,
        ]);
    }

    public function notificationRead(Request $request, string $id)
    {
        $user = auth()->user();
        if (! $user) return redirect('/login');
        $notif = $user->notifications()->where('id', $id)->first();
        if ($notif) {
            $notif->markAsRead();
            $url = $notif->data['url'] ?? '/me';
        }
        return redirect($url ?? '/notifications');
    }

    public function notificationMarkAll(Request $request)
    {
        $user = auth()->user();
        if (! $user) return redirect('/login');
        $user->unreadNotifications->markAsRead();
        return back()->with('ok', '已全部标记已读');
    }

    public function notificationUnreadCount()
    {
        return response()->json([
            'count' => auth()->check() ? auth()->user()->unreadNotifications()->count() : 0,
        ]);
    }

    // ---- Phase 18.5: 关注/粉丝 ----

    public function userProfile(string $name)
    {
        $user = User::where('name', $name)->firstOrFail();
        $contents = Content::public()
            ->where('user_id', $user->id)
            ->with(['coverMedia', 'places'])
            ->latest()
            ->limit(20)
            ->get()
            ->map(fn ($c) => $this->presentContent($c));

        $isFollowing = auth()->check() ? auth()->user()->isFollowing($user) : false;
        $stats = [
            'contents'    => Content::where('user_id', $user->id)->where('is_public', true)->count(),
            'places'      => Place::where('user_id', $user->id)->count(),
            'followers'   => $user->followers()->count(),
            'followings'  => $user->followings()->count(),
        ];

        return view('frontend.user_profile', [
            'profile'     => $user,
            'contents'    => $contents,
            'isFollowing' => $isFollowing,
            'stats'       => $stats,
        ]);
    }

    public function followToggle(Request $request, int $id)
    {
        if (! auth()->check()) {
            return response()->json(['error' => 'auth_required'], 401);
        }
        $user = User::findOrFail($id);
        if ($user->id === auth()->id()) {
            return response()->json(['error' => 'cannot_follow_self'], 400);
        }
        $me = auth()->user();
        if ($me->isFollowing($user)) {
            $me->unfollow($user);
            $following = false;
        } else {
            $me->follow($user);
            $following = true;
            // 通知被关注者
            $user->notify(new \App\Notifications\ContentInteractionNotification(
                type: 'followed',
                title: $me->name . ' 关注了你',
                message: $me->name . ' 现在能在动态里看到你的内容',
                url: url('/u/' . $me->name),
            ));
        }
        return response()->json([
            'ok' => true,
            'following' => $following,
            'followers_count' => $user->followers()->count(),
        ]);
    }

    // ---- Phase 18.6: Tag 标签页 ----

    public function tagIndex(Request $request)
    {
        $tags = \App\Models\Tag::query()
            ->withCount('contents')
            ->orderBy('contents_count', 'desc')
            ->limit(200)
            ->get();

        return view('frontend.tags_index', ['tags' => $tags]);
    }

    public function tagShow(Request $request, string $slug)
    {
        $tag = \App\Models\Tag::where('slug', $slug)->orWhere('name', $slug)->firstOrFail();
        $contents = Content::public()
            ->whereHas('tags', fn ($q) => $q->where('tags.id', $tag->id))
            ->with(['user', 'coverMedia', 'places'])
            ->latest()
            ->paginate(24);

        return view('frontend.tag_show', [
            'tag' => $tag,
            'contents' => $contents,
        ]);
    }

    // ---- Phase 18.7: 内容分享卡 (生成 1200x630 PNG) ----

    /**
     * 生成内容分享卡 PNG (Open Graph image)
     *  - 1200x630 (微信/小红书/微博 标准尺寸)
     *  - 背景: 8 type 颜色 + 深色渐变
     *  - N° 编号 + type 标签 + 标题 + 副标题 + 作者
     *  - 缓存到 storage/app/public/share/{id}.png (regenerate on demand)
     */
    public function shareCard(int $id)
    {
        $c = Content::public()->with(['user', 'places'])->findOrFail($id);
        $relPath = 'share/content-' . $c->id . '.png';
        $absPath = \Storage::disk('public')->path($relPath);

        // 缓存: 24h 内不重生成
        if (file_exists($absPath) && (time() - filemtime($absPath)) < 86400) {
            return response()->file($absPath, ['Content-Type' => 'image/png']);
        }

        \Storage::disk('public')->makeDirectory('share');
        $typeMeta = $c->typeMeta();
        $color = $typeMeta['color'] ?? '#114B5F';
        $typeLabel = __('ui.type_' . $c->type);

        $w = 1200; $h = 630;
        $im = imagecreatetruecolor($w, $h);

        // 颜色
        [$r, $g, $b] = sscanf($color, "#%02x%02x%02x");
        $typeColor = imagecolorallocate($im, $r, $g, $b);
        $ink = imagecolorallocate($im, 26, 24, 20);     // #1A1814
        $paper = imagecolorallocate($im, 242, 237, 226); // #F2EDE2
        $ink3 = imagecolorallocate($im, 132, 126, 114);  // #847E72
        $warm = imagecolorallocate($im, 196, 86, 38);    // #C45626

        // 背景渐变 (从 type 颜色 → 深 ink)
        for ($y = 0; $y < $h; $y++) {
            $t = $y / $h;
            $cr = (int) ($r * (1 - $t) + 26 * $t);
            $cg = (int) ($g * (1 - $t) + 24 * $t);
            $cb = (int) ($b * (1 - $t) + 20 * $t);
            $line = imagecolorallocate($im, $cr, $cg, $cb);
            imageline($im, 0, $y, $w, $y, $line);
        }

        // 顶部 mono 文字
        $fontPath = $this->findSystemFont();
        $fontBold = $this->findSystemFont(bold: true);

        if ($fontPath) {
            // 顶部 mono 标签
            imagettftext($im, 16, 0, 60, 60, $paper, $fontPath, "MARKER · ROAD ATLAS");
            imagettftext($im, 14, 0, 60, 90, $paper, $fontPath, strtoupper($typeMeta['icon'] ?? 'N°00') . " · " . strtoupper($typeLabel));

            // 标题 (大, 最多 2 行)
            $title = $c->title;
            $title = mb_strimwidth($title, 0, 28, '...');
            imagettftext($im, 56, 0, 60, 280, $paper, $fontBold, $this->safe($title));

            // 副标题
            if ($c->subtitle) {
                $sub = mb_strimwidth($c->subtitle, 0, 50, '...');
                imagettftext($im, 22, 0, 60, 340, $paper, $fontPath, $this->safe($sub));
            } elseif ($c->summary) {
                $summary = mb_strimwidth(strip_tags($c->summary), 0, 60, '...');
                imagettftext($im, 20, 0, 60, 340, $paper, $fontPath, $this->safe($summary));
            }

            // 底部信息
            $author = $c->user ? '@' . $c->user->name : '@anonymous';
            $city = $c->places->first()?->city ?? '';
            imagettftext($im, 18, 0, 60, 560, $paper, $fontPath, "CURATED BY " . strtoupper($author));
            if ($city) {
                imagettftext($im, 14, 0, 60, 590, $paper, $fontPath, strtoupper($city));
            }

            // 右下大数字
            $num = sprintf("N°%02d", $c->id);
            imagettftext($im, 80, 0, 850, 480, $paper, $fontBold, $num);
        } else {
            // fallback 简单文字 (用内置字体)
            imagestring($im, 5, 60, 60, "MARKER", $paper);
            imagestring($im, 5, 60, 100, strtoupper($typeLabel), $paper);
        }

        // 底部 hairline
        imageline($im, 60, 540, $w - 60, 540, $paper);
        // 左下角 logo 点
        imagefilledellipse($im, 40, 590, 12, 12, $warm);

        imagepng($im, $absPath, 6);
        imagedestroy($im);

        return response()->file($absPath, ['Content-Type' => 'image/png']);
    }

    private function findSystemFont(bool $bold = false): ?string
    {
        $candidates = $bold
            ? ['/System/Library/Fonts/Supplemental/Songti.ttc', '/System/Library/Fonts/PingFang.ttc', '/System/Library/Fonts/Helvetica.ttc', '/Library/Fonts/Arial Bold.ttf', '/usr/share/fonts/truetype/dejavu/DejaVuSans-Bold.ttf']
            : ['/System/Library/Fonts/Supplemental/Songti.ttc', '/System/Library/Fonts/PingFang.ttc', '/System/Library/Fonts/Helvetica.ttc', '/usr/share/fonts/truetype/dejavu/DejaVuSans.ttf'];
        foreach ($candidates as $f) {
            if (file_exists($f)) return $f;
        }
        return null;
    }

    private function safe(string $s): string
    {
        return mb_convert_encoding($s, 'UTF-8', 'UTF-8');
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

    // ---- 评论区 (多态) ----
    public function commentStore(Request $request, string $type, int $id)
    {
        if (! auth()->check()) {
            return back()->with('error', '请先登录');
        }
        $data = $request->validate([
            'body' => 'required|string|max:2000',
            'parent_id' => 'nullable|integer|exists:comments,id',
            'rating_label' => 'nullable|string|in:terrible,npc,nice,great,amazing',
            // Phase 17：评论图片/视频 (多文件)
            'images' => 'nullable|array|max:9',
            'images.*' => 'image|mimes:jpg,jpeg,png,webp|max:8192',
            'videos' => 'nullable|array|max:3',
            'videos.*' => 'file|mimes:mp4,mov,webm|max:51200', // 50MB
        ]);

        $modelClass = match ($type) {
            'content' => Content::class,
            'place' => Place::class,
            'activity' => Activity::class,
            default => abort(404),
        };

        $model = $modelClass::findOrFail($id);

        $comment = new Comment();
        $comment->body = $data['body'];
        $comment->user_id = auth()->id();
        $comment->parent_id = $data['parent_id'] ?? null;
        $comment->rating_label = $data['rating_label'] ?? null;
        $comment->is_public = true;
        $model->comments()->save($comment);

        // Phase 17：attach uploaded images/videos → media 表 + comment_media 关联
        $seq = 0;
        foreach (($request->file('images') ?? []) as $file) {
            $path = $file->store('comments/' . $comment->id . '/images', 'public');
            $media = \App\Models\Media::create([
                'user_id' => auth()->id(),
                'type' => 'image',
                'disk' => 'public',
                'path' => $path,
                'mime' => $file->getMimeType(),
                'size' => $file->getSize(),
            ]);
            $comment->media()->attach($media->id, [
                'kind' => 'image',
                'sequence' => $seq++,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }
        $seq = 0;
        foreach (($request->file('videos') ?? []) as $file) {
            $path = $file->store('comments/' . $comment->id . '/videos', 'public');
            $media = \App\Models\Media::create([
                'user_id' => auth()->id(),
                'type' => 'video',
                'disk' => 'public',
                'path' => $path,
                'mime' => $file->getMimeType(),
                'size' => $file->getSize(),
            ]);
            $comment->media()->attach($media->id, [
                'kind' => 'video',
                'sequence' => $seq++,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }

        // Phase 18.4: 通知 content/activity/place 作者 (非自己)
        $owner = $model->user ?? null;
        if ($owner && $owner->id !== auth()->id()) {
            $title = match ($type) {
                'content' => '有人评论了你的内容',
                'place'   => '有人评论了你的地点',
                'activity' => '有人评论了你的活动',
            };
            $owner->notify(new \App\Notifications\ContentInteractionNotification(
                type: 'comment',
                title: $title,
                message: mb_substr($data['body'], 0, 60) . (mb_strlen($data['body']) > 60 ? '...' : ''),
                url: match ($type) {
                    'content' => url('/content/' . $model->id . '#comment-' . $comment->id),
                    'place'   => url('/place/' . $model->id),
                    'activity' => url('/activities/' . $model->id),
                },
                contentId: $type === 'content' ? $model->id : null,
                activityId: $type === 'activity' ? $model->id : null,
            ));
        }

        return back()->with('ok', '评论已发布');
    }

    // ---- Phase 17：内容评分投票 ----
    public function vote(Request $request, int $id)
    {
        if (! auth()->check()) {
            return response()->json(['error' => 'auth_required'], 401);
        }
        $data = $request->validate([
            'value' => 'required|integer|min:1|max:5',
        ]);
        $content = Content::public()->findOrFail($id);
        $content->vote(auth()->user(), (int) $data['value']);

        // Phase 18.4: 通知 content 作者 (投票人 != 作者)
        if ($content->user && $content->user->id !== auth()->id()) {
            $labels = Content::RATING_LABELS;
            $v = (int) $data['value'];
            $ratingLabel = $labels[$content->fresh()->rating_label]['label'] ?? '';
            $content->user->notify(new \App\Notifications\ContentInteractionNotification(
                type: 'vote',
                title: '有人给「' . mb_substr($content->title, 0, 20) . '」投了' . ($labels[array_key_first(array_filter($labels, fn ($l) => $l['label'] === ($labels[$content->fresh()->rating_label]['label'] ?? ''))) ?? 'nice'] ?? $ratingLabel),
                message: '当前共 ' . $content->fresh()->vote_count . ' 票 · avg ' . $content->fresh()->vote_avg,
                url: url('/content/' . $content->id),
                contentId: $content->id,
            ));
        }

        return response()->json([
            'ok' => true,
            'rating_label' => $content->fresh()->rating_label,
            'vote_count' => $content->fresh()->vote_count,
            'vote_avg' => $content->fresh()->vote_avg,
            'vote_distribution' => $content->fresh()->vote_distribution,
        ]);
    }

    // ---- helpers ----
    protected function getItemsByType(string $typeKey, int $limit = 10)
    {
        return Content::public()
            ->ofType($typeKey)
            ->with(['user', 'coverMedia', 'gallery', 'places'])
            ->orderByDesc('view_count')
            ->limit($limit)
            ->get()
            ->map(fn ($c) => $this->presentContent($c));
    }

    protected function presentContent(Content $c): array
    {
        $cover = $c->coverMedia ?: $c->gallery->first();
        $city = $c->places->first()?->city;
        $typeMeta = $c->typeMeta();
        return [
            'kind'        => 'content',
            'id'          => $c->id,
            'type'        => $c->type,
            'type_label'  => __('ui.type_' . $c->type), // 翻译后的类型标签
            'type_icon'   => $typeMeta['icon'] ?? 'N°00',
            'type_color'  => $typeMeta['color'] ?? '#4A4640',
            'title'       => $c->title,
            'name'        => $c->title, // alias for legacy home view
            'subtitle'    => $c->subtitle,
            'summary'     => $c->summary,
            'description' => $c->summary, // alias
            'cover'       => $cover?->url ?? url('/images/placeholder.png'),
            'city'        => $city,
            'rating_label'   => $c->rating_label,
            'rating_label_text' => $c->rating_label ? (__('ui.rating_' . $c->rating_label) ?: null) : null,
            'places_count'   => $c->places->count(),
            'is_multiple'    => $c->isMultiplePlaces(),
            'view_count'     => $c->view_count,
            'is_wishlist'    => $c->is_wishlist,
            'is_visited'     => $c->is_visited,
            'is_public'      => $c->is_public,
            'user' => $c->user ? ['id' => $c->user->id, 'name' => $c->user->name] : null,
            'url' => url("/content/{$c->id}"),
        ];
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
            'city' => $p->city,
            'view_count' => $p->view_count,
            'url' => url("/place/{$p->id}"),
        ];
    }
}
