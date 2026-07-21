@extends('frontend.layout')

@section('title', '我的 · Marker')

@section('content')
{{-- 用户头部 --}}
<section class="px-4 py-5 bg-gradient-to-br from-emerald-500 to-teal-500 text-white">
    <div class="max-w-2xl mx-auto flex items-center gap-4">
        <div class="w-16 h-16 rounded-full bg-white/20 backdrop-blur flex items-center justify-center text-2xl font-bold">
            {{ mb_substr($user->name, 0, 1) }}
        </div>
        <div class="flex-1 min-w-0">
            <h1 class="text-xl font-bold">{{ $user->name }}</h1>
            <p class="text-sm text-white/80">{{ $user->email }}</p>
        </div>
    </div>
</section>

{{-- 统计 --}}
<section class="px-4 py-4 bg-white border-b border-gray-100">
    <div class="max-w-2xl mx-auto grid grid-cols-3 gap-2 text-center">
        <a href="/me/places" class="p-3 bg-emerald-50 rounded-lg">
            <div class="text-2xl font-bold text-emerald-600">{{ $stats['places_total'] }}</div>
            <div class="text-xs text-gray-600 mt-1">📍 收藏地点</div>
            <div class="text-[10px] text-gray-400 mt-0.5">种草 {{ $stats['places_wishlist'] }} · 去过 {{ $stats['places_visited'] }}</div>
        </a>
        <a href="/me/routes" class="p-3 bg-orange-50 rounded-lg">
            <div class="text-2xl font-bold text-orange-600">{{ $stats['routes_total'] }}</div>
            <div class="text-xs text-gray-600 mt-1">🛣️ 线路</div>
        </a>
        <a href="/me/collections" class="p-3 bg-pink-50 rounded-lg">
            <div class="text-2xl font-bold text-pink-600">{{ $stats['collections_total'] }}</div>
            <div class="text-xs text-gray-600 mt-1">📂 收藏集</div>
        </a>
    </div>
</section>

{{-- 我的功能 --}}
<section class="px-4 py-3 bg-white border-b border-gray-100">
    <div class="max-w-2xl mx-auto grid grid-cols-4 gap-2 text-center text-xs">
        <a href="/me/places" class="p-2 hover:bg-gray-50 rounded">
            <div class="text-2xl">📍</div>
            <div class="mt-1 text-gray-700">我的地点</div>
        </a>
        <a href="/me/routes" class="p-2 hover:bg-gray-50 rounded">
            <div class="text-2xl">🛣️</div>
            <div class="mt-1 text-gray-700">我的线路</div>
        </a>
        <a href="/me/collections" class="p-2 hover:bg-gray-50 rounded">
            <div class="text-2xl">📂</div>
            <div class="mt-1 text-gray-700">收藏集</div>
        </a>
        <a href="/me/activities" class="p-2 hover:bg-gray-50 rounded">
            <div class="text-2xl">🎒</div>
            <div class="mt-1 text-gray-700">我的活动</div>
        </a>
    </div>
</section>

{{-- 最近收藏 --}}
@if($recentPlaces->isNotEmpty())
    <section class="py-3">
        <div class="px-4 max-w-2xl mx-auto flex items-baseline justify-between mb-2">
            <h2 class="text-base font-bold text-gray-900">最近收藏</h2>
            <a href="/me/places" class="text-xs text-emerald-600">查看全部 →</a>
        </div>
        <div class="masonry max-w-2xl mx-auto">
            @foreach($recentPlaces as $p)
                @php
                    $ratios = ['aspect-[3/4]','aspect-square','aspect-[4/5]','aspect-[3/4]','aspect-[4/3]','aspect-[2/3]'];
                    $ratio = $ratios[$p->id % count($ratios)];
                    $gradients = [['#fda4af','#fb923c'],['#86efac','#22d3ee'],['#a78bfa','#f472b6'],['#fcd34d','#fb7185'],['#5eead4','#818cf8'],['#fca5a5','#a855f7']];
                    $gradient = $gradients[$p->id % count($gradients)];
                    $icon = \App\Models\Place::PLACE_TYPES[$p->place_type]['icon'] ?? '📍';
                @endphp
                <a href="{{ url('/place/' . $p->id) }}" class="masonry-item group block bg-white rounded-2xl overflow-hidden shadow-sm">
                    <div class="{{ $ratio }} relative" style="background: linear-gradient(135deg, {{ $gradient[0] }}, {{ $gradient[1] }});">
                        <div class="w-full h-full flex items-center justify-center text-7xl opacity-90 group-hover:scale-110 transition-transform duration-300">{{ $icon }}</div>
                        @if($p->rating_label)
                            @php $rl = \App\Models\Place::RATING_LABELS[$p->rating_label] ?? null; @endphp
                            @if($rl)
                                <div class="absolute top-2 right-2">
                                    <span class="px-2 py-0.5 text-[10px] font-bold text-white rounded-full" style="background:{{ $rl['color'] }}">{{ $rl['icon'] }} {{ $rl['label'] }}</span>
                                </div>
                            @endif
                        @endif
                    </div>
                    <div class="p-3">
                        <h3 class="font-semibold text-sm line-clamp-1">{{ $p->name }}</h3>
                        <div class="mt-1 text-[10px] text-gray-400">{{ $p->city ?? '—' }} · {{ $p->created_at->diffForHumans() }}</div>
                    </div>
                </a>
            @endforeach
        </div>
    </section>
@endif

{{-- 最近线路 --}}
@if($recentRoutes->isNotEmpty())
    <section class="py-3">
        <div class="px-4 max-w-2xl mx-auto flex items-baseline justify-between mb-2">
            <h2 class="text-base font-bold text-gray-900">我的线路</h2>
            <a href="/me/routes" class="text-xs text-emerald-600">查看全部 →</a>
        </div>
        <div class="max-w-2xl mx-auto space-y-2 px-4">
            @foreach($recentRoutes as $r)
                <a href="{{ url('/route/' . $r->id) }}" class="block p-3 bg-white rounded-xl shadow-sm hover:shadow-md">
                    <div class="flex items-center gap-3">
                        <div class="w-10 h-10 rounded-lg flex items-center justify-center text-xl text-white" style="background: {{ $r->typeMeta()['color'] ?? '#10b981' }}">
                            {{ $r->typeMeta()['icon'] ?? '🚗' }}
                        </div>
                        <div class="flex-1 min-w-0">
                            <div class="font-medium text-sm truncate">{{ $r->name }}</div>
                            <div class="text-[10px] text-gray-400">{{ $r->typeMeta()['label'] ?? '' }} · {{ $r->places_count }} 个点</div>
                        </div>
                        @if($r->rating_label)
                            @php $rl = \App\Models\Route::RATING_LABELS[$r->rating_label] ?? null; @endphp
                            @if($rl)
                                <span class="px-2 py-0.5 text-[10px] font-bold text-white rounded-full" style="background:{{ $rl['color'] }}">{{ $rl['icon'] }} {{ $rl['label'] }}</span>
                            @endif
                        @endif
                    </div>
                </a>
            @endforeach
        </div>
    </section>
@endif

{{-- 收藏集 --}}
@if($collections->isNotEmpty())
    <section class="py-3">
        <div class="px-4 max-w-2xl mx-auto flex items-baseline justify-between mb-2">
            <h2 class="text-base font-bold text-gray-900">我的收藏集</h2>
            <a href="/me/collections" class="text-xs text-emerald-600">查看全部 →</a>
        </div>
        <div class="max-w-2xl mx-auto grid grid-cols-2 gap-2 px-4">
            @foreach($collections as $c)
                <a href="{{ url('/me/collections') }}" class="block p-3 bg-white rounded-xl shadow-sm">
                    <div class="flex items-center gap-2">
                        <div class="text-2xl">📂</div>
                        <div class="flex-1 min-w-0">
                            <div class="font-medium text-sm truncate">{{ $c->name }}</div>
                            <div class="text-[10px] text-gray-400">{{ $c->places_count }} 个地点</div>
                        </div>
                        @if($c->is_public)
                            <span class="text-[10px] text-emerald-600">🔗</span>
                        @endif
                    </div>
                </a>
            @endforeach
        </div>
    </section>
@endif

{{-- 设置 --}}
<section class="px-4 py-4 bg-white mt-3">
    <div class="max-w-2xl mx-auto space-y-1">
        <a href="/me/activities" class="flex items-center gap-3 p-3 hover:bg-gray-50 rounded-lg">
            <span class="text-xl">🎒</span>
            <span class="flex-1 text-sm">我的活动</span>
            <span class="text-gray-400 text-xs">→</span>
        </a>
        <a href="/admin" class="flex items-center gap-3 p-3 hover:bg-gray-50 rounded-lg">
            <span class="text-xl">⚙️</span>
            <span class="flex-1 text-sm">后台管理（需登录）</span>
            <span class="text-gray-400 text-xs">→</span>
        </a>
        @auth
            <form action="/logout" method="POST" class="block">
                @csrf
                <button type="submit" class="w-full text-left flex items-center gap-3 p-3 hover:bg-red-50 rounded-lg text-red-500">
                    <span class="text-xl">🚪</span>
                    <span class="flex-1 text-sm">退出登录</span>
                </button>
            </form>
        @endauth
    </div>
</section>

@endsection
