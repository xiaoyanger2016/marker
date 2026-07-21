@extends('frontend.layout')

@section('title', 'Marker · 公路旅行私人地图')

@section('content')

{{-- =================================================================
   01 · MASTHEAD
   编辑感 masthead：编号 + 期刊感 + 真实文案（不"Hi 开车人"）
   ================================================================= --}}
<section class="border-b border-line-2">
    <div class="max-w-6xl mx-auto px-5 sm:px-8 pt-6 pb-2">
        {{-- 期刊标头：期数 / 日期 / 坐标 --}}
        <div class="flex items-center justify-between text-[10px] font-mono uppercase tracking-[0.2em] text-ink-3 mb-6">
            <div class="flex items-center gap-3">
                <span>VOL.01</span>
                <span class="w-px h-3 bg-line-2"></span>
                <span>{{ now()->format('Y/m/d') }}</span>
            </div>
            <div class="hidden sm:flex items-center gap-3">
                <span>30°15'N</span>
                <span class="w-px h-3 bg-line-2"></span>
                <span>120°10'E</span>
                <span class="w-px h-3 bg-line-2"></span>
                <span>EDITED BY YOU</span>
            </div>
        </div>
    </div>
</section>

{{-- =================================================================
   02 · HERO 标题（大衬线 + 不对称布局）
   标题左 7/12，引语右 4/12 错位下行
   ================================================================= --}}
<section class="border-b border-line">
    <div class="max-w-6xl mx-auto px-5 sm:px-8 py-10 sm:py-16">
        <div class="grid grid-cols-12 gap-4 sm:gap-8">
            <div class="col-span-12 sm:col-span-8">
                <h1 class="font-display font-medium text-[2.5rem] sm:text-[4rem] leading-[1.05] tracking-tight text-ink">
                    一份<br>
                    只属于你的<br>
                    <span class="serif-italic text-warm">公路地图志</span>
                </h1>
            </div>
            <div class="col-span-12 sm:col-span-4 sm:pt-12 flex flex-col justify-end">
                <p class="text-sm leading-relaxed text-ink-2 border-l border-line-2 pl-4">
                    记录那些不期而遇的路、值得绕道一公里的店、和凌晨三点的星空。
                    这里没有算法推荐，只有你和朋友走过的痕迹。
                </p>
                <div class="mt-4 flex items-center gap-2 text-[10px] font-mono uppercase tracking-[0.2em] text-ink-3">
                    <span class="bullet-warm"></span>
                    <span>39 places · 6 routes · 2 readers</span>
                </div>
            </div>
        </div>
    </div>
</section>

{{-- =================================================================
   03 · SEARCH（编辑感搜索框，胶囊 + mono 占位）
   ================================================================= --}}
<section class="border-b border-line">
    <div class="max-w-6xl mx-auto px-5 sm:px-8 py-6">
        <div class="flex items-center gap-3 border border-ink px-4 py-2.5 bg-paper">
            <svg class="w-4 h-4 text-ink-3" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5">
                <circle cx="11" cy="11" r="7"/>
                <path d="M21 21l-4.5-4.5"/>
            </svg>
            <input type="search" placeholder="搜索：杭州周边 / 露营 / 川菜 / 日出机位 ..."
                   class="bg-transparent border-0 outline-none flex-1 font-mono text-sm placeholder:text-ink-3 text-ink" id="search-input">
            <span class="hidden sm:inline font-mono text-[10px] text-ink-3 border border-line-2 px-1.5 py-0.5">⌘ K</span>
        </div>
    </div>
</section>

{{-- =================================================================
   04 · TYPE INDEX（编辑感索引：8 个类型水平排列 + 数字编号）
   不用 8 个圆角色块，改成报纸目录样式
   ================================================================= --}}
<section class="border-b border-line">
    <div class="max-w-6xl mx-auto px-5 sm:px-8 py-6">
        <div class="flex items-baseline justify-between mb-4">
            <span class="eyebrow">§ 01 — 类型索引</span>
            <span class="font-mono text-[10px] text-ink-3">8 types</span>
        </div>
        <div class="overflow-x-auto scrollbar-hide -mx-5 sm:-mx-8">
            <div class="flex px-5 sm:px-8 min-w-max">
                @php
                    $typeIndex = [
                        ['01', 'self_drive',     '自驾线路',     'self-driving route', '#114B5F'],
                        ['02', 'play_water',     '玩水点',       'where the water is', '#0D3A4A'],
                        ['03', 'hiking',         '徒步线路',     'on foot',             '#1A1814'],
                        ['04', 'sup',            '桨板点',       'stand up',            '#2D5F3F'],
                        ['05', 'photo',          '拍照点',       'frame the shot',      '#847E72'],
                        ['06', 'food',           '美食探店',     'eat like a local',    '#C45626'],
                        ['07', 'camping',        '露营点',       'sleep under stars',   '#1A3A3A'],
                        ['08', 'sunrise_sunset', '日出日落',     'the light shows up',  '#A1461E'],
                    ];
                @endphp
                @foreach($typeIndex as $i => [$no, $key, $label, $en, $color])
                    <a href="{{ url('/type/' . $key) }}"
                       class="group flex flex-col items-start min-w-[120px] px-4 py-3 border-r border-line {{ $loop->last ? 'border-r-0' : '' }}">
                        <span class="font-mono text-[10px] tracking-wider text-ink-3 mb-2">N°{{ $no }}</span>
                        <span class="font-display text-xl text-ink group-hover:text-warm transition-colors leading-none">{{ $label }}</span>
                        <span class="font-mono text-[10px] text-ink-3 mt-1.5 italic">{{ $en }}</span>
                    </a>
                @endforeach
            </div>
        </div>
    </div>
</section>

{{-- =================================================================
   05 · EDITORIAL PICKS（编辑精选 - 不对称网格）
   第一行：一个大卡（占 2 列）+ 一个小卡（占 1 列）
   后续：瀑布流
   ================================================================= --}}
@php
    // 把 8 个类型 top items 拍平
    $picks = collect($recommendations)->filter(fn ($r) => ! empty($r['items']))->values();
    $hero = $picks->first();
    $heroItem = $hero['items'][0] ?? null;
    $side = $picks->skip(1)->take(2);
@endphp
@if($heroItem)
<section class="border-b border-line">
    <div class="max-w-6xl mx-auto px-5 sm:px-8 py-8 sm:py-12">
        <div class="flex items-baseline justify-between mb-6">
            <span class="eyebrow">§ 02 — 本期精选</span>
            <span class="font-mono text-[10px] text-ink-3">curated · by you</span>
        </div>

        <div class="grid grid-cols-12 gap-4 sm:gap-6">
            {{-- 大卡 7 列 --}}
            <a href="{{ url('/place/' . $heroItem['id']) }}" class="col-span-12 sm:col-span-7 group block">
                <div class="aspect-[4/5] sm:aspect-[5/6] overflow-hidden border border-line">
                    @php
                        $g = ['#114B5F', '#1A3A3A', '#0D3A4A', '#1A1814'];
                        $gi = $heroItem['id'] % 4;
                    @endphp
                    <div class="w-full h-full relative" style="background: linear-gradient(135deg, {{ $g[$gi] }} 0%, #1A1814 100%);">
                        <div class="absolute inset-0 flex items-center justify-center">
                            <span class="font-display text-[8rem] sm:text-[12rem] text-paper/15 leading-none select-none">N°{{ str_pad($heroItem['id'], 2, '0', STR_PAD_LEFT) }}</span>
                        </div>
                        <div class="absolute top-3 left-3 flex items-center gap-2">
                            <span class="font-mono text-[10px] uppercase tracking-[0.2em] text-paper/80">N°01</span>
                            <span class="w-px h-3 bg-paper/30"></span>
                            <span class="font-mono text-[10px] uppercase tracking-[0.2em] text-paper/80">{{ $hero['type']['label'] }}</span>
                        </div>
                        @if(! empty($heroItem['rating_label']))
                            @php $rl = \App\Models\Place::RATING_LABELS[$heroItem['rating_label']] ?? null; @endphp
                            @if($rl)
                                <div class="absolute top-3 right-3 font-mono text-[10px] uppercase tracking-[0.2em] px-2 py-1 border border-paper/40 text-paper">
                                    {{ $rl['label'] }}
                                </div>
                            @endif
                        @endif
                        <div class="absolute bottom-0 left-0 right-0 p-5 sm:p-6 bg-gradient-to-t from-ink/80 to-transparent">
                            <h2 class="font-display text-2xl sm:text-4xl text-paper leading-tight mb-1">{{ $heroItem['name'] }}</h2>
                            @if(! empty($heroItem['description']))
                                <p class="font-sans text-sm text-paper/80 line-clamp-2 max-w-md">{{ \Illuminate\Support\Str::limit(strip_tags($heroItem['description']), 80) }}</p>
                            @endif
                        </div>
                    </div>
                </div>
                <div class="mt-3 flex items-center justify-between font-mono text-[10px] text-ink-3">
                    <span>{{ $heroItem['city'] ?? '—' }}</span>
                    <span class="text-warm underline underline-offset-4">READ MORE →</span>
                </div>
            </a>

            {{-- 右侧 2 小卡 5 列 --}}
            <div class="col-span-12 sm:col-span-5 flex flex-col gap-4 sm:gap-6">
                @foreach($side as $i => $rec)
                    @php $item = $rec['items'][0] ?? null; @endphp
                    @if($item)
                        <a href="{{ url('/place/' . $item['id']) }}" class="group flex-1 flex gap-3 sm:gap-4 border border-line p-3 hover:border-ink transition-colors">
                            <div class="w-20 sm:w-28 flex-shrink-0 aspect-square relative" style="background: linear-gradient(135deg, {{ ['#114B5F', '#2D5F3F', '#C45626'][$i % 3] }} 0%, #1A1814 100%);">
                                <div class="absolute inset-0 flex items-center justify-center text-paper/25 font-display text-3xl select-none">
                                    N°{{ str_pad($item['id'], 2, '0', STR_PAD_LEFT) }}
                                </div>
                                <div class="absolute top-1 left-1 font-mono text-[8px] text-paper/70">N°0{{ $i + 2 }}</div>
                            </div>
                            <div class="flex-1 min-w-0 flex flex-col">
                                <span class="eyebrow">{{ $rec['type']['label'] }}</span>
                                <h3 class="font-display text-lg sm:text-xl text-ink mt-1 line-clamp-2 leading-tight">{{ $item['name'] }}</h3>
                                <div class="mt-auto pt-2 flex items-center justify-between font-mono text-[10px] text-ink-3">
                                    <span>{{ $item['city'] ?? '—' }}</span>
                                    <span class="text-ink-2 group-hover:text-warm">→</span>
                                </div>
                            </div>
                        </a>
                    @endif
                @endforeach
            </div>
        </div>
    </div>
</section>
@endif

{{-- =================================================================
   06 · ALL FEED（瀑布流 + 标签 tab：编辑感 chip）
   ================================================================= --}}
<section class="border-b border-line-2" id="feed-section">
    <div class="max-w-6xl mx-auto px-5 sm:px-8 py-8 sm:py-12">
        <div class="flex items-baseline justify-between mb-6">
            <span class="eyebrow">§ 03 — 全部内容</span>
            <span class="font-mono text-[10px] text-ink-3" id="feed-counter">loading…</span>
        </div>

        {{-- 编辑感 tab：边框 + mono 数字 --}}
        <div class="flex items-center gap-0 border border-line mb-6 max-w-md">
            <button data-tab="all" class="feed-tab flex-1 py-2 text-center font-mono text-[11px] uppercase tracking-[0.15em] bg-ink text-paper">
                <span class="hidden sm:inline">All </span>全部
            </button>
            <button data-tab="place" class="feed-tab flex-1 py-2 text-center font-mono text-[11px] uppercase tracking-[0.15em] text-ink-2 hover:text-ink">
                <span class="hidden sm:inline">Place </span>单点
            </button>
            <button data-tab="route" class="feed-tab flex-1 py-2 text-center font-mono text-[11px] uppercase tracking-[0.15em] text-ink-2 hover:text-ink">
                <span class="hidden sm:inline">Route </span>线路
            </button>
        </div>

        <div id="feed-grid" class="masonry"></div>

        <div id="feed-loading" class="py-8 text-center font-mono text-[10px] uppercase tracking-[0.2em] text-ink-3 hidden">
            loading…
        </div>
        <div id="feed-empty" class="py-16 text-center text-ink-3 hidden">
            <div class="font-display text-3xl text-ink-2 mb-2">还没有内容</div>
            <p class="text-sm">发起你的第一次约伴，或者告诉我们哪里值得去</p>
        </div>
        <div id="feed-end" class="py-8 text-center font-mono text-[10px] uppercase tracking-[0.2em] text-ink-3 hidden">
            · END ·
        </div>
    </div>
</section>

{{-- =================================================================
   07 · FOOTER NOTE（编辑感底注）
   ================================================================= --}}
<footer class="border-t border-line-2">
    <div class="max-w-6xl mx-auto px-5 sm:px-8 py-8 flex flex-col sm:flex-row items-start sm:items-center justify-between gap-3">
        <div class="font-mono text-[10px] uppercase tracking-[0.2em] text-ink-3">
            Marker · 公路杂志 · Vol. 01
        </div>
        <div class="font-mono text-[10px] uppercase tracking-[0.2em] text-ink-3">
            Made with hands · No algorithm
        </div>
    </div>
</footer>

@endsection

@push('scripts')
<script>
(function() {
    const grid = document.getElementById('feed-grid');
    const loading = document.getElementById('feed-loading');
    const empty = document.getElementById('feed-empty');
    const end = document.getElementById('feed-end');
    const counter = document.getElementById('feed-counter');

    let currentTab = 'all';
    let page = { all: 1, place: 1, route: 1 };
    let hasMore = { all: true, place: true, route: true };
    let totalLoaded = { all: 0, place: 0, route: 0 };
    let loading_ = false;
    let observer = null;

    function setActiveTab(tab) {
        currentTab = tab;
        document.querySelectorAll('.feed-tab').forEach(btn => {
            const isActive = btn.dataset.tab === tab;
            btn.classList.toggle('bg-ink', isActive);
            btn.classList.toggle('text-paper', isActive);
            btn.classList.toggle('text-ink-2', !isActive);
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

    // 编辑感配色（不用 AI 默认 emerald）
    const GRADS = [
        ['#114B5F', '#1A1814'],
        ['#2D5F3F', '#0D3A4A'],
        ['#C45626', '#1A1814'],
        ['#847E72', '#1A1814'],
        ['#0D3A4A', '#2D5F3F'],
        ['#A1461E', '#1A1814'],
    ];
    const RATIOS = ['aspect-[3/4]','aspect-square','aspect-[4/5]','aspect-[3/4]','aspect-[4/3]','aspect-[2/3]'];

    function placeCard(p) {
        const g = GRADS[p.id % GRADS.length];
        const r = RATIOS[p.id % RATIOS.length];
        const typeLabel = p.place_type_label || '';
        // 不再用 emoji icon，改用 N° 编号 + 大字标签
        const numStr = String(p.id).padStart(2, '0');
        return `
            <a href="/place/${p.id}" class="masonry-item group block bg-paper border border-line hover:border-ink transition-colors">
                <div class="${r} relative overflow-hidden" style="background: linear-gradient(135deg, ${g[0]} 0%, ${g[1]} 100%);">
                    <div class="absolute inset-0 flex items-center justify-center">
                        <div class="font-display text-[10rem] leading-none text-paper/15 group-hover:text-paper/25 transition-colors select-none">${numStr}</div>
                    </div>
                    <div class="absolute top-2 left-2 font-mono text-[9px] uppercase tracking-[0.2em] text-paper/80">${escapeHtml(typeLabel)}</div>
                    ${p.rating_label && p.rating_meta ? `<div class="absolute top-2 right-2 font-mono text-[9px] uppercase tracking-[0.2em] px-1.5 py-0.5 border border-paper/50 text-paper">${escapeHtml(p.rating_meta.label)}</div>` : ''}
                </div>
                <div class="px-3 py-3 border-t border-line">
                    <h3 class="font-display text-base text-ink leading-tight line-clamp-1">${escapeHtml(p.name)}</h3>
                    ${p.description ? `<p class="text-xs text-ink-3 mt-1 line-clamp-2 leading-relaxed">${escapeHtml(p.description.replace(/<[^>]+>/g,'').slice(0,60))}</p>` : ''}
                    <div class="mt-2 flex items-center justify-between font-mono text-[10px] text-ink-3">
                        <span>${escapeHtml(p.city || '—')}</span>
                        <span class="opacity-0 group-hover:opacity-100 text-warm transition-opacity">→</span>
                    </div>
                </div>
            </a>`;
    }

    function routeCard(r) {
        const g = GRADS[r.id % GRADS.length];
        const r_ratio = RATIOS[r.id % RATIOS.length];
        const color = r.type_color || '#114B5F';
        const typeLabel = r.type_label || '';
        const numStr = String(r.id).padStart(2, '0');
        return `
            <a href="/route/${r.id}" class="masonry-item group block bg-paper border border-line hover:border-ink transition-colors">
                <div class="${r_ratio} relative overflow-hidden" style="background: linear-gradient(135deg, ${color} 0%, #1A1814 100%);">
                    <div class="absolute inset-0 flex items-center justify-center">
                        <div class="font-display text-[10rem] leading-none text-paper/15 group-hover:text-paper/25 transition-colors select-none">${numStr}</div>
                    </div>
                    <div class="absolute top-2 left-2 font-mono text-[9px] uppercase tracking-[0.2em] text-paper/80">${escapeHtml(typeLabel)}</div>
                    ${r.rating_meta ? `<div class="absolute top-2 right-2 font-mono text-[9px] uppercase tracking-[0.2em] px-1.5 py-0.5 border border-paper/50 text-paper">${escapeHtml(r.rating_meta.label)}</div>` : ''}
                    <div class="absolute bottom-0 left-0 right-0 p-2 bg-gradient-to-t from-ink/80 to-transparent">
                        <div class="flex items-center gap-2 text-paper font-mono text-[10px]">
                            ${r.distance_km ? `<span>${r.distance_km}KM</span>` : ''}
                            ${r.duration_hours ? `<span>${r.duration_hours}H</span>` : ''}
                            ${r.view_count > 0 ? `<span>${r.view_count}</span>` : ''}
                        </div>
                    </div>
                </div>
                <div class="px-3 py-3 border-t border-line">
                    <h3 class="font-display text-base text-ink leading-tight line-clamp-1">${escapeHtml(r.name)}</h3>
                    ${r.subtitle ? `<p class="font-mono text-[10px] text-ink-3 mt-1 line-clamp-1 italic">${escapeHtml(r.subtitle)}</p>` : (r.summary ? `<p class="text-xs text-ink-3 mt-1 line-clamp-2 leading-relaxed">${escapeHtml(r.summary)}</p>` : '')}
                    <div class="mt-2 flex items-center justify-between font-mono text-[10px] text-ink-3">
                        <span>${escapeHtml(r.city || '—')}</span>
                        ${r.like_count > 0 ? `<span>+${r.like_count}</span>` : ''}
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

            const merged = [
                ...places.map(p => ({ type: 'place', created_at: p.created_at, data: p })),
                ...routes.map(r => ({ type: 'route', created_at: r.created_at, data: r })),
            ].sort((a, b) => new Date(b.created_at) - new Date(a.created_at));

            const html = merged.map(m => m.type === 'place' ? placeCard(m.data) : routeCard(m.data)).join('');
            grid.insertAdjacentHTML('beforeend', html);

            totalLoaded[currentTab] += totalNew;
            counter.textContent = `${totalLoaded[currentTab]} items`;

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

    document.querySelectorAll('.feed-tab').forEach(btn => {
        btn.addEventListener('click', () => setActiveTab(btn.dataset.tab));
    });

    observer = new IntersectionObserver(entries => {
        if (entries.some(e => e.isIntersecting)) loadMore();
    }, { rootMargin: '200px' });
    observer.observe(loading);

    setTimeout(loadMore, 100);
})();
</script>
@endpush
