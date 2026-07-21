@extends('frontend.layout')

@section('title', '我的活动 · Marker')

@section('content')
<section class="px-4 py-4 max-w-2xl mx-auto">
    <h1 class="text-xl font-bold text-gray-900">🎒 我的活动</h1>
    <p class="text-xs text-gray-500 mt-1">约伴、发起活动、跟队参加</p>
</section>

<section class="px-4 max-w-2xl mx-auto mb-4">
    <h2 class="text-sm font-semibold text-gray-700 mb-2">我发起的 <span class="text-xs text-gray-400">({{ $myCreated->count() }})</span></h2>
    @forelse($myCreated as $a)
        <a href="/activities/{{ $a->id }}" class="block p-3 bg-white rounded-xl shadow-sm mb-2 hover:shadow-md transition">
            <div class="flex items-start justify-between gap-2">
                <div class="flex-1 min-w-0">
                    <div class="font-medium text-sm text-gray-900 line-clamp-1">{{ $a->title ?? '未命名活动' }}</div>
                    <div class="text-xs text-gray-500 mt-0.5">
                        📅 {{ $a->start_at?->format('m-d H:i') ?? '日期待定' }}
                        · {{ $a->participants_count ?? 0 }} 人报名
                        · {{ $a->region_name ?? '' }}
                    </div>
                </div>
                <span class="text-xs px-2 py-0.5 rounded-full flex-shrink-0
                    @switch($a->status)
                        @case('open') bg-emerald-100 text-emerald-700 @break
                        @case('full') bg-amber-100 text-amber-700 @break
                        @case('cancelled') bg-rose-100 text-rose-700 @break
                        @case('closed') bg-gray-100 text-gray-600 @break
                        @default bg-gray-100 text-gray-600
                    @endswitch">
                    {{ \App\Models\Activity::STATUSES[$a->status] ?? $a->status }}
                </span>
            </div>
        </a>
    @empty
        <div class="p-6 bg-white rounded-xl text-center text-gray-400 text-sm">
            <div class="text-3xl mb-2">📅</div>
            还没有发起的活动
            <div class="mt-2"><a href="/activities/create" class="text-rose-500">立即发起一个 →</a></div>
        </div>
    @endforelse
</section>

<section class="px-4 max-w-2xl mx-auto">
    <h2 class="text-sm font-semibold text-gray-700 mb-2">我参加的 <span class="text-xs text-gray-400">({{ $myJoined->count() }})</span></h2>
    @forelse($myJoined as $p)
        @if($p->activity)
            <a href="/activities/{{ $p->activity->id }}" class="block p-3 bg-white rounded-xl shadow-sm mb-2 hover:shadow-md transition">
                <div class="flex items-start justify-between gap-2">
                    <div class="flex-1 min-w-0">
                        <div class="font-medium text-sm text-gray-900 line-clamp-1">{{ $p->activity->title ?? '未命名活动' }}</div>
                        <div class="text-xs text-gray-500 mt-0.5">
                            📅 {{ $p->activity->start_at?->format('m-d H:i') }}
                            @if($p->activity->meeting_point)· {{ $p->activity->meeting_point }}@endif
                        </div>
                    </div>
                    <span class="text-xs px-2 py-0.5 bg-rose-50 text-rose-600 rounded-full flex-shrink-0">
                        {{ $p->people_count > 1 ? '×' . $p->people_count : '已报名' }}
                    </span>
                </div>
            </a>
        @endif
    @empty
        <div class="p-6 bg-white rounded-xl text-center text-gray-400 text-sm">
            <div class="text-3xl mb-2">🚶</div>
            还没参加过活动
            <div class="mt-2"><a href="/activities" class="text-rose-500">去逛逛 →</a></div>
        </div>
    @endforelse
</section>

@endsection
