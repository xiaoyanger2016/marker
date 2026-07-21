@extends('frontend.layout')

@section('title', '活动 · Marker')

@section('content')
<section class="px-4 py-4 bg-gradient-to-br from-rose-500 via-pink-500 to-orange-500 text-white">
    <div class="max-w-2xl mx-auto flex items-center justify-between">
        <div>
            <h1 class="text-xl font-bold">🎒 活动</h1>
            <p class="text-sm text-white/80 mt-1">看看大家在玩什么，感兴趣的点一下跟随报名</p>
        </div>
        @auth
            <a href="/activities/create" class="px-3 py-1.5 bg-white text-rose-600 text-sm font-medium rounded-full shadow hover:bg-rose-50">
                + 发起
            </a>
        @endauth
    </div>
</section>

{{-- 城市筛选 --}}
<section class="px-4 py-2 bg-white border-b border-gray-100">
    <div class="max-w-2xl mx-auto flex items-center gap-2 text-sm">
        <span class="text-gray-500">📍</span>
        <select id="region-select" class="flex-1 bg-transparent text-sm font-medium text-gray-700 focus:outline-none cursor-pointer">
            <option value="">全部城市</option>
            <option value="BJ-1" {{ $regionCode == 'BJ-1' ? 'selected' : '' }}>北京</option>
            <option value="SH-1" {{ $regionCode == 'SH-1' ? 'selected' : '' }}>上海</option>
            <option value="SZ"   {{ $regionCode == 'SZ' ? 'selected' : '' }}>深圳</option>
            <option value="CAN"  {{ $regionCode == 'CAN' ? 'selected' : '' }}>广州</option>
            <option value="HZ"   {{ $regionCode == 'HZ' ? 'selected' : '' }}>杭州</option>
            <option value="CD"   {{ $regionCode == 'CD' ? 'selected' : '' }}>成都</option>
            <option value="XA"   {{ $regionCode == 'XA' ? 'selected' : '' }}>西安</option>
            <option value="CS"   {{ $regionCode == 'CS' ? 'selected' : '' }}>长沙</option>
            <option value="KM"   {{ $regionCode == 'KM' ? 'selected' : '' }}>昆明</option>
            <option value="SY4"  {{ $regionCode == 'SY4' ? 'selected' : '' }}>三亚</option>
            <option value="SY5"  {{ $regionCode == 'SY5' ? 'selected' : '' }}>沈阳</option>
            <option value="DL"   {{ $regionCode == 'DL' ? 'selected' : '' }}>大连</option>
        </select>
    </div>
</section>

@if(session('ok'))
    <div class="max-w-2xl mx-auto mt-3 px-4">
        <div class="p-3 bg-emerald-50 border border-emerald-200 text-emerald-700 text-sm rounded-lg">✓ {{ session('ok') }}</div>
    </div>
@endif

<div class="px-4 py-3 max-w-2xl mx-auto space-y-3">
    @forelse($activities as $a)
        <a href="/activities/{{ $a->id }}" class="block bg-white rounded-2xl shadow-sm hover:shadow-md overflow-hidden">
            <div class="p-4">
                <div class="flex items-start justify-between gap-3">
                    <div class="flex-1 min-w-0">
                        <h3 class="font-bold text-gray-900 line-clamp-2">{{ $a->title }}</h3>
                        <div class="mt-1 flex items-center gap-2 text-xs text-gray-500">
                            <span>👤 {{ $a->user->name ?? '匿名' }}</span>
                            <span>·</span>
                            <span>{{ $a->region_name ?: '不限' }}</span>
                        </div>
                    </div>
                    <div class="text-right flex-shrink-0">
                        <div class="text-xs text-gray-400">{{ $a->status_label }}</div>
                        @if($a->fee > 0)
                            <div class="text-lg font-bold text-rose-500">¥{{ number_format($a->fee, 0) }}</div>
                            <div class="text-[10px] text-gray-400">/人</div>
                        @else
                            <div class="text-sm font-bold text-emerald-500">免费</div>
                        @endif
                    </div>
                </div>

                <div class="mt-3 flex items-center gap-3 text-xs text-gray-600">
                    <span>📅 {{ $a->start_at?->format('m-d H:i') }}</span>
                    @if($a->transport)<span>🚗 {{ $a->transport }}</span>@endif
                    <span class="ml-auto">👥 {{ $a->joined_participants_count }}{{ $a->max_participants > 0 ? '/' . $a->max_participants : '' }} 人</span>
                </div>

                @if($a->meeting_point)
                    <div class="mt-2 text-xs text-gray-500">📍 {{ $a->meeting_point }}</div>
                @endif

                @if($a->place)
                    <div class="mt-2 inline-flex items-center gap-1 px-2 py-0.5 bg-rose-50 text-rose-600 text-[10px] rounded">
                        📍 关联地点：{{ $a->place->name }}
                    </div>
                @endif
                @if($a->route)
                    <div class="mt-2 inline-flex items-center gap-1 px-2 py-0.5 bg-emerald-50 text-emerald-600 text-[10px] rounded ml-1">
                        🛣️ 关联线路：{{ $a->route->name }}
                    </div>
                @endif
            </div>
        </a>
    @empty
        <div class="p-12 bg-white rounded-xl text-center text-gray-400">
            <div class="text-5xl mb-3">🎈</div>
            <p class="text-sm">还没有活动，<a href="/activities/create" class="text-rose-500">发起一个</a>？</p>
        </div>
    @endforelse
</div>

<div class="max-w-2xl mx-auto px-4 pb-3">
    {{ $activities->links() }}
</div>
@endsection

@push('scripts')
<script>
document.getElementById('region-select')?.addEventListener('change', (e) => {
    const code = e.target.value;
    const url = new URL(window.location.href);
    if (code) url.searchParams.set('region', code);
    else url.searchParams.delete('region');
    window.location.href = url.toString();
});
</script>
@endpush
