@extends('frontend.layout')

@section('title', $activity->title . ' · 活动 · Marker')

@section('content')
<section class="px-4 py-4 bg-gradient-to-br from-rose-500 via-pink-500 to-orange-500 text-white">
    <div class="max-w-2xl mx-auto">
        <a href="/activities" class="text-xs text-white/80">← 返回列表</a>
        <h1 class="text-xl font-bold mt-2">{{ $activity->title }}</h1>
        <div class="mt-2 flex items-center gap-3 text-sm text-white/90">
            <span>👤 {{ $activity->user->name ?? '匿名' }}</span>
            <span>👁️ {{ $activity->view_count }}</span>
            <span class="ml-auto px-2 py-0.5 bg-white/20 text-white text-xs rounded-full">{{ $activity->status_label }}</span>
        </div>
        @auth
            @if($activity->user_id === auth()->id())
                <div class="mt-2">
                    <a href="/admin/activities/{{ $activity->id }}/edit" class="text-xs text-white/90 underline">后台编辑 →</a>
                </div>
            @endif
        @endauth
    </div>
</section>

<div class="px-4 py-3 max-w-2xl mx-auto space-y-3">
    {{-- 关键信息卡片 --}}
    <div class="bg-white rounded-2xl shadow-sm p-4">
        <div class="grid grid-cols-2 gap-3 text-sm">
            <div>
                <div class="text-xs text-gray-400">出发时间</div>
                <div class="font-medium text-gray-900 mt-0.5">📅 {{ $activity->start_at?->format('Y-m-d H:i') }}</div>
            </div>
            @if($activity->signup_deadline)
            <div>
                <div class="text-xs text-gray-400">报名截止</div>
                <div class="font-medium text-rose-600 mt-0.5">⏰ {{ $activity->signup_deadline->format('m-d H:i') }}</div>
            </div>
            @endif
            @if($activity->meeting_point)
            <div class="col-span-2">
                <div class="text-xs text-gray-400">集合地点</div>
                <div class="font-medium text-gray-900 mt-0.5">📍 {{ $activity->meeting_point }}</div>
            </div>
            @endif
            <div>
                <div class="text-xs text-gray-400">出行方式</div>
                <div class="font-medium text-gray-900 mt-0.5">{{ $activity->transport ?: '未指定' }}</div>
            </div>
            <div>
                <div class="text-xs text-gray-400">人数</div>
                <div class="font-medium text-gray-900 mt-0.5">
                    👥 {{ $activity->joined_participants_count }}{{ $activity->max_participants > 0 ? '/' . $activity->max_participants : '' }} 人
                </div>
            </div>
        </div>
    </div>

    {{-- 费用 --}}
    @if($activity->fee > 0 || $activity->fee_includes || $activity->fee_excludes)
    <div class="bg-white rounded-2xl shadow-sm p-4">
        <h3 class="text-sm font-bold text-gray-900 mb-2">💰 费用</h3>
        <div class="text-2xl font-bold text-rose-500 mb-3">
            ¥{{ number_format($activity->fee, 0) }}<span class="text-xs text-gray-400 font-normal"> /人</span>
        </div>
        @if($activity->fee_includes)
            <div class="text-xs text-gray-600 mb-1"><span class="text-emerald-600 font-medium">✓ 包含：</span>{{ $activity->fee_includes }}</div>
        @endif
        @if($activity->fee_excludes)
            <div class="text-xs text-gray-600"><span class="text-rose-500 font-medium">✗ 不含：</span>{{ $activity->fee_excludes }}</div>
        @endif
    </div>
    @endif

    {{-- 详情 --}}
    @if($activity->description)
    <div class="bg-white rounded-2xl shadow-sm p-4">
        <h3 class="text-sm font-bold text-gray-900 mb-2">📝 活动详情</h3>
        <p class="text-sm text-gray-700 leading-relaxed whitespace-pre-line">{{ $activity->description }}</p>
    </div>
    @endif

    {{-- 关联内容 --}}
    @if($activity->place || $activity->route)
    <div class="bg-white rounded-2xl shadow-sm p-4">
        <h3 class="text-sm font-bold text-gray-900 mb-2">🔗 关联内容</h3>
        @if($activity->place)
            <a href="/place/{{ $activity->place->id }}" class="block p-3 bg-rose-50 rounded-lg mb-2">
                <div class="text-xs text-rose-600">📍 关联地点</div>
                <div class="text-sm font-medium text-gray-900 mt-0.5">{{ $activity->place->name }}</div>
            </a>
        @endif
        @if($activity->route)
            <a href="/route/{{ $activity->route->id }}" class="block p-3 bg-emerald-50 rounded-lg">
                <div class="text-xs text-emerald-600">🛣️ 关联线路</div>
                <div class="text-sm font-medium text-gray-900 mt-0.5">{{ $activity->route->name }}</div>
            </a>
        @endif
    </div>
    @endif

    {{-- 已报名列表 --}}
    <div class="bg-white rounded-2xl shadow-sm p-4">
        <h3 class="text-sm font-bold text-gray-900 mb-2">👥 已报名 ({{ $participants->count() }})</h3>
        @if($participants->isNotEmpty())
            <div class="flex flex-wrap gap-2">
                @foreach($participants as $p)
                    <div class="flex items-center gap-1.5 px-2 py-1 bg-gray-50 rounded-full text-xs">
                        <div class="w-5 h-5 rounded-full bg-emerald-500 text-white text-[10px] flex items-center justify-center font-bold">
                            {{ mb_substr($p->user->name ?? '?', 0, 1) }}
                        </div>
                        <span class="text-gray-700">{{ $p->user->name ?? '匿名' }}</span>
                        @if($p->people_count > 1)<span class="text-rose-500">×{{ $p->people_count }}</span>@endif
                    </div>
                @endforeach
            </div>
        @else
            <p class="text-xs text-gray-400">还没有人报名，来做第一个吧～</p>
        @endif
    </div>
</div>

{{-- 底部报名按钮 --}}
<div class="fixed bottom-20 left-0 right-0 z-40 px-4 pb-2 bg-gradient-to-t from-white via-white to-transparent pt-3">
@auth
    @if($activity->user_id === auth()->id())
        <div class="max-w-2xl mx-auto space-y-2">
            <div class="bg-gray-100 text-gray-500 text-center py-2.5 rounded-2xl text-sm">你发起的活动</div>
            @if(! $activity->is_expired)
                <form method="POST" action="/activities/{{ $activity->id }}" onsubmit="return confirm('确定取消活动？报名的人会收到通知')">
                    @csrf
                    @method('DELETE')
                    <button type="submit" class="w-full py-2 bg-rose-50 text-rose-500 rounded-2xl text-xs">取消活动</button>
                </form>
            @endif
        </div>
    @elseif($activity->is_expired)
        <div class="max-w-2xl mx-auto">
            <div class="bg-gray-100 text-gray-500 text-center py-3 rounded-2xl text-sm">活动已截止/取消</div>
        </div>
    @else
        <div class="max-w-2xl mx-auto">
            @if($isJoined)
                <form method="POST" action="/activities/{{ $activity->id }}/leave">
                    @csrf
                    <button type="submit" class="w-full py-3 bg-gray-200 text-gray-700 rounded-2xl text-sm font-medium">
                        ✓ 已报名（点击取消）
                    </button>
                </form>
            @else
                <form method="POST" action="/activities/{{ $activity->id }}/join" class="flex gap-2">
                    @csrf
                    <select name="people_count" class="bg-white border border-gray-300 rounded-2xl px-3 text-sm">
                        @for($i=1; $i<=5; $i++)<option value="{{ $i }}">{{ $i }}人</option>@endfor
                    </select>
                    <button type="submit" class="flex-1 py-3 bg-rose-500 hover:bg-rose-600 text-white rounded-2xl text-sm font-bold">
                        🎒 一键报名
                    </button>
                </form>
            @endif
        </div>
    @endif
@else
    <div class="max-w-2xl mx-auto">
        <a href="/login" class="block w-full py-3 bg-rose-500 text-white text-center rounded-2xl text-sm font-bold">登录后报名</a>
    </div>
@endauth
</div>

@if(session('ok'))
    <div class="fixed top-20 left-0 right-0 z-50 px-4">
        <div class="max-w-sm mx-auto p-3 bg-emerald-500 text-white text-sm rounded-lg text-center">✓ {{ session('ok') }}</div>
    </div>
@endif
@if(session('error'))
    <div class="fixed top-20 left-0 right-0 z-50 px-4">
        <div class="max-w-sm mx-auto p-3 bg-rose-500 text-white text-sm rounded-lg text-center">{{ session('error') }}</div>
    </div>
@endif
@endsection
