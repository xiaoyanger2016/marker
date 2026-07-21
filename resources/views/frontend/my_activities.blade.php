@extends('frontend.layout')

@section('title', '我的活动 · Marker')

@section('content')

<section class="border-b border-line-2">
    <div class="max-w-6xl mx-auto px-5 sm:px-8 pt-4 pb-2">
        <div class="flex items-center gap-3 font-mono text-[10px] uppercase tracking-[0.2em] text-ink-3">
            <a href="/me" class="hover:text-ink transition-colors">← PROFILE</a>
            <span class="w-px h-3 bg-line-2"></span>
            <span>N°04 · ACTIVITIES</span>
        </div>
    </div>
</section>

<section class="border-b border-line">
    <div class="max-w-6xl mx-auto px-5 sm:px-8 py-6 sm:py-10">
        <h1 class="font-display font-medium text-3xl sm:text-5xl text-ink leading-none">我的活动</h1>
        <p class="font-display italic text-base sm:text-lg text-ink-2 mt-3">约伴、发起、跟队参加。</p>
    </div>
</section>

<section class="border-b border-line">
    <div class="max-w-6xl mx-auto px-5 sm:px-8 py-6">
        <div class="eyebrow mb-3">§ 我发起的 <span class="text-ink-3">({{ $myCreated->count() }})</span></div>
        @forelse($myCreated as $a)
            <a href="/activities/{{ $a->id }}" class="block border-b border-line py-4 group hover:bg-paper-2 transition-colors -mx-2 px-2">
                <div class="grid grid-cols-12 gap-3 items-baseline">
                    <div class="col-span-7 sm:col-span-8">
                        <div class="font-display text-base text-ink group-hover:text-warm transition-colors line-clamp-1">{{ $a->title }}</div>
                        <div class="font-mono text-[10px] text-ink-3 mt-1 uppercase tracking-[0.15em]">
                            {{ $a->start_at?->format('m/d H:i') ?? '日期待定' }} · {{ $a->participants_count ?? 0 }} 人报名 · {{ $a->region_name ?? '' }}
                        </div>
                    </div>
                    <div class="col-span-5 sm:col-span-4 text-right">
                        <span class="tag {{ $a->status === 'open' ? 'tag-grass' : ($a->status === 'full' ? 'tag-warm' : ($a->status === 'cancelled' ? 'tag-blood' : '')) }}">
                            {{ \App\Models\Activity::STATUSES[$a->status] ?? $a->status }}
                        </span>
                    </div>
                </div>
            </a>
        @empty
            <div class="py-8 text-center text-ink-3">
                <p class="font-display italic">还没有发起的活动</p>
                <a href="/activities/create" class="font-mono text-[11px] text-warm uppercase tracking-[0.2em] mt-2 inline-block">立即发起 →</a>
            </div>
        @endforelse
    </div>
</section>

<section class="border-b border-line">
    <div class="max-w-6xl mx-auto px-5 sm:px-8 py-6">
        <div class="eyebrow mb-3">§ 我参加的 <span class="text-ink-3">({{ $myJoined->count() }})</span></div>
        @forelse($myJoined as $p)
            @if($p->activity)
                <a href="/activities/{{ $p->activity->id }}" class="block border-b border-line py-4 group hover:bg-paper-2 transition-colors -mx-2 px-2">
                    <div class="grid grid-cols-12 gap-3 items-baseline">
                        <div class="col-span-9 sm:col-span-10">
                            <div class="font-display text-base text-ink group-hover:text-warm transition-colors line-clamp-1">{{ $p->activity->title ?? '未命名' }}</div>
                            <div class="font-mono text-[10px] text-ink-3 mt-1 uppercase tracking-[0.15em]">
                                {{ $p->activity->start_at?->format('m/d H:i') }}
                                @if($p->activity->meeting_point)· {{ $p->activity->meeting_point }}@endif
                            </div>
                        </div>
                        <div class="col-span-3 sm:col-span-2 text-right">
                            @if($p->people_count > 1)
                                <span class="tag tag-warm">×{{ $p->people_count }}</span>
                            @else
                                <span class="tag tag-grass">已报名</span>
                            @endif
                        </div>
                    </div>
                </a>
            @endif
        @empty
            <div class="py-8 text-center text-ink-3">
                <p class="font-display italic">还没参加过活动</p>
                <a href="/activities" class="font-mono text-[11px] text-warm uppercase tracking-[0.2em] mt-2 inline-block">去逛逛 →</a>
            </div>
        @endforelse
    </div>
</section>

@endsection
