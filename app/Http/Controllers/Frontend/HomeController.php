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
     */
    public const TYPES = [
        ['key' => 'self_drive',     'label' => '自驾线路', 'icon' => 'N°01', 'color' => '#114B5F', 'desc' => '公路旅行的路径和途经点'],
        ['key' => 'play_water',     'label' => '玩水点',   'icon' => 'N°02', 'color' => '#0D3A4A', 'desc' => '可下水游泳戏水的地点'],
        ['key' => 'hiking',         'label' => '徒步线路', 'icon' => 'N°03', 'color' => '#2D5F3F', 'desc' => '行走探索的路径'],
        ['key' => 'paddle',         'label' => '桨板点',   'icon' => 'N°04', 'color' => '#0D5C5C', 'desc' => '桨板 / SUP 适合的水域'],
        ['key' => 'photo',          'label' => '拍照点',   'icon' => 'N°05', 'color' => '#A1461E', 'desc' => '值得出片的取景地'],
        ['key' => 'food',           'label' => '美食探店', 'icon' => 'N°06', 'color' => '#C45626', 'desc' => '值得专程去吃的店'],
        ['key' => 'camping',        'label' => '露营点',   'icon' => 'N°07', 'color' => '#1A3A3A', 'desc' => '可以过夜的营地'],
        ['key' => 'sunrise_sunset', 'label' => '日出日落', 'icon' => 'N°08', 'color' => '#7A4A1A', 'desc' => '专门看日出日落的位置'],
    ];

    public function home()
    {
        // 8 大类各取热度 top 3
        $recommendations = [];
        foreach (self::TYPES as $type) {
            $items = $this->getItemsByType($type['key'], 3);
            if ($items->isNotEmpty()) {
                $recommendations[] = [
                    'type' => $type,
                    'items' => $items,
                ];
            }
        }

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
            'types' => self::TYPES,
            'recommendations' => $recommendations,
            'hotContents' => $hotContents,
            'recentPlaces' => $recentPlaces,
        ]);
    }

    public function type(string $key)
    {
        $type = collect(self::TYPES)->firstWhere('key', $key);
        if (! $type) {
            abort(404);
        }

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

        return view('frontend.content_show', [
            'content' => $content,
            'cover' => $content->coverMedia ?: $content->gallery->first(),
            'gallery' => $content->gallery,
            'videos' => $content->videos,
            'type' => $content->typeMeta(),
            'rating' => $content->ratingMeta(),
            'activities' => $activities,
        ]);
    }

    public function map()
    {
        $places = Place::query()
            ->select(['id', 'name', 'latitude', 'longitude', 'city', 'view_count'])
            ->with(['media' => fn ($q) => $q->where('is_cover', true)])
            ->get();

        return view('frontend.map', ['places' => $places]);
    }

    public function radar(Request $request)
    {
        return view('frontend.radar');
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

        return back()->with('ok', '评论已发布');
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
        return [
            'kind'        => 'content',
            'id'          => $c->id,
            'type'        => $c->type,
            'type_label'  => $c->typeMeta()['label'] ?? $c->type,
            'type_icon'   => $c->typeMeta()['icon'] ?? 'N°00',
            'type_color'  => $c->typeMeta()['color'] ?? '#4A4640',
            'title'       => $c->title,
            'name'        => $c->title, // alias for legacy home view
            'subtitle'    => $c->subtitle,
            'summary'     => $c->summary,
            'description' => $c->summary, // alias
            'cover'       => $cover?->url ?? url('/images/placeholder.png'),
            'city'        => $city,
            'rating_label'   => $c->rating_label,
            'rating_label_text' => $c->rating_label ? (Content::RATING_LABELS[$c->rating_label]['label'] ?? null) : null,
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
