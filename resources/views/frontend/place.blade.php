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
{{-- 封面/Header --}}
<section class="relative">
    @if($cover)
        <img src="{{ $cover->url }}" class="w-full h-64 object-cover" alt="{{ $place->name }}">
    @else
        <div class="w-full h-64 bg-gradient-to-br from-emerald-500 to-cyan-500"></div>
    @endif
    <div class="absolute inset-0 bg-gradient-to-t from-black/70 via-transparent to-transparent"></div>
    <a href="{{ url('/') }}" class="absolute top-4 left-4 w-10 h-10 bg-black/40 backdrop-blur rounded-full flex items-center justify-center text-white">
        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"></path></svg>
    </a>

    <div class="absolute bottom-0 left-0 right-0 p-4 text-white">
        <div class="flex items-center gap-2 mb-2">
            @if($place->category)
                <span class="px-2 py-0.5 text-[10px] font-semibold rounded-full" style="background: {{ $place->category->color ?? '#6b7280' }}">
                    {{ $place->category->icon }} {{ $place->category->name }}
                </span>
            @endif
            @if($place->place_type)
                @php $pt = \App\Models\Place::PLACE_TYPES[$place->place_type] ?? null; @endphp
                @if($pt)
                    <span class="px-2 py-0.5 text-[10px] font-medium bg-white/20 backdrop-blur rounded-full">
                        {{ $pt['icon'] }} {{ $pt['label'] }}
                    </span>
                @endif
            @endif
        </div>
        <h1 class="text-2xl font-bold">{{ $place->name }}</h1>
        <p class="text-sm text-white/80 mt-1">
            📍 {{ $place->address ?: $place->city }}
        </p>
    </div>
</section>

{{-- 评分条 --}}
@if($place->rating_label)
    @php $rl = \App\Models\Place::RATING_LABELS[$place->rating_label] ?? null; @endphp
    <section class="px-4 py-3 bg-white border-b border-gray-100">
        <div class="max-w-2xl mx-auto flex items-center justify-between">
            <div class="flex items-center gap-2">
                <span class="px-3 py-1 text-sm font-bold text-white rounded-full" style="background: {{ $rl['color'] }}">
                    {{ $rl['icon'] }} {{ $rl['label'] }}
                </span>
                @if($place->rating)
                    <span class="text-sm text-gray-500">{{ str_repeat('⭐', $place->rating) }}</span>
                @endif
            </div>
            <div class="text-xs text-gray-400">by {{ $place->user?->name ?? '匿名' }}</div>
        </div>
    </section>
@endif

{{-- 描述 --}}
@if($place->description)
    <section class="px-4 py-4 bg-white border-b border-gray-100">
        <div class="max-w-2xl mx-auto text-sm text-gray-700 leading-relaxed whitespace-pre-line">{{ $place->description }}</div>
    </section>
@endif

{{-- 关键信息 --}}
<section class="px-4 py-4 bg-white border-b border-gray-100">
    <div class="max-w-2xl mx-auto grid grid-cols-2 gap-3 text-sm">
        @if($place->has_parking)
            <div class="flex items-center gap-2 p-2 bg-blue-50 rounded-lg">
                <span class="text-lg">🅿️</span>
                <div>
                    <div class="text-xs text-gray-500">停车</div>
                    <div class="font-medium text-gray-800">
                        @if($place->parking_fee_type === 'free')
                            免费
                        @elseif($place->parking_fee)
                            ¥{{ $place->parking_fee }} / {{ \App\Models\Place::PARKING_FEE_TYPES[$place->parking_fee_type] ?? '' }}
                        @else
                            {{ \App\Models\Place::PARKING_FEE_TYPES[$place->parking_fee_type] ?? '可停' }}
                        @endif
                    </div>
                </div>
            </div>
        @endif
        <div class="flex items-center gap-2 p-2 rounded-lg {{ $place->has_ticket ? 'bg-amber-50' : 'bg-emerald-50' }}">
            <span class="text-lg">{{ $place->has_ticket ? '🎫' : '🎉' }}</span>
            <div>
                <div class="text-xs text-gray-500">门票</div>
                <div class="font-medium text-gray-800">
                    @if($place->has_ticket && $place->ticket_price)
                        ¥{{ $place->ticket_price }} / {{ $place->ticket_unit }}
                    @else
                        免费
                    @endif
                </div>
            </div>
        </div>
        @if($place->business_hours)
            <div class="flex items-center gap-2 p-2 bg-gray-50 rounded-lg">
                <span class="text-lg">🕐</span>
                <div>
                    <div class="text-xs text-gray-500">营业</div>
                    <div class="font-medium text-gray-800">{{ $place->business_hours }}</div>
                </div>
            </div>
        @endif
        @if($place->phone)
            <a href="tel:{{ $place->phone }}" class="flex items-center gap-2 p-2 bg-gray-50 rounded-lg">
                <span class="text-lg">📞</span>
                <div>
                    <div class="text-xs text-gray-500">电话</div>
                    <div class="font-medium text-gray-800">{{ $place->phone }}</div>
                </div>
            </a>
        @endif
    </div>
</section>

{{-- 地图 --}}
<section class="px-4 py-4 bg-white border-b border-gray-100">
    <div class="max-w-2xl mx-auto">
        <h2 class="text-base font-semibold text-gray-900 mb-2">📍 位置</h2>
        <div id="map" class="w-full h-48 rounded-xl overflow-hidden border border-gray-200"></div>
        <div class="mt-2 flex items-center gap-2">
            <a href="https://uri.amap.com/marker?position={{ $place->longitude }},{{ $place->latitude }}&name={{ urlencode($place->name) }}&src=Marker&coordinate=gaode&callnative=1"
               class="flex-1 text-center text-sm text-white bg-blue-500 hover:bg-blue-600 py-2 rounded-lg">高德地图导航</a>
            <a href="https://apis.map.qq.com/uri/v1/marker?marker=coord:{{ $place->latitude }},{{ $place->longitude }};title:{{ urlencode($place->name) }};addr:{{ urlencode($place->address) }}&referer=Marker"
               class="flex-1 text-center text-sm text-white bg-emerald-500 hover:bg-emerald-600 py-2 rounded-lg">腾讯地图</a>
            <a href="http://maps.apple.com/?daddr={{ $place->latitude }},{{ $place->longitude }}&dirflg=d"
               class="flex-1 text-center text-sm text-white bg-gray-700 hover:bg-gray-800 py-2 rounded-lg">苹果地图</a>
        </div>
    </div>
</section>

{{-- 相册 --}}
@if($gallery->isNotEmpty())
    <section class="px-4 py-4 bg-white border-b border-gray-100">
        <div class="max-w-2xl mx-auto">
            <h2 class="text-base font-semibold text-gray-900 mb-2">📷 相册 <span class="text-xs text-gray-400 font-normal">({{ $gallery->count() }})</span></h2>
            <div class="grid grid-cols-3 gap-1.5">
                @foreach($gallery as $img)
                    <img src="{{ $img->url }}" class="w-full aspect-square object-cover rounded-lg" loading="lazy" onclick="window.open('{{ $img->url }}')">
                @endforeach
            </div>
        </div>
    </section>
@endif

{{-- 视频 --}}
@if($videos->isNotEmpty())
    <section class="px-4 py-4 bg-white border-b border-gray-100">
        <div class="max-w-2xl mx-auto">
            <h2 class="text-base font-semibold text-gray-900 mb-2">🎬 视频 <span class="text-xs text-gray-400 font-normal">({{ $videos->count() }})</span></h2>
            <div class="space-y-3">
                @foreach($videos as $video)
                    <div class="video-wrap rounded-xl overflow-hidden bg-black">
                        <video src="{{ $video->url }}" controls playsinline preload="metadata"></video>
                    </div>
                @endforeach
            </div>
        </div>
    </section>
@endif

{{-- 标签 --}}
@if($place->tags->isNotEmpty())
    <section class="px-4 py-3 bg-white border-b border-gray-100">
        <div class="max-w-2xl mx-auto flex flex-wrap gap-2">
            @foreach($place->tags as $tag)
                <span class="px-3 py-1 text-xs rounded-full" style="background: {{ $tag->color ?: '#f3f4f6' }}20; color: {{ $tag->color ?: '#6b7280' }}">
                    #{{ $tag->name }}
                </span>
            @endforeach
        </div>
    </section>
@endif

{{-- 笔记 --}}
@if($place->notes->isNotEmpty())
    <section class="px-4 py-4 bg-white">
        <div class="max-w-2xl mx-auto">
            <h2 class="text-base font-semibold text-gray-900 mb-2">📝 关联笔记 <span class="text-xs text-gray-400 font-normal">({{ $place->notes->count() }})</span></h2>
            <div class="space-y-3">
                @foreach($place->notes as $note)
                    <div class="border border-gray-100 rounded-xl p-3">
                        <div class="flex items-center gap-2 mb-1">
                            <span class="text-xs px-2 py-0.5 rounded-full
                                @if($note->source === 'xiaohongshu') bg-rose-100 text-rose-700
                                @elseif($note->source === 'dianping') bg-amber-100 text-amber-700
                                @else bg-gray-100 text-gray-600
                                @endif">
                                @if($note->source === 'xiaohongshu') 📕 小红书
                                @elseif($note->source === 'dianping') 🟡 大众点评
                                @else 📝 手动
                                @endif
                            </span>
                            @if($note->author)
                                <span class="text-xs text-gray-500">{{ $note->author }}</span>
                            @endif
                        </div>
                        <h3 class="font-medium text-sm text-gray-900">{{ $note->title }}</h3>
                        @if($note->content)
                            <p class="text-xs text-gray-600 mt-1 line-clamp-3">{{ $note->content }}</p>
                        @endif
                        @if($note->source_url)
                            <a href="{{ $note->source_url }}" target="_blank" class="text-xs text-emerald-600 mt-1 inline-block">查看原文 →</a>
                        @endif
                    </div>
                @endforeach
            </div>
        </div>
    </section>
@endif

{{-- 关联活动 --}}
@if(isset($activities) && $activities->isNotEmpty())
    <section class="px-4 py-4 bg-white border-t border-gray-100">
        <div class="max-w-2xl mx-auto">
            <h2 class="text-base font-semibold text-gray-900 mb-2">🎒 关联活动 <span class="text-xs text-gray-400 font-normal">({{ $activities->count() }})</span></h2>
            <div class="space-y-2">
                @foreach($activities as $a)
                    <a href="/activities/{{ $a->id }}" class="block p-3 bg-rose-50 hover:bg-rose-100 rounded-xl">
                        <div class="font-medium text-sm text-gray-900 line-clamp-1">{{ $a->title }}</div>
                        <div class="text-xs text-gray-500 mt-0.5">📅 {{ $a->start_at?->format('m-d H:i') }} · 👥 {{ $a->joined_participants_count }}{{ $a->max_participants > 0 ? '/' . $a->max_participants : '' }}</div>
                    </a>
                @endforeach
            </div>
        </div>
    </section>
@endif

{{-- 发起约伴按钮 --}}
<div class="fixed bottom-20 left-0 right-0 z-40 px-4 pb-2 bg-gradient-to-t from-white via-white to-transparent pt-3">
    <div class="max-w-2xl mx-auto flex gap-2">
        <a href="/activities/create?place_id={{ $place->id }}" class="flex-1 py-3 bg-rose-500 hover:bg-rose-600 text-white text-center rounded-2xl text-sm font-bold shadow-lg">
            🎒 发起约伴
        </a>
    </div>
</div>

@endsection

@section('main_class', 'pb-20')

@push('scripts')
<script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js" crossorigin=""></script>
<script>
    const lat = {{ $place->latitude }};
    const lng = {{ $place->longitude }};
    const map = L.map('map', { zoomControl: false }).setView([lat, lng], 15);
    L.tileLayer('https://webrd0{s}.is.autonavi.com/appmaptile?lang=zh_cn&size=1&scale=1&style=8&x={x}&y={y}&z={z}', {
        subdomains: ['1', '2', '3', '4'],
        attribution: '高德地图'
    }).addTo(map);
    L.marker([lat, lng]).addTo(map).bindPopup(`<b>{{ $place->name }}</b><br>{{ $place->address }}`).openPopup();
</script>
@endpush
