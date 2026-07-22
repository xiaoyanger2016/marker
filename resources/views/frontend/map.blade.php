@extends('frontend.layout')

@section('title', '地图浏览 · Marker')

@section('head')
<link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" crossorigin=""/>
<style>
    #map { height: calc(100vh - 56px - 88px); }
    @media (min-width: 640px) { #map { height: calc(100vh - 56px - 88px); } }
    .place-popup img { max-width: 200px; border-radius: 0; }
    .leaflet-popup-content-wrapper { border-radius: 0; }
    .leaflet-popup-content { font-family: 'JetBrains Mono', monospace; font-size: 11px; margin: 12px 14px; }
    .leaflet-popup-tip-container { display: none; }
    /* 自定义 marker (编辑感, 8 type 颜色) */
    .mkr { display: flex; align-items: center; justify-content: center; border: 2px solid #fff; box-shadow: 0 2px 6px rgba(0,0,0,0.25); font-family: 'JetBrains Mono', monospace; font-size: 9px; font-weight: 600; color: #fff; width: 26px; height: 26px; transform: rotate(45deg); }
    .mkr > span { transform: rotate(-45deg); }
    /* 类型图例 */
    .legend-chip { display: inline-flex; align-items: center; gap: 4px; padding: 4px 8px; border: 1px solid var(--ink-3, #847E72); font-family: 'JetBrains Mono', monospace; font-size: 10px; cursor: pointer; user-select: none; background: var(--paper, #F2EDE2); }
    .legend-chip.active { background: var(--ink, #1A1814); color: var(--paper, #F2EDE2); border-color: var(--ink, #1A1814); }
    .legend-dot { width: 8px; height: 8px; }
</style>
@endsection

@section('content')
<div class="relative">
    <div id="map"></div>

    {{-- 顶部搜索 + 类型图例 --}}
    <div class="absolute top-3 left-3 right-3 z-[1000] pointer-events-none">
        <div class="bg-paper border border-ink px-3 sm:px-4 py-2 flex items-center gap-2 pointer-events-auto shadow-sm">
            <svg class="w-4 h-4 text-ink-3 flex-shrink-0" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5">
                <circle cx="11" cy="11" r="7"/><path d="M21 21l-4.5-4.5"/>
            </svg>
            <input id="search-input" type="search" placeholder="搜索：地点/内容...  ({{ count($contents) }})" class="bg-transparent border-0 outline-none flex-1 font-mono text-xs sm:text-sm text-ink placeholder:text-ink-3">
        </div>

        {{-- 8 type 筛选 chips --}}
        <div class="mt-2 flex items-stretch gap-1 overflow-x-auto no-scrollbar pointer-events-auto pb-1">
            <a href="{{ url('/map') }}" class="legend-chip flex-shrink-0 {{ empty($filterType) ? 'active' : '' }}">All · {{ count($contents) + count($places) }}</a>
            @foreach($types as $t)
                <a href="{{ url('/map?type=' . $t['key']) }}" class="legend-chip flex-shrink-0 {{ ($filterType ?? '') === $t['key'] ? 'active' : '' }}">
                    <span class="legend-dot" style="background: {{ $t['color'] }}"></span>{{ $t['icon'] }} {{ $t['label'] }}
                </a>
            @endforeach
        </div>
    </div>

    {{-- 右下角控件 --}}
    <div class="absolute bottom-24 right-3 z-[1000] flex flex-col gap-2">
        <button onclick="locateMe()" title="定位我" class="w-10 h-10 bg-paper border border-ink flex items-center justify-center hover:bg-ink hover:text-paper transition-colors">
            <svg class="w-5 h-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5">
                <circle cx="12" cy="12" r="3"/><path d="M12 2v3M12 19v3M2 12h3M19 12h3" stroke-linecap="round"/>
            </svg>
        </button>
    </div>
</div>
@endsection

@section('main_class', 'pb-0')

@push('scripts')
<script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js" crossorigin=""></script>
<script>
    const contents = @json($contents);
    const places   = @json($places);
    const map = L.map('map', { zoomControl: false }).setView([30.5, 114.3], 6);
    L.control.zoom({ position: 'bottomleft' }).addTo(map);
    L.tileLayer('https://webrd0{s}.is.autonavi.com/appmaptile?lang=zh_cn&size=1&scale=1&style=8&x={x}&y={y}&z={z}', {
        subdomains: ['1', '2', '3', '4'],
        attribution: '高德地图 · Marker'
    }).addTo(map);

    const allMarkers = [];
    const bounds = [];

    // 8 大类内容 markers (菱形彩色)
    contents.forEach(c => {
        if (!c.lat || !c.lng) return;
        const html = `<div class="mkr" style="background:${c.type_color || '#114B5F'}"><span>${(c.type_icon || 'N°').replace('N°','')}</span></div>`;
        const icon = L.divIcon({ html, className: '', iconSize: [26, 26], iconAnchor: [13, 13] });
        const m = L.marker([c.lat, c.lng], { icon }).addTo(map);
        const popup = `
            <div style="min-width:180px">
                <div style="font-family:'Inter',sans-serif;font-size:13px;font-weight:600;color:#1A1814;margin-bottom:4px">${c.title}</div>
                <div style="display:flex;align-items:center;gap:4px;margin-bottom:6px">
                    <span style="background:${c.type_color};color:#fff;padding:1px 5px;font-size:9px">${c.type_icon} ${c.type_label}</span>
                    ${c.rating ? `<span style="border:1px solid #1A1814;padding:1px 4px;font-size:9px">${c.rating}</span>` : ''}
                </div>
                ${c.cover ? `<img src="${c.cover}" style="width:100%;max-width:200px;height:90px;object-fit:cover;margin:4px 0" />` : ''}
                <div style="color:#847E72;font-size:10px;margin-top:4px">${c.city || ''}${c.user ? ' · @' + c.user : ''}</div>
                <a href="${c.url}" style="display:inline-block;margin-top:6px;color:#1A1814;border-bottom:1px solid #1A1814;font-size:10px">READ MORE →</a>
            </div>
        `;
        m.bindPopup(popup, { offset: [0, -10] });
        m._filterType = c.type;
        allMarkers.push(m);
        bounds.push([c.lat, c.lng]);
    });

    // 单独 places (小圆点)
    places.forEach(p => {
        if (!p.lat || !p.lng) return;
        const icon = L.divIcon({
            html: `<div style="background:#847E72;width:10px;height:10px;border:2px solid #fff;box-shadow:0 1px 3px rgba(0,0,0,0.3)"></div>`,
            className: '', iconSize: [10, 10], iconAnchor: [5, 5]
        });
        const m = L.marker([p.lat, p.lng], { icon }).addTo(map);
        m.bindPopup(`<b>${p.title}</b><br><small>${p.city || ''}</small><br><a href="${p.url}">查看 →</a>`);
        m._filterType = p.type || 'place';
        allMarkers.push(m);
        bounds.push([p.lat, p.lng]);
    });

    if (bounds.length > 0) {
        map.fitBounds(bounds, { padding: [60, 60], maxZoom: 12 });
    }

    function locateMe() {
        if (!navigator.geolocation) return alert('浏览器不支持定位');
        navigator.geolocation.getCurrentPosition(
            pos => {
                map.setView([pos.coords.latitude, pos.coords.longitude], 14);
                L.circle([pos.coords.latitude, pos.coords.longitude], { radius: 200, color: '#114B5F', fillOpacity: 0.1 }).addTo(map);
            },
            err => alert('定位失败：' + err.message),
            { enableHighAccuracy: true, timeout: 10000 }
        );
    }

    // 搜索过滤
    document.getElementById('search-input')?.addEventListener('input', (e) => {
        const q = e.target.value.toLowerCase().trim();
        allMarkers.forEach(m => {
            if (!q) { m.addTo(map); return; }
            const txt = (m._filterType + ' ' + (m.getPopup()?.getContent() || '')).toLowerCase();
            if (txt.includes(q)) m.addTo(map); else map.removeLayer(m);
        });
    });
</script>
<style>
.no-scrollbar::-webkit-scrollbar { display: none; }
.no-scrollbar { -ms-overflow-style: none; scrollbar-width: none; }
</style>
@endpush
