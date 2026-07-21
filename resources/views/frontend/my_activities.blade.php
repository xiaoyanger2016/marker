@extends('frontend.layout')

@section('title', '我的活动 · Marker')

@section('content')
<section class="px-4 py-4 max-w-2xl mx-auto">
    <h1 class="text-xl font-bold text-gray-900">🎒 我的活动</h1>
    <p class="text-xs text-gray-500 mt-1">约伴、发起活动、跟队参加</p>
</section>

<section class="px-4 max-w-2xl mx-auto mb-3">
    <h2 class="text-sm font-semibold text-gray-700 mb-2">我发起的</h2>
    @forelse($myCreated as $a)
        <a href="/activities/{{ $a->id }}" class="block p-3 bg-white rounded-xl shadow-sm mb-2">
            <div class="font-medium text-sm">{{ $a->title ?? '未命名活动' }}</div>
            <div class="text-xs text-gray-500 mt-0.5">
                {{ $a->start_date ?? '日期待定' }}
                · {{ $a->participants_count ?? 0 }} 人参加
            </div>
        </a>
    @empty
        <div class="p-6 bg-white rounded-xl text-center text-gray-400 text-sm">
            <div class="text-3xl mb-2">📅</div>
            还没有发起的活动
        </div>
    @endforelse
</section>

<section class="px-4 max-w-2xl mx-auto">
    <h2 class="text-sm font-semibold text-gray-700 mb-2">我参加的</h2>
    @forelse($myJoined as $p)
        <a href="/activities/{{ $p->activity_id ?? $p->id }}" class="block p-3 bg-white rounded-xl shadow-sm mb-2">
            <div class="font-medium text-sm">活动 #{{ $p->activity_id ?? $p->id }}</div>
        </a>
    @empty
        <div class="p-6 bg-white rounded-xl text-center text-gray-400 text-sm">
            <div class="text-3xl mb-2">🚶</div>
            还没参加过活动
        </div>
    @endforelse
</section>

@endsection
