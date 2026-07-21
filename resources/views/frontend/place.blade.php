@extends('frontend.layout')

@section('title', $place->name . ' · Marker')

@section('head')
<link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" crossorigin=""/>
<style>
    .gallery-image {
        width: 100%;
        height: 240px;
        object-fit: cover;
        display: block;
    }
    .video-wrap {
        position: relative;
        padding-bottom: 56.25%;
        height: 0;
    }
    .video-wrap video {
        position: absolute;
        top: 0; left: 0;
        width: 100%;
        height: 100%;
    }
</style>
@endsection

@section('content')

@php
    $pt = \App\Models\Place::PLACE_TYPES[$place->place_type] ?? null;
    $ptColor = $pt['color'] ?? '#4A4640';
    $ptLabel = $pt['label'] ?? '';
    $ptIcon = $pt['icon'] ?? 'N°00';
    $rl = $place->rating_label ? (\App\Models\Place::RATING_LABELS[$place->rating_label] ?? null) : null;
@endphp

{{-- =================================================================
   01 · 杂志式 HERO：编辑感排版
   不再是「封面图 + 居中标题」，改成「左编号 + 标签 + 大字标题 + meta」
   ================================================================= --}}
<section class="border-b border-line-2">
    <div class="max-w-5xl mx-auto px-5 sm:px-8 py-8 sm:py-12">
        <div class="flex items-center gap-3 font-mono text-[10px] uppercase tracking-[0.2em] text-ink-3 mb-5">
            <a href="{{ url('/') }}" class="hover:text-ink transition-colors">← BACK</a>
            <span class="w-px h-3 bg-line-2"></span>
            <span>PLACE</span>
            <span class="w-px h-3 bg-line-2"></span>
            <span>N°{{ str_pad($place->id, 4, '0', STR_PAD_LEFT) }}</span>
        </div>

        @if($ptLabel)
            <div class="mb-3">
                <span class="tag" style="background: {{ $ptColor }}; color: var(--color-paper);">{{ $ptIcon }} · {{ $ptLabel }}</span>
            </div>
        @endif

        <h1 class="font-display font-medium text-3xl sm:text-5xl leading-[1.1] text-ink max-w-3xl">
            {{ $place->name }}
        </h1>

        @if($place->address)
            <p class="font-mono text-[11px] uppercase tracking-[0.15em] text-ink-2 mt-4 flex items-center gap-2">
                <span class="bullet-warm"></span>
                {{ $place->address }}
            </p>
        @elseif($place->city)
            <p class="font-mono text-[11px] uppercase tracking-[0.15em] text-ink-2 mt-4">
                <span class="bullet-warm"></span>
                {{ $place->city }}
            </p>
        @endif

        @if($rl && !empty($rl['label']))
            <div class="mt-5 flex items-center gap-3">
                <span class="font-mono text-[10px] uppercase tracking-[0.2em] text-ink-3">REVIEW</span>
                <span class="font-mono text-[11px] uppercase tracking-[0.15em] px-3 py-1.5 text-paper" style="background: {{ $rl['color'] }};">{{ $rl['label'] }}</span>
            </div>
        @endif

        <div class="mt-5 pt-5 border-t border-line flex items-center justify-between font-mono text-[10px] uppercase tracking-[0.15em] text-ink-3">
            <span>BY {{ $place->user?->name ?? '匿名' }}</span>
            @if($place->view_count)
                <span>{{ $place->view_count }} views</span>
            @endif
        </div>
    </div>
</section>

{{-- =================================================================
   02 · 封面图（有图时显示）
   ================================================================= --}}
@if($cover)
<section class="border-b border-line">
    <div class="max-w-5xl mx-auto">
        <img src="{{ $cover->url }}" class="w-full h-64 sm:h-96 object-cover" alt="{{ $place->name }}">
    </div>
</section>
@endif

{{-- =================================================================
   03 · 关键信息（编辑感：4 列 + mono 标签 + 衬线数值）
   ================================================================= --}}
<section class="border-b border-line">
    <div class="max-w-5xl mx-auto px-5 sm:px-8 py-8 sm:py-10">
        <div class="grid grid-cols-2 sm:grid-cols-4 gap-6 sm:gap-8">
            @if($place->has_parking)
                <div>
                    <div class="eyebrow">停车 · PARK</div>
                    <div class="font-display text-lg text-ink mt-2">
                        @if($place->parking_fee_type === 'free')
                            免费
                        @elseif($place->parking_fee)
                            ¥{{ $place->parking_fee }} / {{ \App\Models\Place::PARKING_FEE_TYPES[$place->parking_fee_type] ?? '' }}
                        @else
                            {{ \App\Models\Place::PARKING_FEE_TYPES[$place->parking_fee_type] ?? '可停' }}
                        @endif
                    </div>
                </div>
            @endif
            <div>
                <div class="eyebrow">门票 · TICKET</div>
                <div class="font-display text-lg text-ink mt-2">
                    @if($place->has_ticket && $place->ticket_price)
                        ¥{{ $place->ticket_price }} / {{ $place->ticket_unit }}
                    @else
                        免费
                    @endif
                </div>
            </div>
            @if($place->business_hours)
                <div>
                    <div class="eyebrow">营业 · HOURS</div>
                    <div class="font-display text-base text-ink mt-2">{{ $place->business_hours }}</div>
                </div>
            @endif
            @if($place->phone)
                <div>
                    <div class="eyebrow">电话 · TEL</div>
                    <a href="tel:{{ $place->phone }}" class="font-display text-base text-ink link mt-2 inline-block">{{ $place->phone }}</a>
                </div>
            @endif
        </div>
    </div>
</section>

{{-- =================================================================
   04 · 描述（衬线大字 + 暖色左线）
   ================================================================= --}}
@if($place->description)
<section class="border-b border-line">
    <div class="max-w-3xl mx-auto px-5 sm:px-8 py-10 sm:py-14">
        <div class="eyebrow mb-3">§ DESCRIPTION</div>
        <div class="border-l-2 border-warm pl-5">
            <p class="font-display text-lg sm:text-xl leading-relaxed text-ink whitespace-pre-line">{{ $place->description }}</p>
        </div>
    </div>
</section>
@endif

{{-- =================================================================
   05 · 位置地图
   ================================================================= --}}
<section class="border-b border-line">
    <div class="max-w-5xl mx-auto px-5 sm:px-8 py-8 sm:py-12">
        <div class="flex items-baseline justify-between mb-4">
            <span class="eyebrow">§ LOCATION</span>
            @if($place->latitude)
                <span class="font-mono text-[10px] text-ink-3">{{ number_format($place->latitude, 4) }}°N · {{ number_format($place->longitude, 4) }}°E</span>
            @endif
        </div>
        <div id="map" class="w-full h-64 sm:h-80 border border-line"></div>
        <div class="mt-3 grid grid-cols-3 gap-2 font-mono text-[10px] uppercase tracking-[0.15em]">
            <a href="https://uri.amap.com/marker?position={{ $place->longitude }},{{ $place->latitude }}&name={{ urlencode($place->name) }}&src=Marker&coordinate=gaode&callnative=1"
               class="text-center py-2.5 border border-line-2 text-ink-2 hover:border-ink hover:text-ink transition-colors">高德地图</a>
            <a href="https://apis.map.qq.com/uri/v1/marker?marker=coord:{{ $place->latitude }},{{ $place->longitude }};title:{{ urlencode($place->name) }};addr:{{ urlencode($place->address) }}&referer=Marker"
               class="text-center py-2.5 border border-line-2 text-ink-2 hover:border-ink hover:text-ink transition-colors">腾讯地图</a>
            <a href="http://maps.apple.com/?daddr={{ $place->latitude }},{{ $place->longitude }}&dirflg=d"
               class="text-center py-2.5 border border-line-2 text-ink-2 hover:border-ink hover:text-ink transition-colors">苹果地图</a>
        </div>
    </div>
</section>

{{-- =================================================================
   06 · 相册（编辑感：3 列 + 数字编号）
   ================================================================= --}}
@if($gallery->isNotEmpty())
<section class="border-b border-line">
    <div class="max-w-5xl mx-auto px-5 sm:px-8 py-8 sm:py-12">
        <div class="flex items-baseline justify-between mb-4">
            <span class="eyebrow">§ GALLERY</span>
            <span class="font-mono text-[10px] text-ink-3">{{ $gallery->count() }} photos</span>
        </div>
        <div class="grid grid-cols-3 gap-1">
            @foreach($gallery as $i => $img)
                <a href="{{ $img->url }}" target="_blank" class="block relative group">
                    <img src="{{ $img->url }}" class="w-full aspect-square object-cover" loading="lazy">
                    <span class="absolute top-1 left-1 font-mono text-[9px] text-paper/80 mix-blend-difference">{{ str_pad($i + 1, 2, '0', STR_PAD_LEFT) }}</span>
                </a>
            @endforeach
        </div>
    </div>
</section>
@endif

{{-- =================================================================
   07 · 视频
   ================================================================= --}}
@if($videos->isNotEmpty())
<section class="border-b border-line">
    <div class="max-w-5xl mx-auto px-5 sm:px-8 py-8 sm:py-12">
        <div class="eyebrow mb-4">§ VIDEO</div>
        <div class="space-y-4">
            @foreach($videos as $video)
                <div class="video-wrap border border-line overflow-hidden bg-ink">
                    <video src="{{ $video->url }}" controls playsinline preload="metadata"></video>
                </div>
            @endforeach
        </div>
    </div>
</section>
@endif

{{-- =================================================================
   08 · 标签
   ================================================================= --}}
@if($place->tags->isNotEmpty())
<section class="border-b border-line">
    <div class="max-w-5xl mx-auto px-5 sm:px-8 py-6">
        <div class="eyebrow mb-3">§ TAGS</div>
        <div class="flex flex-wrap gap-2">
            @foreach($place->tags as $tag)
                <span class="font-mono text-[10px] uppercase tracking-[0.15em] px-2.5 py-1 border border-line-2 text-ink-2 hover:border-ink hover:text-ink transition-colors">#{{ $tag->name }}</span>
            @endforeach
        </div>
    </div>
</section>
@endif

{{-- =================================================================
   09 · 笔记
   ================================================================= --}}
@if($place->notes->isNotEmpty())
<section class="border-b border-line">
    <div class="max-w-3xl mx-auto px-5 sm:px-8 py-8 sm:py-12">
        <div class="eyebrow mb-4">§ NOTES · {{ $place->notes->count() }}</div>
        <div class="space-y-3">
            @foreach($place->notes as $note)
                <div class="border border-line p-4">
                    <div class="flex items-center gap-2 mb-2">
                        <span class="tag tag-ink">
                            @if($note->source === 'xiaohongshu') 小红书
                            @elseif($note->source === 'dianping') 大众点评
                            @else 自记
                            @endif
                        </span>
                        @if($note->author)
                            <span class="font-mono text-[10px] text-ink-3">— {{ $note->author }}</span>
                        @endif
                    </div>
                    <h3 class="font-display text-lg text-ink">{{ $note->title }}</h3>
                    @if($note->content)
                        <p class="text-sm text-ink-2 mt-2 leading-relaxed line-clamp-4">{{ $note->content }}</p>
                    @endif
                    @if($note->source_url)
                        <a href="{{ $note->source_url }}" target="_blank" class="link font-mono text-[10px] uppercase tracking-[0.15em] mt-3 inline-block">查看原文 →</a>
                    @endif
                </div>
            @endforeach
        </div>
    </div>
</section>
@endif

{{-- =================================================================
   10 · 关联活动
   ================================================================= --}}
@if(isset($activities) && $activities->isNotEmpty())
<section class="border-b border-line">
    <div class="max-w-5xl mx-auto px-5 sm:px-8 py-8 sm:py-12">
        <div class="eyebrow mb-4">§ LINKED ACTIVITIES · {{ $activities->count() }}</div>
        <div class="space-y-2">
            @foreach($activities as $a)
                <a href="/activities/{{ $a->id }}" class="block border border-line p-4 hover:border-ink transition-colors">
                    <div class="font-display text-base text-ink">{{ $a->title }}</div>
                    <div class="mt-1 flex items-center gap-3 font-mono text-[10px] uppercase tracking-[0.15em] text-ink-3">
                        <span>{{ $a->start_at?->format('Y/m/d H:i') }}</span>
                        <span class="w-px h-3 bg-line-2"></span>
                        <span>{{ $a->joined_participants_count }}{{ $a->max_participants > 0 ? '/' . $a->max_participants : '' }} 人</span>
                    </div>
                </a>
            @endforeach
        </div>
    </div>
</section>
@endif

{{-- =================================================================
   11 · 发起约伴按钮（编辑感底注）
   ================================================================= --}}
<div class="fixed bottom-[72px] sm:bottom-[64px] left-0 right-0 z-40 px-2 sm:px-4 pb-2 sm:pb-3 safe-bottom bg-gradient-to-t from-paper via-paper to-transparent pt-4">
    @auth
        <div class="max-w-5xl mx-auto">
            <a href="/activities/create?place_id={{ $place->id }}" class="btn btn-warm w-full">
                <span>发起约伴</span>
                <span class="font-mono text-[10px] opacity-70">→</span>
            </a>
        </div>
    @else
        <div class="max-w-5xl mx-auto">
            <a href="/login" class="btn btn-primary w-full">登录后发起</a>
        </div>
    @endauth
</div>

@endsection

@section('main_class', 'pb-40 sm:pb-32')

@push('scripts')
<script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js" crossorigin=""></script>
<script>
    @if($place->latitude && $place->longitude)
    const lat = {{ $place->latitude }};
    const lng = {{ $place->longitude }};
    const map = L.map('map', { zoomControl: false }).setView([lat, lng], 15);
    L.tileLayer('https://webrd0{s}.is.autonavi.com/appmaptile?lang=zh_cn&size=1&scale=1&style=8&x={x}&y={y}&z={z}', {
        subdomains: ['1', '2', '3', '4'],
        attribution: '高德地图'
    }).addTo(map);
    L.marker([lat, lng]).addTo(map).bindPopup(`<b>{{ $place->name }}</b><br>{{ $place->address }}`).openPopup();
    @endif
</script>
@endpush
