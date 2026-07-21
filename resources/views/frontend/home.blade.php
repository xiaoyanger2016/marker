@extends('frontend.layout')

@section('title', 'Marker · 我的收藏地图')

@section('content')
{{-- 顶部 banner --}}
<section class="px-4 pt-3 pb-5 bg-gradient-to-br from-emerald-500 via-teal-500 to-cyan-500 text-white">
    <div class="max-w-2xl mx-auto">
        <h1 class="text-2xl font-bold mb-1">{{ __('ui.greeting') }} 🚗</h1>
        <p class="text-sm text-white/90">{{ __('ui.tagline') }}</p>
        <div class="mt-3 flex items-center gap-2 bg-white/20 backdrop-blur rounded-full px-4 py-2.5">
            <svg class="w-4 h-4 text-white/80" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"></path></svg>
            <input type="search" placeholder="{{ __('ui.search_ph') }}" class="bg-transparent border-0 outline-none text-white placeholder-white/70 flex-1 text-sm" id="search-input">
        </div>
    </div>
</section>

{{-- 类型导航（横向滚动） --}}
<section class="bg-white border-b border-gray-100">
    <div class="overflow-x-auto scrollbar-hide">
        <div class="flex items-stretch gap-1 px-3 py-3 min-w-max">
            @foreach($types as $t)
                <a href="{{ url('/type/' . $t['key']) }}" class="group flex flex-col items-center w-[68px] flex-shrink-0">
                    <div class="w-12 h-12 rounded-2xl flex items-center justify-center text-2xl shadow-sm group-hover:scale-110 active:scale-95 transition-transform"
                         style="background: linear-gradient(135deg, {{ $t['color'] }}, {{ $t['color'] }}cc);">
                        <span>{{ $t['icon'] }}</span>
                    </div>
                    <div class="mt-1 text-[11px] font-medium text-gray-700 text-center leading-tight whitespace-nowrap">{{ $t['label'] }}</div>
                </a>
            @endforeach
        </div>
    </div>
</section>

{{-- 6 类型精选推荐（每类 top 3）--}}
@foreach($recommendations as $rec)
    @if(!empty($rec['items']) && count($rec['items']) > 0)
        <section class="py-3">
            <div class="px-4 max-w-2xl mx-auto flex items-baseline justify-between mb-2">
                <h2 class="text-lg font-bold text-gray-900">
                    <span style="margin-right: 4px;">{{ $rec['type']['icon'] }}</span>
                    {{ $rec['type']['label'] }}
                </h2>
                <a href="{{ url('/type/' . $rec['type']['key']) }}" class="text-xs text-emerald-600">查看更多 →</a>
            </div>
            <div class="masonry max-w-2xl mx-auto">
                @foreach($rec['items'] as $item)
                    @include('frontend.partials.card', ['item' => $item])
                @endforeach
            </div>
        </section>
    @endif
@endforeach

{{-- 全部内容（无限滚动瀑布流）--}}
<section class="py-3 border-t-4 border-gray-100" id="feed-section">
    <div class="px-4 max-w-2xl mx-auto flex items-baseline justify-between mb-3">
        <h2 class="text-lg font-bold text-gray-900">🌟 全部内容</h2>
        <div class="flex items-center gap-2 text-xs">
            <button data-tab="all" class="feed-tab px-2.5 py-1 rounded-full bg-emerald-500 text-white font-medium">全部</button>
            <button data-tab="place" class="feed-tab px-2.5 py-1 rounded-full text-gray-600">单点</button>
            <button data-tab="route" class="feed-tab px-2.5 py-1 rounded-full text-gray-600">线路</button>
        </div>
    </div>

    <div id="feed-grid" class="masonry max-w-2xl mx-auto"></div>

    <div id="feed-loading" class="py-8 text-center text-gray-400 text-sm hidden">
        <div class="inline-block animate-spin w-5 h-5 border-2 border-emerald-500 border-t-transparent rounded-full"></div>
        <div class="mt-2">加载中...</div>
    </div>
    <div id="feed-empty" class="py-12 text-center text-gray-400 text-sm hidden">
        <div class="text-4xl mb-2">🎈</div>
        <div>暂无更多内容</div>
    </div>
    <div id="feed-end" class="py-8 text-center text-gray-300 text-xs hidden">— 已经到底啦 —</div>
</section>

@endsection

@push('scripts')
<script>
(function() {
    const grid = document.getElementById('feed-grid');
    const loading = document.getElementById('feed-loading');
    const empty = document.getElementById('feed-empty');
    const end = document.getElementById('feed-end');

    let currentTab = 'all';
    let page = { all: 1, place: 1, route: 1 };
    let hasMore = { all: true, place: true, route: true };
    let loading_ = false;
    let observer = null;

    function setActiveTab(tab) {
        currentTab = tab;
        document.querySelectorAll('.feed-tab').forEach(btn => {
            const isActive = btn.dataset.tab === tab;
            btn.classList.toggle('bg-emerald-500', isActive);
            btn.classList.toggle('text-white', isActive);
            btn.classList.toggle('font-medium', isActive);
            btn.classList.toggle('text-gray-600', !isActive);
        });
        grid.innerHTML = '';
        page[tab] = 1;
        hasMore[tab] = true;
        loadMore();
    }

    async function fetchPage(tab, p) {
        const promises = [];
        if (tab === 'all' || tab === 'place') {
            promises.push(
                fetch(`/api/v1/places?page=${p}&per_page=12`, { headers: { 'Accept': 'application/json' }})
                    .then(r => r.json()).catch(() => ({ data: [] }))
            );
        } else {
            promises.push(Promise.resolve({ data: [] }));
        }
        if (tab === 'all' || tab === 'route') {
            promises.push(
                fetch(`/api/v1/routes?page=${p}&per_page=6`, { headers: { 'Accept': 'application/json' }})
                    .then(r => r.json()).catch(() => ({ data: [] }))
            );
        } else {
            promises.push(Promise.resolve({ data: [] }));
        }
        const [placesData, routesData] = await Promise.all(promises);
        return { places: placesData.data || [], routes: routesData.data || [] };
    }

    function escapeHtml(s) {
        return (s || '').replace(/[&<>"']/g, c => ({'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":'&#39;'}[c]));
    }

    function gradient(id) {
        const gs = [['#fda4af','#fb923c'],['#86efac','#22d3ee'],['#a78bfa','#f472b6'],['#fcd34d','#fb7185'],['#5eead4','#818cf8'],['#fca5a5','#a855f7']];
        return gs[id % gs.length];
    }
    function ratio(id) {
        const rs = ['aspect-[3/4]','aspect-square','aspect-[4/5]','aspect-[3/4]','aspect-[4/3]','aspect-[2/3]'];
        return rs[id % rs.length];
    }

    function placeCard(p) {
        const g = gradient(p.id);
        const r = ratio(p.id);
        const icon = p.place_type_icon || '📍';
        const typeLabel = p.place_type_label || '';
        let ratingBadge = '';
        if (p.rating_label && p.rating_meta) {
            ratingBadge = `<div class="absolute top-2 right-2"><span class="px-2 py-0.5 text-[10px] font-bold text-white rounded-full shadow" style="background:${p.rating_meta.color}">${p.rating_meta.icon} ${p.rating_meta.label}</span></div>`;
        }
        const typeBadge = typeLabel ? `<div class="absolute top-2 left-2"><span class="px-2 py-0.5 text-[10px] font-medium text-gray-700 bg-white/90 rounded-full">${typeLabel}</span></div>` : '';
        return `
            <a href="/place/${p.id}" class="masonry-item group block bg-white rounded-2xl overflow-hidden shadow-sm hover:shadow-md transition-shadow">
                <div class="${r} relative overflow-hidden" style="background: linear-gradient(135deg, ${g[0]}, ${g[1]});">
                    <div class="w-full h-full flex items-center justify-center">
                        <div class="text-7xl sm:text-8xl opacity-90 group-hover:scale-110 transition-transform duration-300">${icon}</div>
                    </div>
                    ${typeBadge}
                    ${ratingBadge}
                </div>
                <div class="p-3">
                    <h3 class="font-semibold text-sm text-gray-900 line-clamp-1">${escapeHtml(p.name)}</h3>
                    ${p.description ? `<p class="text-xs text-gray-500 mt-0.5 line-clamp-2">${escapeHtml(p.description.replace(/<[^>]+>/g,'').slice(0,60))}</p>` : ''}
                    <div class="mt-2 flex items-center justify-between text-[10px] text-gray-400">
                        <span>${escapeHtml(p.city || '—')}</span>
                    </div>
                </div>
            </a>`;
    }

    function routeCard(r) {
        const g = gradient(r.id);
        const ratioClass = ratio(r.id);
        const icon = r.type_icon || '🚗';
        const color = r.type_color || '#10b981';
        const typeLabel = r.type_label || '';
        const typeBadge = `<div class="absolute top-2 left-2"><span class="px-2 py-0.5 text-[10px] font-semibold text-white rounded-full shadow" style="background:${color}">${icon} ${typeLabel}</span></div>`;
        const ratingBadge = r.rating_meta ? `<div class="absolute top-2 right-2"><span class="px-2 py-0.5 text-[10px] font-bold text-white rounded-full shadow" style="background:${r.rating_meta.color}">${r.rating_meta.icon} ${r.rating_meta.label}</span></div>` : '';
        return `
            <a href="/route/${r.id}" class="masonry-item group block bg-white rounded-2xl overflow-hidden shadow-sm hover:shadow-md transition-shadow">
                <div class="${ratioClass} relative overflow-hidden" style="background: linear-gradient(135deg, ${color}, ${color}aa);">
                    <div class="w-full h-full flex items-center justify-center">
                        <div class="text-7xl sm:text-8xl opacity-90 group-hover:scale-110 transition-transform duration-300">${icon}</div>
                    </div>
                    ${typeBadge}
                    ${ratingBadge}
                    <div class="absolute bottom-0 left-0 right-0 p-2 bg-gradient-to-t from-black/70 to-transparent">
                        <div class="flex items-center gap-2 text-white text-[10px]">
                            ${r.distance_km ? `<span>🛣️ ${r.distance_km}km</span>` : ''}
                            ${r.duration_hours ? `<span>⏱️ ${r.duration_hours}h</span>` : ''}
                            ${r.view_count > 0 ? `<span>👁️ ${r.view_count}</span>` : ''}
                        </div>
                    </div>
                </div>
                <div class="p-3">
                    <h3 class="font-semibold text-sm text-gray-900 line-clamp-1">${escapeHtml(r.name)}</h3>
                    ${r.subtitle ? `<p class="text-xs text-gray-500 mt-0.5 line-clamp-1">${escapeHtml(r.subtitle)}</p>` : (r.summary ? `<p class="text-xs text-gray-500 mt-0.5 line-clamp-2">${escapeHtml(r.summary)}</p>` : '')}
                    <div class="mt-2 flex items-center justify-between text-[10px] text-gray-400">
                        <span>${escapeHtml(r.city || '—')}</span>
                        ${r.like_count > 0 ? `<span>❤️ ${r.like_count}</span>` : ''}
                    </div>
                </div>
            </a>`;
    }

    async function loadMore() {
        if (loading_ || !hasMore[currentTab]) return;
        loading_ = true;
        loading.classList.remove('hidden');
        empty.classList.add('hidden');
        end.classList.add('hidden');

        try {
            const { places, routes } = await fetchPage(currentTab, page[currentTab]);
            const totalNew = places.length + routes.length;

            if (totalNew === 0) {
                hasMore[currentTab] = false;
                if (grid.children.length === 0) empty.classList.remove('hidden');
                else end.classList.remove('hidden');
                return;
            }

            // 混排：按 created_at 降序混合
            const merged = [
                ...places.map(p => ({ type: 'place', created_at: p.created_at, data: p })),
                ...routes.map(r => ({ type: 'route', created_at: r.created_at, data: r })),
            ].sort((a, b) => new Date(b.created_at) - new Date(a.created_at));

            const html = merged.map(m => m.type === 'place' ? placeCard(m.data) : routeCard(m.data)).join('');
            grid.insertAdjacentHTML('beforeend', html);

            if (totalNew < 12) {
                hasMore[currentTab] = false;
                end.classList.remove('hidden');
            } else {
                page[currentTab]++;
            }
        } catch (e) {
            console.error(e);
            hasMore[currentTab] = false;
        } finally {
            loading_ = false;
            loading.classList.add('hidden');
        }
    }

    // Tab 切换
    document.querySelectorAll('.feed-tab').forEach(btn => {
        btn.addEventListener('click', () => setActiveTab(btn.dataset.tab));
    });

    // IntersectionObserver 无限滚动
    observer = new IntersectionObserver(entries => {
        if (entries.some(e => e.isIntersecting)) {
            loadMore();
        }
    }, { rootMargin: '200px' });
    observer.observe(loading);

    // 首屏加载
    setTimeout(loadMore, 100);
})();
</script>
@endpush
