@extends('frontend.layout')

@section('title', '地图浏览 · Marker')

@section('head')
<link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" crossorigin=""/>
<style>
    #map { height: calc(100vh - 130px); }
    .place-popup img { max-width: 200px; border-radius: 8px; }
</style>
@endsection

@section('content')
<div class="relative">
    <div id="map"></div>

    <div class="absolute top-3 left-3 right-3 z-[1000]">
        <div class="bg-white rounded-full shadow-lg flex items-center px-4 py-2">
            <svg class="w-4 h-4 text-gray-400 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"></path></svg>
            <input id="search-input" type="search" placeholder="搜索地点..." class="bg-transparent border-0 outline-none flex-1 text-sm">
        </div>
    </div>

    <div class="absolute bottom-24 right-3 z-[1000] flex flex-col gap-2">
        <button onclick="locateMe()" class="w-10 h-10 bg-white rounded-full shadow-lg flex items-center justify-center">
            <svg class="w-5 h-5 text-emerald-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><circle cx="12" cy="12" r="3" stroke-width="2"/><path d="M12 2v3M12 19v3M2 12h3M19 12h3" stroke-width="2" stroke-linecap="round"/></svg>
        </button>
    </div>
</div>
@endsection

@section('main_class', 'pb-0')

@push('scripts')
<script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js" crossorigin=""></script>
<script>
    const places = @json($places);
    const map = L.map('map', { zoomControl: false }).setView([30.5, 114.3], 6);
    L.tileLayer('https://webrd0{s}.is.autonavi.com/appmaptile?lang=zh_cn&size=1&scale=1&style=8&x={x}&y={y}&z={z}', {
        subdomains: ['1', '2', '3', '4'],
        attribution: '高德地图'
    }).addTo(map);

    const markers = [];
    places.forEach(p => {
        if (!p.latitude || !p.longitude) return;
        const m = L.marker([p.latitude, p.longitude]).addTo(map);
        m.bindPopup(`
            <div class="place-popup">
                <b>${p.name}</b><br>
                <small>${p.city || ''}</small><br>
                <a href="/place/${p.id}" style="color:#10b981">查看详情 →</a>
            </div>
        `);
        markers.push(m);
    });

    if (markers.length > 0) {
        const group = L.featureGroup(markers);
        map.fitBounds(group.getBounds(), { padding: [30, 30], maxZoom: 13 });
    }

    function locateMe() {
        if (!navigator.geolocation) return alert('浏览器不支持定位');
        navigator.geolocation.getCurrentPosition(
            pos => {
                map.setView([pos.coords.latitude, pos.coords.longitude], 14);
                L.circle([pos.coords.latitude, pos.coords.longitude], { radius: 200, color: '#10b981' }).addTo(map);
            },
            err => alert('定位失败：' + err.message)
        );
    }
</script>
@endpush
