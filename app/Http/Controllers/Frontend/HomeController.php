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

        // 8 大类各取热度 top 3
        $recommendations = [];
        foreach ($types as $type) {
            $items = $this->getItemsByType($type['key'], 3);
            if ($items->isNotEmpty()) {
                $recommendations[] = [
                    'type' => $type,
                    'items' => $items,
                ];
            }
        }

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
            'recommendations' => $recommendations,
            'feedItems' => $feedItems,
            'feedType' => $feedType,
            'hotContents' => $hotContents,
            'recentPlaces' => $recentPlaces,
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
