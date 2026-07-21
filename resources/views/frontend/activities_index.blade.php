@extends('frontend.layout')

@section('title', '活动 · Marker 公路杂志')

@section('content')

{{-- 杂志式标题区：编号 + 衬线大字 + 副标 --}}
<section class="border-b border-line-2">
    <div class="max-w-6xl mx-auto px-5 sm:px-8 pt-6 pb-2">
        <div class="flex items-center justify-between text-[10px] font-mono uppercase tracking-[0.2em] text-ink-3 mb-4">
            <div class="flex items-center gap-3">
                <span>SECTION</span>
                <span class="w-px h-3 bg-line-2"></span>
                <span>04 · EVENTS</span>
            </div>
            <div class="hidden sm:flex items-center gap-3">
                <span>UPDATE: {{ now()->format('Y/m/d') }}</span>
            </div>
        </div>
    </div>
</section>

<section class="border-b border-line">
    <div class="max-w-6xl mx-auto px-5 sm:px-8 py-10">
        <div class="grid grid-cols-12 gap-4 sm:gap-8">
            <div class="col-span-12 sm:col-span-8">
                <h1 class="font-display font-medium text-4xl sm:text-6xl leading-[1.05] text-ink">
                    约伴，<br>
                    <span class="serif-italic text-warm">和陌生人</span><br>
                    看同一片风景
                </h1>
            </div>
            <div class="col-span-12 sm:col-span-4 sm:pt-12 flex flex-col justify-between">
                <p class="text-sm leading-relaxed text-ink-2 border-l border-line-2 pl-4">
                    一个周末、一个目的地、一群想出门的人。在这里发起你的约伴，
                    或者挑感兴趣的，<span class="font-display italic">follow 一下</span>。
                </p>
                <div class="mt-6">
                    @auth
                        <a href="/activities/create" class="btn btn-primary">
                            <span>发起约伴</span>
                            <span class="font-mono text-[10px] opacity-70">N°01</span>
                        </a>
                    @else
                        <a href="/login" class="btn btn-ghost">登录后发起</a>
                    @endauth
                </div>
            </div>
        </div>
    </div>
</section>

{{-- 筛选条：编辑感下拉 + 标签 --}}
<section class="border-b border-line">
    <div class="max-w-6xl mx-auto px-5 sm:px-8 py-4">
        <div class="flex items-center gap-3 flex-wrap">
            <span class="eyebrow flex-shrink-0">FILTER</span>
            <select id="region-select" class="input max-w-[200px] py-1.5 text-sm">
                <option value="">所有城市 / All Cities</option>
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
            </select>
            <span class="flex-1"></span>
            <span class="font-mono text-[10px] uppercase tracking-[0.2em] text-ink-3">{{ $activities->total() }} events</span>
        </div>
    </div>
</section>

@if(session('ok'))
    <div class="max-w-6xl mx-auto mt-4 px-5 sm:px-8">
        <div class="p-3 border border-grass text-grass text-sm font-mono">{{ session('ok') }}</div>
    </div>
@endif

{{-- 活动列表：杂志式目录，每条 = 一行 + 编号 + 状态 + 城市 --}}
<div class="max-w-6xl mx-auto px-5 sm:px-8 py-8 sm:py-12">
    @forelse($activities as $i => $a)
        <a href="/activities/{{ $a->id }}" class="block border-b border-line py-4 sm:py-6 group hover:bg-paper-2 transition-colors -mx-2 px-2">
            {{-- Mobile: 单列；sm+: 4 列对照 --}}
            <div class="flex items-start gap-3 sm:hidden">
                <span class="font-mono text-[10px] text-ink-3 uppercase tracking-[0.15em] mt-1.5 w-6 flex-shrink-0">
                    N°{{ str_pad($i + 1, 2, '0', STR_PAD_LEFT) }}
                </span>
                <div class="flex-1 min-w-0">
                    <h3 class="font-display text-base text-ink leading-tight line-clamp-2 group-hover:text-warm transition-colors">
                        {{ $a->title }}
                    </h3>
                    <div class="mt-1.5 flex items-center flex-wrap gap-x-2 gap-y-0.5 font-mono text-[10px] uppercase tracking-[0.15em] text-ink-3">
                        <span>{{ $a->start_at?->format('m/d H:i') }}</span>
                        <span class="text-ink-3/50">·</span>
                        <span>{{ $a->region_name ?: '不限' }}</span>
                    </div>
                    <div class="mt-2 flex items-center justify-between">
                        <span class="font-mono text-[10px] uppercase tracking-[0.15em] text-ink-3">
                            <svg class="w-3 h-3 inline-block -mt-px mr-0.5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5"><circle cx="9" cy="8" r="3"/><circle cx="17" cy="9" r="2.5"/><path d="M3 20a6 6 0 0112 0M14 19a5 5 0 0110 0"/></svg>
                            {{ $a->joined_participants_count }}<span class="text-ink-3/70">/{{ $a->max_participants > 0 ? $a->max_participants : '∞' }}</span>
                        </span>
                        @if($a->fee > 0)
                            <span class="font-display text-lg text-warm">¥{{ number_format($a->fee, 0) }}</span>
                        @else
                            <span class="font-display text-base text-grass">免费</span>
                        @endif
                    </div>
                </div>
            </div>

            {{-- sm+ 4 列对照 --}}
            <div class="hidden sm:grid grid-cols-12 gap-3 sm:gap-4 items-baseline">
                <div class="col-span-1 font-mono text-[10px] text-ink-3 uppercase tracking-[0.15em]">
                    N°{{ str_pad($i + 1, 2, '0', STR_PAD_LEFT) }}
                </div>
                <div class="col-span-7">
                    <h3 class="font-display text-xl sm:text-2xl text-ink leading-tight line-clamp-2 group-hover:text-warm transition-colors">
                        {{ $a->title }}
                    </h3>
                    <div class="mt-2 flex items-center gap-3 font-mono text-[10px] uppercase tracking-[0.15em] text-ink-3">
                        <span>{{ $a->start_at?->format('Y/m/d H:i') }}</span>
                        <span class="w-px h-3 bg-line-2"></span>
                        <span>{{ $a->region_name ?: '不限' }}</span>
                        @if($a->transport)
                            <span class="w-px h-3 bg-line-2"></span>
                            <span>{{ $a->transport }}</span>
                        @endif
                    </div>
                </div>
                <div class="col-span-2 font-mono text-[10px] uppercase tracking-[0.15em] text-ink-3">
                    <div class="text-ink text-base sm:text-lg font-display">
                        {{ $a->joined_participants_count }}<span class="text-ink-3 text-xs">/{{ $a->max_participants > 0 ? $a->max_participants : '∞' }}</span>
                    </div>
                    <div>已报名</div>
                </div>
                <div class="col-span-2 text-right">
                    @if($a->fee > 0)
                        <div class="font-display text-2xl text-warm">¥{{ number_format($a->fee, 0) }}</div>
                        <div class="font-mono text-[9px] uppercase tracking-[0.2em] text-ink-3">per person</div>
                    @else
                        <div class="font-display text-2xl text-grass">免费</div>
                        <div class="font-mono text-[9px] uppercase tracking-[0.2em] text-ink-3">free</div>
                    @endif
                </div>
            </div>
        </a>
    @empty
        <div class="py-20 text-center">
            <div class="font-display text-3xl text-ink-2 mb-2">还没有活动</div>
            <p class="text-sm text-ink-3 mb-4">这里会显示大家发起的约伴</p>
            @auth
                <a href="/activities/create" class="btn btn-primary">发起第一个</a>
            @else
                <a href="/login" class="btn btn-ghost">登录后发起</a>
            @endauth
        </div>
    @endforelse
</div>

<div class="max-w-6xl mx-auto px-5 sm:px-8 pb-12">
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
