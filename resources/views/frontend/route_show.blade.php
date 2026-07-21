@extends('frontend.layout')

@section('title', $route->name . ' · Marker')

@section('head')
<link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" crossorigin=""/>
<style>
    .place-card {
        display: flex;
        gap: 0.75rem;
        padding: 0.75rem;
        background: white;
        border-radius: 0.75rem;
        border: 1px solid #f3f4f6;
        margin-bottom: 0.5rem;
    }
    .order-badge {
        flex-shrink: 0;
        width: 28px;
        height: 28px;
        border-radius: 50%;
        display: flex;
        align-items: center;
        justify-content: center;
        color: white;
        font-weight: bold;
        font-size: 12px;
    }
</style>
@endsection

@section('content')
{{-- 封面 --}}
<section class="relative">
    @if($route->media->first())
        <img src="{{ $route->media->first()->url }}" class="w-full h-64 object-cover" alt="{{ $route->name }}">
    @elseif($route->places->first() && $route->places->first()->media->first())
        <img src="{{ $route->places->first()->media->first()->url }}" class="w-full h-64 object-cover" alt="{{ $route->name }}">
    @else
        <div class="w-full h-64" style="background: linear-gradient(135deg, {{ $type['color'] ?? '#10b981' }}, {{ $type['color'] ?? '#10b981' }}aa);"></div>
    @endif
    <div class="absolute inset-0 bg-gradient-to-t from-black/80 via-black/30 to-transparent"></div>
    <a href="{{ url('/') }}" class="absolute top-4 left-4 w-10 h-10 bg-black/40 backdrop-blur rounded-full flex items-center justify-center text-white">
        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"></path></svg>
    </a>

    <div class="absolute bottom-0 left-0 right-0 p-4 text-white">
        <span class="inline-block px-2.5 py-1 text-xs font-semibold rounded-full mb-2" style="background: {{ $type['color'] ?? '#10b981' }}">
            {{ $type['icon'] ?? '📍' }} {{ $type['label'] ?? $route->type }}
        </span>
        <h1 class="text-2xl font-bold">{{ $route->name }}</h1>
        @if($route->subtitle)
            <p class="text-sm text-white/80 mt-1">{{ $route->subtitle }}</p>
        @endif
    </div>
</section>

{{-- 评分条 + 操作 --}}
<section class="px-4 py-3 bg-white border-b border-gray-100">
    <div class="max-w-2xl mx-auto flex items-center justify-between">
        <div class="flex items-center gap-2">
            @if($rating)
                <span class="px-3 py-1 text-sm font-bold text-white rounded-full" style="background: {{ $rating['color'] }}">
                    {{ $rating['icon'] }} {{ $rating['label'] }}
                </span>
            @endif
        </div>
        <button class="text-sm text-rose-500 border border-rose-200 px-3 py-1 rounded-full hover:bg-rose-50"
                onclick="likeRoute({{ $route->id }})">
            ❤️ <span id="like-count">{{ $route->like_count }}</span>
        </button>
    </div>
</section>

{{-- 关键信息 --}}
<section class="px-4 py-3 bg-white border-b border-gray-100">
    <div class="max-w-2xl mx-auto grid grid-cols-4 gap-2 text-center text-xs">
        @if($route->distance_km)
            <div class="p-2 bg-gray-50 rounded-lg">
                <div class="text-base">🛣️</div>
                <div class="font-bold text-gray-800 mt-0.5">{{ $route->distance_km }}km</div>
                <div class="text-gray-500 text-[10px]">总里程</div>
            </div>
        @endif
        @if($route->duration_hours)
            <div class="p-2 bg-gray-50 rounded-lg">
                <div class="text-base">⏱️</div>
                <div class="font-bold text-gray-800 mt-0.5">{{ $route->duration_hours }}h</div>
                <div class="text-gray-500 text-[10px]">时长</div>
            </div>
        @endif
        <div class="p-2 bg-gray-50 rounded-lg">
            <div class="text-base">📍</div>
            <div class="font-bold text-gray-800 mt-0.5">{{ $route->places->count() }}</div>
            <div class="text-gray-500 text-[10px]">地点</div>
        </div>
        <div class="p-2 bg-gray-50 rounded-lg">
            <div class="text-base">👁️</div>
            <div class="font-bold text-gray-800 mt-0.5">{{ $route->view_count }}</div>
            <div class="text-gray-500 text-[10px]">浏览</div>
        </div>
    </div>
</section>

{{-- 简介 --}}
@if($route->summary)
    <section class="px-4 py-3 bg-white border-b border-gray-100">
        <div class="max-w-2xl mx-auto text-sm text-gray-700 leading-relaxed">{{ $route->summary }}</div>
    </section>
@endif

{{-- 线路地图 --}}
@if($route->places->count() > 0)
    <section class="px-4 py-4 bg-white border-b border-gray-100">
        <div class="max-w-2xl mx-auto">
            <h2 class="text-base font-semibold text-gray-900 mb-2">🗺️ 线路地图</h2>
            <div id="map" class="w-full h-64 rounded-xl overflow-hidden border border-gray-200"></div>
        </div>
    </section>
@endif

{{-- 沿途地点 --}}
@if($route->places->count() > 0)
    <section class="px-4 py-4 bg-white border-b border-gray-100">
        <div class="max-w-2xl mx-auto">
            <h2 class="text-base font-semibold text-gray-900 mb-3">
                {{ $route->requires_order ? '🛣️ 沿途地点' : '🗻 线路点位' }}
                <span class="text-xs text-gray-400 font-normal">({{ $route->places->count() }})</span>
            </h2>
            @foreach($route->places as $i => $place)
                <a href="{{ url('/place/' . $place->id) }}" class="place-card hover:shadow-md transition-shadow">
                    @if($route->requires_order)
                        <div class="order-badge" style="background: {{ $type['color'] ?? '#10b981' }}">{{ $place->pivot->order ?: ($i + 1) }}</div>
                    @else
                        <div class="order-badge bg-gray-400">·</div>
                    @endif
                    @php $cover = $place->media->firstWhere('is_cover', true) ?? $place->media->first(); @endphp
                    @if($cover)
                        <img src="{{ $cover->url }}" class="w-16 h-16 rounded-lg object-cover flex-shrink-0">
                    @else
                        <div class="w-16 h-16 rounded-lg bg-gray-200 flex-shrink-0 flex items-center justify-center text-xl">📍</div>
                    @endif
                    <div class="flex-1 min-w-0">
                        <h3 class="font-medium text-sm text-gray-900 line-clamp-1">{{ $place->name }}</h3>
                        <p class="text-xs text-gray-500 mt-0.5 line-clamp-1">{{ $place->address ?: $place->city }}</p>
                        <div class="flex items-center gap-2 mt-1 text-[10px] text-gray-400">
                            @if($place->city) <span>📍 {{ $place->city }}</span> @endif
                            @if($place->has_parking) <span>🅿️ 可停</span> @endif
                            @if($place->has_ticket) <span>🎫 ¥{{ $place->ticket_price }}</span> @else <span>🎉 免费</span> @endif
                        </div>
                    </div>
                </a>
            @endforeach
        </div>
    </section>
@endif

{{-- 详细描述 --}}
@if($route->description)
    <section class="px-4 py-4 bg-white border-b border-gray-100">
        <div class="max-w-2xl mx-auto">
            <h2 class="text-base font-semibold text-gray-900 mb-2">📝 详细说明</h2>
            <div class="text-sm text-gray-700 leading-relaxed whitespace-pre-line">{{ $route->description }}</div>
        </div>
    </section>
@endif

{{-- 装备 --}}
@if($route->gear_checklist && count($route->gear_checklist) > 0)
    <section class="px-4 py-3 bg-white border-b border-gray-100">
        <div class="max-w-2xl mx-auto">
            <h3 class="text-sm font-semibold text-gray-900 mb-2">🎒 装备清单</h3>
            <div class="flex flex-wrap gap-1.5">
                @foreach($route->gear_checklist as $g)
                    <span class="px-2 py-1 text-xs bg-gray-100 rounded">✓ {{ $g }}</span>
                @endforeach
            </div>
        </div>
    </section>
@endif

{{-- 安全提示 --}}
@if($route->safety_notes && count($route->safety_notes) > 0)
    <section class="px-4 py-3 bg-white">
        <div class="max-w-2xl mx-auto">
            <h3 class="text-sm font-semibold text-gray-900 mb-2">⚠️ 安全提示</h3>
            <ul class="space-y-1 text-xs text-gray-700">
                @foreach($route->safety_notes as $s)
                    <li>· {{ $s }}</li>
                @endforeach
            </ul>
        </div>
    </section>
@endif

@endsection

@section('main_class', 'pb-20')

@push('scripts')
<script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js" crossorigin=""></script>
@if($route->places->count() > 0)
<script>
    const points = [
        @foreach($route->places as $place)
            [{{ $place->latitude }}, {{ $place->longitude }}, '{{ addslashes($place->name) }}']{{ $loop->last ? '' : ',' }}
        @endforeach
    ];

    const map = L.map('map', { zoomControl: true }).setView(points[0], 11);
    L.tileLayer('https://webrd0{s}.is.autonavi.com/appmaptile?lang=zh_cn&size=1&scale=1&style=8&x={x}&y={y}&z={z}', {
        subdomains: ['1', '2', '3', '4'],
        attribution: '高德地图'
    }).addTo(map);

    const latlngs = [];
    const isOrdered = @json($route->requires_order);
    points.forEach(([lat, lng, name], i) => {
        const color = @json($type['color'] ?? '#10b981');
        const label = isOrdered ? (i + 1) : '·';
        const icon = L.divIcon({
            className: 'route-marker',
            html: `<div style="background:${color};color:white;width:28px;height:28px;border-radius:50%;display:flex;align-items:center;justify-content:center;font-weight:bold;border:2px solid white;box-shadow:0 2px 4px rgba(0,0,0,0.3)">${label}</div>`,
            iconSize: [28, 28],
            iconAnchor: [14, 14]
        });
        L.marker([lat, lng], { icon }).addTo(map).bindPopup(`<b>${i+1}. ${name}</b>`);
        latlngs.push([lat, lng]);
    });

    if (latlngs.length > 1) {
        const line = L.polyline(latlngs, { color: @json($type['color'] ?? '#10b981'), weight: 4, opacity: 0.7, dashArray: '6, 8' }).addTo(map);
        map.fitBounds(line.getBounds(), { padding: [30, 30] });
    }

    async function likeRoute(id) {
        const csrf = document.querySelector('meta[name="csrf-token"]')?.content || '';
        try {
            const r = await fetch(`/api/v1/routes/${id}/like`, {
                method: 'POST',
                headers: { 'X-CSRF-TOKEN': csrf, 'Accept': 'application/json' }
            });
            if (r.ok) {
                const d = await r.json();
                document.getElementById('like-count').textContent = d.like_count;
            }
        } catch (e) {
            console.error(e);
        }
    }
</script>
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
        <a href="/activities/create?route_id={{ $route->id }}" class="flex-1 py-3 bg-rose-500 hover:bg-rose-600 text-white text-center rounded-2xl text-sm font-bold shadow-lg">
            🎒 发起约伴
        </a>
    </div>
</div>

@endpush
