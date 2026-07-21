@extends('frontend.layout')

@section('title', $route->name . ' · Marker')

@section('content')

{{-- 杂志式 header：编号 + 类型 + 标题 --}}
<section class="border-b border-line-2">
    <div class="max-w-6xl mx-auto px-5 sm:px-8 pt-4 pb-2">
        <div class="flex items-center gap-3 font-mono text-[10px] uppercase tracking-[0.2em] text-ink-3">
            <a href="/" class="hover:text-ink transition-colors">← BACK</a>
            <span class="w-px h-3 bg-line-2"></span>
            <span>N°{{ str_pad($route->id, 3, '0', STR_PAD_LEFT) }}</span>
            <span class="w-px h-3 bg-line-2"></span>
            <span>{{ strtoupper($route->type ?? 'ROUTE') }}</span>
        </div>
    </div>
</section>

<section class="border-b border-line">
    <div class="max-w-6xl mx-auto px-5 sm:px-8 py-10 sm:py-16">
        <div class="grid grid-cols-12 gap-6 sm:gap-12">
            <div class="col-span-12 sm:col-span-8">
                <h1 class="font-display font-medium text-4xl sm:text-6xl leading-[1.05] text-ink">
                    {{ $route->name }}
                </h1>
                @if($route->subtitle)
                    <p class="font-display italic text-xl text-ink-2 mt-4">{{ $route->subtitle }}</p>
                @endif
            </div>

            <div class="col-span-12 sm:col-span-4 sm:pt-12 space-y-4">
                <div>
                    <div class="eyebrow">DISTANCE</div>
                    <div class="font-display text-3xl text-ink mt-1">
                        {{ $route->distance_km ?: '—' }}<span class="text-base text-ink-3 ml-1">KM</span>
                    </div>
                </div>
                <div>
                    <div class="eyebrow">DURATION</div>
                    <div class="font-display text-3xl text-ink mt-1">
                        {{ $route->duration_hours ?: '—' }}<span class="text-base text-ink-3 ml-1">H</span>
                    </div>
                </div>
                @if($route->rating_label)
                    @php $rl = $rating ?? null; @endphp
                    <div>
                        <div class="eyebrow">RATING</div>
                        <div class="font-display text-3xl mt-1" style="color: {{ $rl['color'] ?? '#1A1814' }}">{{ $rl['label'] ?? '' }}</div>
                    </div>
                @endif
            </div>
        </div>
    </div>
</section>

{{-- 封面图 --}}
@php
    $coverUrl = $route->media->first()?->url ?? $route->places->first()?->media->first()?->url ?? null;
@endphp
@if($coverUrl)
<section class="border-b border-line">
    <div class="max-w-6xl mx-auto px-5 sm:px-8 py-6">
        <img src="{{ $coverUrl }}" class="w-full max-h-[60vh] object-cover" alt="{{ $route->name }}">
    </div>
</section>
@endif

{{-- 描述 --}}
@if($route->summary || $route->description)
<section class="border-b border-line">
    <div class="max-w-6xl mx-auto px-5 sm:px-8 py-8 sm:py-12 space-y-6">
        @if($route->summary)
            <div>
                <div class="eyebrow mb-3">§ SUMMARY</div>
                <p class="font-display text-lg sm:text-xl text-ink-2 leading-relaxed">{{ $route->summary }}</p>
            </div>
        @endif
        @if($route->description)
            <div>
                <div class="eyebrow mb-3">§ DETAILS</div>
                <div class="border-l-2 border-warm pl-5 max-w-3xl">
                    <p class="font-display text-base sm:text-lg text-ink leading-relaxed whitespace-pre-line">{{ strip_tags($route->description) }}</p>
                </div>
            </div>
        @endif
    </div>
</section>
@endif

{{-- 沿途地点（杂志式目录） --}}
@if($route->places->isNotEmpty())
<section class="border-b border-line">
    <div class="max-w-6xl mx-auto px-5 sm:px-8 py-8">
        <div class="flex items-baseline justify-between mb-4">
            <div class="eyebrow">§ STOPS</div>
            <span class="font-mono text-[10px] text-ink-3">{{ $route->places->count() }} places</span>
        </div>
        <div class="border-t border-ink">
            @foreach($route->places as $i => $p)
                <a href="/place/{{ $p->id }}" class="block py-4 border-b border-line group hover:bg-paper-2 transition-colors -mx-2 px-2">
                    <div class="grid grid-cols-12 gap-3 items-baseline">
                        <div class="col-span-2 sm:col-span-1 font-mono text-xs text-ink-3 tracking-wider">
                            N°{{ str_pad($i + 1, 2, '0', STR_PAD_LEFT) }}
                        </div>
                        <div class="col-span-7 sm:col-span-8">
                            <div class="font-display text-lg text-ink group-hover:text-warm transition-colors">{{ $p->name }}</div>
                            <div class="font-mono text-[10px] text-ink-3 mt-0.5">
                                {{ \App\Models\Place::PLACE_TYPES[$p->place_type]['label'] ?? '' }} · {{ $p->city ?? '' }}
                            </div>
                        </div>
                        <div class="col-span-3 text-right font-mono text-[10px] uppercase tracking-[0.2em] text-ink-3 group-hover:text-ink">→</div>
                    </div>
                </a>
            @endforeach
        </div>
    </div>
</section>
@endif

{{-- 关联活动 --}}
@if(isset($activities) && $activities->isNotEmpty())
<section class="border-b border-line">
    <div class="max-w-6xl mx-auto px-5 sm:px-8 py-8">
        <div class="flex items-baseline justify-between mb-4">
            <div class="eyebrow">§ EVENTS</div>
            <a href="/activities" class="font-mono text-[10px] text-ink-2 hover:text-ink">所有活动 →</a>
        </div>
        <div class="space-y-px">
            @foreach($activities as $a)
                <a href="/activities/{{ $a->id }}" class="block border border-line p-4 hover:border-ink transition-colors">
                    <div class="font-display text-base text-ink">{{ $a->title }}</div>
                    <div class="font-mono text-[10px] text-ink-3 mt-1">
                        {{ $a->start_at?->format('m/d H:i') }} · {{ $a->joined_participants_count }} 已报名
                    </div>
                </a>
            @endforeach
        </div>
    </div>
</section>
@endif

@if(auth()->check())
<section class="border-b border-line">
    <div class="max-w-6xl mx-auto px-5 sm:px-8 py-6 flex items-center justify-between">
        <span class="font-mono text-[10px] uppercase tracking-[0.2em] text-ink-3">
            {{ $route->like_count ?? 0 }} LIKES
        </span>
        @if(auth()->id() !== $route->user_id)
            <button onclick="likeRoute({{ $route->id }})" class="btn btn-ghost btn-sm">
                + LIKE
            </button>
        @endif
    </div>
</section>
@endif

@endsection

@section('main_class', 'pb-40 sm:pb-32')

@push('scripts')
<script>
async function likeRoute(id) {
    const csrf = document.querySelector('meta[name="csrf-token"]')?.content || '';
    try {
        const r = await fetch(`/api/v1/routes/${id}/like`, {
            method: 'POST',
            headers: { 'X-CSRF-TOKEN': csrf, 'Accept': 'application/json' }
        });
        if (r.ok) location.reload();
    } catch (e) {
        console.error(e);
    }
}
</script>
@endpush

{{-- 发起约伴按钮（编辑感） --}}
<div class="fixed bottom-[72px] sm:bottom-[64px] left-0 right-0 z-40 px-2 sm:px-4 pb-2 sm:pb-3 safe-bottom bg-gradient-to-t from-paper via-paper to-transparent pt-4">
    <div class="max-w-5xl mx-auto">
        <a href="/activities/create?route_id={{ $route->id }}" class="btn btn-warm w-full">
            发起约伴
            <span class="font-mono text-[10px] opacity-70">→</span>
        </a>
    </div>
</div>
