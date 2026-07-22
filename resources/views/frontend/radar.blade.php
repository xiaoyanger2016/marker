@extends('frontend.layout')

@section('title', '雷达 · Marker')

@section('head')
<link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" crossorigin=""/>
<style>
    #map { height: calc(100vh - 56px - 88px); }
    .pulse {
        width: 16px; height: 16px;
        background: #114B5F;
        border: 3px solid #fff;
        border-radius: 50%;
        box-shadow: 0 0 0 0 rgba(17, 75, 95, 0.7);
        animation: pulse 1.8s infinite;
    }
    @keyframes pulse {
        0% { box-shadow: 0 0 0 0 rgba(17, 75, 95, 0.7); }
        70% { box-shadow: 0 0 0 18px rgba(17, 75, 95, 0); }
        100% { box-shadow: 0 0 0 0 rgba(17, 75, 95, 0); }
    }
    .leaflet-popup-content-wrapper { border-radius: 0; }
    .leaflet-popup-tip-container { display: none; }
    .leaflet-popup-content { font-family: 'JetBrains Mono', monospace; font-size: 11px; margin: 12px 14px; }
</style>
@endsection

@section('content')
<div class="relative">
    <div id="map"></div>

    {{-- 顶部：状态 + 半径 --}}
    <div class="absolute top-3 left-3 right-3 z-[1000] pointer-events-none">
        <div class="bg-paper border border-ink px-3 sm:px-4 py-2 pointer-events-auto flex items-center gap-2 shadow-sm">
            <div class="pulse flex-shrink-0"></div>
            <div class="flex-1 min-w-0">
                <div class="font-display text-sm text-ink leading-none">{{ __('ui.radar_title') }}</div>
                <div class="font-mono text-[10px] text-ink-3 mt-0.5" id="loc-status">{{ __('ui.radar_idle') }}</div>
            </div>
            <button onclick="locateMe()" id="locate-btn" class="font-mono text-[10px] uppercase tracking-[0.15em] border border-ink px-2 py-1 hover:bg-ink hover:text-paper transition-colors">
                {{ __('ui.radar_locate') }}
            </button>
        </div>

        {{-- 半径 --}}
        <div class="mt-2 bg-paper border border-ink px-3 sm:px-4 py-2 pointer-events-auto shadow-sm">
            <div class="flex items-center gap-2">
                <span class="font-mono text-[10px] uppercase tracking-[0.15em] text-ink-3 flex-shrink-0">{{ __('ui.radar_radius') }}</span>
                <input type="range" id="radius" min="500" max="50000" value="5000" step="500" class="flex-1 accent-ink"
                       oninput="document.getElementById('radius-val').textContent = (this.value/1000).toFixed(1) + 'km'">
                <span id="radius-val" class="font-mono text-[10px] text-warm w-12 text-right">5.0km</span>
            </div>
        </div>
    </div>

    {{-- 结果面板 --}}
    <div id="result-panel" class="absolute bottom-24 left-3 right-3 z-[1000] hidden">
        <div class="bg-paper border border-ink shadow-sm max-h-72 overflow-y-auto">
            <div class="px-3 py-2 border-b border-line flex items-center justify-between">
                <div class="font-mono text-[10px] uppercase tracking-[0.15em] text-ink-2" id="result-title">{{ __('ui.radar_nearby') }} 0</div>
                <button onclick="document.getElementById('result-panel').classList.add('hidden')" class="font-mono text-[10px] text-ink-3 hover:text-ink">CLOSE ×</button>
            </div>
            <div id="result-list" class="divide-y divide-line"></div>
        </div>
    </div>

    {{-- 首次访问定位请求 --}}
    <div id="permission-tip" class="absolute top-32 left-3 right-3 z-[1000] bg-paper border border-ink p-5 max-w-sm mx-auto shadow-md hidden">
        <div class="eyebrow text-warm mb-2">{{ __('ui.radar_permission_title') }}</div>
        <p class="font-display italic text-base text-ink-2 leading-relaxed mb-4">{{ __('ui.radar_permission_desc') }}</p>
        <button onclick="locateMe()" class="w-full font-mono text-[10px] uppercase tracking-[0.15em] py-2 bg-ink text-paper hover:bg-warm transition-colors">
            {{ __('ui.radar_permission_cta') }}
        </button>
        <button onclick="document.getElementById('permission-tip').classList.add('hidden')" class="block w-full mt-2 font-mono text-[10px] text-ink-3 hover:text-ink">
            {{ __('ui.cancel') }}
        </button>
    </div>
</div>
@endsection

@section('main_class', 'pb-0')

@push('scripts')
<script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js" crossorigin=""></script>
<script>
    const map = L.map('map', { zoomControl: false }).setView([30.5, 114.3], 8);
    L.control.zoom({ position: 'bottomleft' }).addTo(map);
    L.tileLayer('https://webrd0{s}.is.autonavi.com/appmaptile?lang=zh_cn&size=1&scale=1&style=8&x={x}&y={y}&z={z}', {
        subdomains: ['1', '2', '3', '4'],
        attribution: '高德地图 · Marker'
    }).addTo(map);

    let meMarker = null;
    let radiusCircle = null;
    let placeMarkers = [];

    async function fetchNearby(lat, lng, radius) {
        const params = new URLSearchParams({ lat, lng, radius: Math.round(radius), limit: 30 });
        try {
            const r = await fetch(`/api/radar/nearby?${params}`, { headers: { 'Accept': 'application/json' } });
            if (!r.ok) return [];
            const d = await r.json();
            return d.data || [];
        } catch (e) { console.error(e); return []; }
    }

    function renderNearby(places, centerLat, centerLng) {
        placeMarkers.forEach(m => m.remove());
        placeMarkers = [];
        const list = document.getElementById('result-list');
        list.innerHTML = '';
        document.getElementById('result-title').textContent = `{{ __('ui.radar_nearby') }} ${places.length}`;
        document.getElementById('result-panel').classList.remove('hidden');

        places.forEach(p => {
            const icon = L.divIcon({
                className: '',
                html: `<div style="background:#114B5F;width:14px;height:14px;border:2px solid #fff;box-shadow:0 1px 4px rgba(0,0,0,0.3)"></div>`,
                iconSize: [14, 14], iconAnchor: [7, 7]
            });
            const m = L.marker([p.latitude, p.longitude], { icon }).addTo(map);
            const distTxt = p.distance_meters > 1000 ? (p.distance_meters/1000).toFixed(1) + 'km' : p.distance_meters + 'm';
            m.bindPopup(`
                <div style="min-width:160px">
                    <div style="font-family:'Inter',sans-serif;font-size:13px;font-weight:600;color:#1A1814;margin-bottom:4px">${p.name}</div>
                    <div style="color:#847E72;font-size:10px;margin-bottom:4px">${p.address || p.city || ''}</div>
                    <div style="color:#114B5F;font-size:10px;font-weight:500">📏 ${distTxt}</div>
                    <a href="${p.url}" style="display:inline-block;margin-top:6px;color:#1A1814;border-bottom:1px solid #1A1814;font-size:10px">查看 →</a>
                </div>
            `);
            placeMarkers.push(m);

            const item = document.createElement('a');
            item.href = p.url;
            item.className = 'block px-3 py-2.5 hover:bg-paper-2 transition-colors';
            item.innerHTML = `
                <div class="flex items-center justify-between gap-3">
                    <div class="flex-1 min-w-0">
                        <div class="font-display text-sm text-ink truncate">${p.name}</div>
                        <div class="font-mono text-[10px] text-ink-3 mt-0.5 truncate">${p.address || p.city || ''}</div>
                    </div>
                    <div class="font-mono text-[10px] text-warm flex-shrink-0">${distTxt}</div>
                </div>
            `;
            list.appendChild(item);
        });
    }

    async function searchAt(lat, lng) {
        const radius = parseInt(document.getElementById('radius').value);
        const places = await fetchNearby(lat, lng, radius);
        if (radiusCircle) radiusCircle.remove();
        radiusCircle = L.circle([lat, lng], { radius, color: '#114B5F', fillOpacity: 0.05, weight: 1.5 }).addTo(map);
        renderNearby(places, lat, lng);
    }

    function locateMe() {
        document.getElementById('permission-tip')?.classList.add('hidden');
        document.getElementById('loc-status').textContent = '{{ __('ui.radar_locating') }}';

        if (!navigator.geolocation) {
            document.getElementById('loc-status').textContent = '{{ __('ui.radar_no_geo') }}';
            return;
        }

        navigator.geolocation.getCurrentPosition(
            async pos => {
                const lat = pos.coords.latitude;
                const lng = pos.coords.longitude;
                if (meMarker) meMarker.remove();
                meMarker = L.marker([lat, lng], {
                    icon: L.divIcon({
                        html: '<div class="pulse"></div>',
                        iconSize: [16, 16], iconAnchor: [8, 8]
                    })
                }).addTo(map).bindPopup('{{ __('ui.radar_you_here') }}');
                map.setView([lat, lng], 13);
                await searchAt(lat, lng);
                document.getElementById('loc-status').textContent = '✓ {{ __('ui.radar_located') }}';
            },
            err => {
                document.getElementById('loc-status').textContent = '{{ __('ui.radar_denied') }}';
                document.getElementById('permission-tip')?.classList.remove('hidden');
            },
            { enableHighAccuracy: true, timeout: 15000, maximumAge: 60000 }
        );
    }

    document.getElementById('radius').addEventListener('change', () => {
        if (meMarker) {
            const ll = meMarker.getLatLng();
            searchAt(ll.lat, ll.lng);
        }
    });

    // Auto-show permission tip on first visit
    if (!navigator.permissions) {
        document.getElementById('permission-tip')?.classList.remove('hidden');
    } else {
        navigator.permissions.query({ name: 'geolocation' }).then(p => {
            if (p.state === 'prompt') document.getElementById('permission-tip')?.classList.remove('hidden');
        }).catch(() => {
            document.getElementById('permission-tip')?.classList.remove('hidden');
        });
    }
</script>
@endpush
