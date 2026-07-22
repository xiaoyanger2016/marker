<x-filament-panels::page>
    {{-- 杂志式 dashboard
         1. 头版：今天 + 关键数据
         2. 4 个 metric 大数字 (paper 杂志感)
         3. 最近地点卡片
    --}}

    @php
        $stats = $stats ?? [];
        $recent = $recent_places ?? collect();
    @endphp

    {{-- 头版：编辑感大标题 --}}
    <div class="mb-10 sm:mb-14">
        <div class="flex items-baseline gap-3 mb-3 font-mono text-[10px] uppercase tracking-[0.2em] text-gray-500">
            <span>N°01</span>
            <span class="w-px h-3 bg-line-2"></span>
            <span>EDITORIAL DESK</span>
            <span class="w-px h-3 bg-line-2"></span>
            <span>{{ now()->format('Y/m/d · l') }}</span>
        </div>
        <h1 class="font-display text-4xl sm:text-6xl text-ink font-medium leading-none">
            早上好，<span class="italic font-normal">{{ auth()->user()?->name ?? 'Editor' }}</span>。
        </h1>
        <p class="mt-3 font-display italic text-base sm:text-lg text-gray-500">
            今日 {{ $stats['places_today'] ?? 0 }} 个新地点 · {{ $stats['routes_today'] ?? 0 }} 条新线路 · 等待编辑上架。
        </p>
    </div>

    {{-- 4 个 metric 大数字
         用 inline style 强制 grid-template-columns（Filament wrapper 有 flex-1 会覆盖） --}}
    <div class="grid gap-px bg-line border border-line mb-10 metric-grid">
        <style>.metric-grid{grid-template-columns:repeat(2,minmax(0,1fr))!important;display:grid!important}@media(min-width:1024px){.metric-grid{grid-template-columns:repeat(4,minmax(0,1fr))!important}}</style>
        <div class="bg-paper p-5 sm:p-6">
            <div class="font-mono text-[10px] uppercase tracking-[0.2em] text-gray-500 mb-2">N°01 · 地点</div>
            <div class="font-display text-4xl sm:text-5xl text-ink leading-none">{{ $stats['places'] ?? 0 }}</div>
            <div class="font-mono text-[10px] text-gray-500 mt-2">total places</div>
        </div>
        <div class="bg-paper p-5 sm:p-6">
            <div class="font-mono text-[10px] uppercase tracking-[0.2em] text-gray-500 mb-2">N°02 · 线路</div>
            <div class="font-display text-4xl sm:text-5xl text-ink leading-none">{{ $stats['routes'] ?? 0 }}</div>
            <div class="font-mono text-[10px] text-gray-500 mt-2">total routes</div>
        </div>
        <div class="bg-paper p-5 sm:p-6">
            <div class="font-mono text-[10px] uppercase tracking-[0.2em] text-gray-500 mb-2">N°03 · 活动</div>
            <div class="font-display text-4xl sm:text-5xl text-ink leading-none">{{ $stats['activities'] ?? 0 }}</div>
            <div class="font-mono text-[10px] text-gray-500 mt-2">
                {{ $stats['activities_open'] ?? 0 }} 招募中
            </div>
        </div>
        <div class="bg-paper p-5 sm:p-6">
            <div class="font-mono text-[10px] uppercase tracking-[0.2em] text-gray-500 mb-2">N°04 · 读者</div>
            <div class="font-display text-4xl sm:text-5xl text-ink leading-none">{{ $stats['users'] ?? 0 }}</div>
            <div class="font-mono text-[10px] text-gray-500 mt-2">readers total</div>
        </div>
    </div>

    {{-- 快捷入口（编辑感卡片） --}}
    <div class="mb-10">
        <div class="flex items-baseline justify-between mb-4">
            <span class="eyebrow">§ 02 — 快捷入口</span>
            <span class="font-mono text-[10px] text-gray-500">jump in</span>
        </div>
        <div class="grid gap-3 shortcuts-grid">
            <style>.shortcuts-grid{grid-template-columns:repeat(2,minmax(0,1fr))!important;display:grid!important}@media(min-width:640px){.shortcuts-grid{grid-template-columns:repeat(4,minmax(0,1fr))!important}}</style>
            <a href="/admin/places" class="block border border-line p-4 hover:border-ink transition-colors">
                <div class="font-mono text-[10px] text-gray-500">N°01</div>
                <div class="font-display text-lg text-ink mt-1">收藏地点</div>
                <div class="font-mono text-[10px] text-gray-500 mt-1">→ 39 待审</div>
            </a>
            <a href="/admin/activities" class="block border border-line p-4 hover:border-ink transition-colors">
                <div class="font-mono text-[10px] text-gray-500">N°02</div>
                <div class="font-display text-lg text-ink mt-1">活动</div>
                <div class="font-mono text-[10px] text-gray-500 mt-1">→ 3 招募中</div>
            </a>
            <a href="/admin/routes" class="block border border-line p-4 hover:border-ink transition-colors">
                <div class="font-mono text-[10px] text-gray-500">N°03</div>
                <div class="font-display text-lg text-ink mt-1">线路</div>
                <div class="font-mono text-[10px] text-gray-500 mt-1">→ 6 自驾</div>
            </a>
            <a href="/admin/import-from-amap" class="block border border-line p-4 hover:border-ink transition-colors">
                <div class="font-mono text-[10px] text-gray-500">N°04</div>
                <div class="font-display text-lg text-ink mt-1">导入 POI</div>
                <div class="font-mono text-[10px] text-gray-500 mt-1">→ 高德地图</div>
            </a>
        </div>
    </div>

    {{-- 最近地点 --}}
    @if($recent->isNotEmpty())
    <div>
        <div class="flex items-baseline justify-between mb-4">
            <span class="eyebrow">§ 03 — 最近上架</span>
            <a href="/admin/places" class="font-mono text-[10px] uppercase tracking-[0.2em] text-gray-500 hover:text-ink">查看全部 →</a>
        </div>
        <div class="grid gap-3 recent-grid">
            <style>.recent-grid{grid-template-columns:repeat(2,minmax(0,1fr))!important;display:grid!important}@media(min-width:640px){.recent-grid{grid-template-columns:repeat(3,minmax(0,1fr))!important}}</style>
            @foreach($recent as $p)
                <a href="/admin/places/{{ $p->id }}/edit" class="block border border-line p-3 hover:border-ink transition-colors">
                    <div class="font-mono text-[10px] text-gray-500 uppercase tracking-[0.2em]">{{ $p->place_type }}</div>
                    <div class="font-display text-base text-ink mt-1 line-clamp-1">{{ $p->name }}</div>
                    <div class="font-mono text-[10px] text-gray-500 mt-1">{{ $p->city ?? '—' }} · {{ $p->created_at->diffForHumans() }}</div>
                </a>
            @endforeach
        </div>
    </div>
    @endif
</x-filament-panels::page>
