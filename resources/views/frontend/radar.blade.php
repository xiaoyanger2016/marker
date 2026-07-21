@extends('frontend.layout')

@section('title', '雷达 · Marker')

@section('head')
<link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" crossorigin=""/>
<style>
    #map { height: calc(100vh - 130px); }
    .place-marker {
        background: #10b981;
        color: white;
        width: 32px; height: 32px;
        border-radius: 50%;
        display: flex; align-items: center; justify-content: center;
        font-size: 12px; font-weight: bold;
        border: 2px solid white;
        box-shadow: 0 2px 6px rgba(0,0,0,0.3);
    }
</style>
@endsection

@section('content')
<div class="relative">
    <div id="map"></div>

    <div class="absolute top-3 left-3 right-3 z-[1000] space-y-2">
        <div class="bg-white rounded-full shadow-lg flex items-center px-4 py-2">
            <span class="text-sm">📡</span>
            <span class="ml-2 text-sm text-gray-700">雷达模式 - 当前位置附近</span>
        </div>
        <div class="bg-white rounded-2xl shadow-lg p-2 flex items-center gap-2 text-xs">
            <span class="text-gray-500">半径</span>
            <input type="range" id="radius" min="500" max="50000" value="5000" step="500" class="flex-1"
                   oninput="document.getElementById('radius-val').textContent = (this.value/1000) + 'km'">
            <span id="radius-val" class="text-emerald-600 font-semibold w-12 text-right">5km</span>
        </div>
    </div>

    <div class="absolute bottom-24 right-3 z-[1000]">
        <button id="locate-btn" onclick="locateMe()" class="px-4 py-2 bg-emerald-500 text-white rounded-full shadow-lg text-sm flex items-center gap-1.5">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><circle cx="12" cy="12" r="3" stroke-width="2"/><path d="M12 2v3M12 19v3M2 12h3M19 12h3" stroke-width="2" stroke-linecap="round"/></svg>
            定位
        </button>
    </div>

    <div id="result-panel" class="absolute bottom-24 left-3 right-3 z-[1000] hidden">
        <div class="bg-white rounded-2xl shadow-lg p-3 max-h-60 overflow-y-auto">
            <div class="text-xs text-gray-500 mb-2" id="result-title">附近 0 个收藏点</div>
            <div id="result-list" class="space-y-2"></div>
        </div>
    </div>
</div>
@endsection

@section('main_class', 'pb-0')

@push('scripts')
<script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js" crossorigin=""></script>
<script>
    const map = L.map('map', { zoomControl: true }).setView([30.5, 114.3], 8);
    L.tileLayer('https://webrd0{s}.is.autonavi.com/appmaptile?lang=zh_cn&size=1&scale=1&style=8&x={x}&y={y}&z={z}', {
        subdomains: ['1', '2', '3', '4'],
        attribution: '高德地图'
    }).addTo(map);

    let meMarker = null;
    let radiusCircle = null;
    let placeMarkers = [];

    async function fetchNearby(lat, lng, radius) {
        const params = new URLSearchParams({ lat, lng, radius, limit: 50 });
        try {
            const r = await fetch(`/api/v1/places/nearby?${params}`, {
                headers: { 'Accept': 'application/json' }
            });
            if (!r.ok) {
                console.warn('not logged in or no data');
                return [];
            }
            const d = await r.json();
            return d.data || [];
        } catch (e) {
            console.error(e);
            return [];
        }
    }

    function renderNearby(places, centerLat, centerLng) {
        placeMarkers.forEach(m => m.remove());
        placeMarkers = [];

        const list = document.getElementById('result-list');
        list.innerHTML = '';
        document.getElementById('result-title').textContent = `附近 ${places.length} 个收藏点`;
        document.getElementById('result-panel').classList.remove('hidden');

        places.forEach(p => {
            const icon = L.divIcon({
                className: '',
                html: `<div class="place-marker">${p.place_type_icon || '📍'}</div>`,
                iconSize: [32, 32],
                iconAnchor: [16, 16]
            });
            const m = L.marker([p.latitude, p.longitude], { icon }).addTo(map);
            m.bindPopup(`
                <b>${p.name}</b><br>
                <small>${p.address || p.city || ''}</small><br>
                ${p.distance_meters ? '📏 ' + (p.distance_meters > 1000 ? (p.distance_meters/1000).toFixed(1) + 'km' : p.distance_meters + 'm') : ''}<br>
                <a href="/place/${p.id}" style="color:#10b981">查看详情 →</a>
            `);
            placeMarkers.push(m);

            const item = document.createElement('a');
            item.href = `/place/${p.id}`;
            item.className = 'block p-2 rounded-lg hover:bg-gray-50';
            item.innerHTML = `
                <div class="flex items-center gap-2">
                    <div class="text-xl">${p.place_type_icon || '📍'}</div>
                    <div class="flex-1 min-w-0">
                        <div class="text-sm font-medium text-gray-900 truncate">${p.name}</div>
                        <div class="text-[10px] text-gray-500">${p.address || p.city || ''}</div>
                    </div>
                    ${p.distance_meters ? `<div class="text-xs text-emerald-600">${(p.distance_meters/1000).toFixed(1)}km</div>` : ''}
                </div>
            `;
            list.appendChild(item);
        });
    }

    async function searchAt(lat, lng) {
        const radius = parseInt(document.getElementById('radius').value);
        const places = await fetchNearby(lat, lng, radius);

        if (radiusCircle) radiusCircle.remove();
        radiusCircle = L.circle([lat, lng], { radius, color: '#10b981', fillOpacity: 0.05, weight: 2 }).addTo(map);

        renderNearby(places, lat, lng);
    }

    function locateMe() {
        const btn = document.getElementById('locate-btn');
        btn.disabled = true;
        btn.innerHTML = '⏳ 定位中...';
        if (!navigator.geolocation) {
            alert('浏览器不支持定位');
            btn.disabled = false;
            btn.innerHTML = '定位';
            return;
        }
        navigator.geolocation.getCurrentPosition(
            async pos => {
                const lat = pos.coords.latitude;
                const lng = pos.coords.longitude;
                if (meMarker) meMarker.remove();
                meMarker = L.marker([lat, lng], {
                    icon: L.divIcon({
                        html: '<div style="width:20px;height:20px;background:#3b82f6;border:3px solid white;border-radius:50%;box-shadow:0 0 0 4px rgba(59,130,246,0.3)"></div>',
                        iconSize: [20, 20],
                        iconAnchor: [10, 10]
                    })
                }).addTo(map).bindPopup('你在这里').openPopup();
                map.setView([lat, lng], 13);
                await searchAt(lat, lng);
                btn.disabled = false;
                btn.innerHTML = '<svg class="w-4 h-4 inline" fill="none" stroke="currentColor" viewBox="0 0 24 24"><circle cx="12" cy="12" r="3" stroke-width="2"/></svg> 重新定位';
            },
            err => {
                alert('定位失败：' + err.message + '。请允许浏览器位置权限');
                btn.disabled = false;
                btn.innerHTML = '定位';
            },
            { enableHighAccuracy: true, timeout: 10000 }
        );
    }

    document.getElementById('radius').addEventListener('change', () => {
        if (meMarker) {
            const ll = meMarker.getLatLng();
            searchAt(ll.lat, ll.lng);
        }
    });

    // 首次进入尝试定位
    setTimeout(() => {
        if (navigator.geolocation) {
            locateMe();
        }
    }, 500);
</script>
@endpush
