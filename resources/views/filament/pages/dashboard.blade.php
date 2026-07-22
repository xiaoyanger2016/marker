<x-filament-panels::page>
    {{-- Linear 风格 header：简洁标题 + meta + 快捷动作 --}}
    <div class="flex items-center justify-between mb-6">
        <div>
            <h1 class="text-[18px] font-semibold text-ink leading-tight">Inbox</h1>
            <p class="font-mono text-[11px] text-ink-3 mt-0.5">
                {{ now()->format('Y/m/d') }} · {{ now()->format('l') }} · {{ $stats['places'] ?? 0 }} places · {{ $stats['routes'] ?? 0 }} routes · {{ $stats['activities'] ?? 0 }} active activities
            </p>
        </div>
        <div class="flex items-center gap-2">
            <a href="/admin/places/create"
               class="inline-flex items-center gap-1.5 px-3 py-1.5 bg-accent text-white text-[12px] font-medium hover:bg-accent-2 transition-colors"
               style="background: var(--accent)">
                <svg class="w-3.5 h-3.5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M12 5v14M5 12h14"/></svg>
                New place
            </a>
            <a href="/admin/import-from-amap"
               class="inline-flex items-center gap-1.5 px-3 py-1.5 border border-ink/30 text-ink text-[12px] font-medium hover:border-ink hover:bg-paper-2 transition-colors">
                Import from Amap
            </a>
        </div>
    </div>

    {{-- 4 metric cards：Linear 紧凑 1px hairline --}}
    <style>
        .metric-grid{display:grid!important;grid-template-columns:repeat(2,minmax(0,1fr))!important;gap:8px!important}
        @media(min-width:1024px){.metric-grid{grid-template-columns:repeat(4,minmax(0,1fr))!important}}
    </style>
    <div class="metric-grid mb-6">
        <div class="metric-grid-cell">
            <div class="metric-label">Places</div>
            <div class="metric-value">{{ $stats['places'] ?? 0 }}</div>
            <div class="metric-delta {{ ($stats['places_today'] ?? 0) > 0 ? 'positive' : '' }}">
                @if(($stats['places_today'] ?? 0) > 0)
                    +{{ $stats['places_today'] }} today
                @else
                    no change today
                @endif
            </div>
        </div>
        <div class="metric-grid-cell">
            <div class="metric-label">Routes</div>
            <div class="metric-value">{{ $stats['routes'] ?? 0 }}</div>
            <div class="metric-delta">
                {{ $stats['routes_self_drive'] ?? 0 }} self-drive · {{ $stats['routes_hiking'] ?? 0 }} hiking
            </div>
        </div>
        <div class="metric-grid-cell">
            <div class="metric-label">Activities</div>
            <div class="metric-value">{{ $stats['activities'] ?? 0 }}</div>
            <div class="metric-delta {{ ($stats['activities_open'] ?? 0) > 0 ? 'positive' : '' }}">
                {{ $stats['activities_open'] ?? 0 }} open · {{ $stats['activities_full'] ?? 0 }} full
            </div>
        </div>
        <div class="metric-grid-cell">
            <div class="metric-label">Users</div>
            <div class="metric-value">{{ $stats['users'] ?? 0 }}</div>
            <div class="metric-delta">
                {{ $stats['users_active_7d'] ?? 0 }} active (7d)
            </div>
        </div>
    </div>

    {{-- 主体两列：左侧 "Today" 任务，右侧 "Recent" 内容 --}}
    <div class="grid grid-cols-1 lg:grid-cols-[1fr_320px] gap-6">
        {{-- Left: Today's actions / 最近上架 --}}
        <div>
            <div class="flex items-center justify-between mb-3">
                <h2 class="text-[12px] font-medium uppercase tracking-[0.08em] text-ink-3">Recent</h2>
                <a href="/admin/places" class="font-mono text-[10px] uppercase tracking-[0.06em] text-ink-3 hover:text-ink">View all →</a>
            </div>
            <div class="border border-ink/10 divide-y divide-ink/10">
                @forelse($recent_places ?? [] as $p)
                    <a href="/admin/places/{{ $p->id }}/edit" class="flex items-center gap-3 px-4 py-2.5 hover:bg-paper-2 transition-colors group">
                        <span class="font-mono text-[10px] text-ink-3 w-8 shrink-0">#{{ str_pad($p->id, 3, '0', STR_PAD_LEFT) }}</span>
                        <div class="flex-1 min-w-0">
                            <div class="text-[13px] text-ink font-medium truncate">{{ $p->name }}</div>
                            <div class="font-mono text-[10px] text-ink-3 mt-0.5">
                                {{ $p->place_type ?? '—' }} · {{ $p->city ?? '—' }} · {{ $p->created_at->diffForHumans() }}
                            </div>
                        </div>
                        <span class="font-mono text-[10px] text-ink-3 opacity-0 group-hover:opacity-100 transition-opacity">→</span>
                    </a>
                @empty
                    <div class="px-4 py-8 text-center text-ink-3 text-[12px]">
                        No places yet. <a href="/admin/places/create" class="text-accent hover:underline">Create one</a>.
                    </div>
                @endforelse
            </div>
        </div>

        {{-- Right: 快捷入口 + 状态分布 --}}
        <div class="space-y-6">
            <div>
                <h2 class="text-[12px] font-medium uppercase tracking-[0.08em] text-ink-3 mb-3">Shortcuts</h2>
                <div class="border border-ink/10 divide-y divide-ink/10">
                    <a href="/admin/places" class="flex items-center gap-3 px-3 py-2.5 hover:bg-paper-2 transition-colors">
                        <span class="font-mono text-[10px] text-ink-3 w-6">01</span>
                        <span class="text-[12px] text-ink flex-1">Places</span>
                        <span class="font-mono text-[10px] text-ink-3">{{ $stats['places'] ?? 0 }}</span>
                    </a>
                    <a href="/admin/routes" class="flex items-center gap-3 px-3 py-2.5 hover:bg-paper-2 transition-colors">
                        <span class="font-mono text-[10px] text-ink-3 w-6">02</span>
                        <span class="text-[12px] text-ink flex-1">Routes</span>
                        <span class="font-mono text-[10px] text-ink-3">{{ $stats['routes'] ?? 0 }}</span>
                    </a>
                    <a href="/admin/activities" class="flex items-center gap-3 px-3 py-2.5 hover:bg-paper-2 transition-colors">
                        <span class="font-mono text-[10px] text-ink-3 w-6">03</span>
                        <span class="text-[12px] text-ink flex-1">Activities</span>
                        <span class="font-mono text-[10px] text-ink-3">{{ $stats['activities'] ?? 0 }}</span>
                    </a>
                    <a href="/admin/categories" class="flex items-center gap-3 px-3 py-2.5 hover:bg-paper-2 transition-colors">
                        <span class="font-mono text-[10px] text-ink-3 w-6">04</span>
                        <span class="text-[12px] text-ink flex-1">Categories</span>
                        <span class="font-mono text-[10px] text-ink-3">{{ $stats['categories'] ?? 0 }}</span>
                    </a>
                    <a href="/admin/regions" class="flex items-center gap-3 px-3 py-2.5 hover:bg-paper-2 transition-colors">
                        <span class="font-mono text-[10px] text-ink-3 w-6">05</span>
                        <span class="text-[12px] text-ink flex-1">Regions</span>
                        <span class="font-mono text-[10px] text-ink-3">{{ $stats['regions'] ?? 0 }}</span>
                    </a>
                    <a href="/admin/import-from-amap" class="flex items-center gap-3 px-3 py-2.5 hover:bg-paper-2 transition-colors">
                        <span class="font-mono text-[10px] text-ink-3 w-6">06</span>
                        <span class="text-[12px] text-ink flex-1">Import from Amap</span>
                        <span class="font-mono text-[10px] text-ink-3">→</span>
                    </a>
                </div>
            </div>

            {{-- 8 大类分布 --}}
            <div>
                <h2 class="text-[12px] font-medium uppercase tracking-[0.08em] text-ink-3 mb-3">8 types</h2>
                <div class="border border-ink/10 divide-y divide-ink/10">
                    @foreach(\App\Models\Place::PLACE_TYPES as $key => $meta)
                        <a href="/admin/places?tableFilters[place_type][values][0]={{ $key }}&tableFilters[place_type][value]={{ $key }}" class="flex items-center gap-3 px-3 py-2 hover:bg-paper-2 transition-colors">
                            <span class="font-mono text-[10px] text-ink-3 w-6">{{ $meta['icon'] }}</span>
                            <span class="w-2 h-2 inline-block" style="background: {{ $meta['color'] }}"></span>
                            <span class="text-[12px] text-ink flex-1">{{ $meta['label'] }}</span>
                            <span class="font-mono text-[10px] text-ink-3">{{ $stats['by_type'][$key] ?? 0 }}</span>
                        </a>
                    @endforeach
                </div>
            </div>

            {{-- Status (活动状态 + 想去/去过) --}}
            <div>
                <h2 class="text-[12px] font-medium uppercase tracking-[0.08em] text-ink-3 mb-3">Status</h2>
                <div class="border border-ink/10 divide-y divide-ink/10">
                    <div class="flex items-center gap-3 px-3 py-2.5">
                        <span class="w-2 h-2 bg-accent inline-block" style="background: var(--accent)"></span>
                        <span class="text-[12px] text-ink flex-1">Open activities</span>
                        <span class="font-mono text-[10px] text-ink-3">{{ $stats['activities_open'] ?? 0 }}</span>
                    </div>
                    <div class="flex items-center gap-3 px-3 py-2.5">
                        <span class="w-2 h-2 bg-sun inline-block" style="background: var(--sun)"></span>
                        <span class="text-[12px] text-ink flex-1">Full activities</span>
                        <span class="font-mono text-[10px] text-ink-3">{{ $stats['activities_full'] ?? 0 }}</span>
                    </div>
                    <div class="flex items-center gap-3 px-3 py-2.5">
                        <span class="w-2 h-2 bg-grass inline-block" style="background: var(--grass)"></span>
                        <span class="text-[12px] text-ink flex-1">Visited places</span>
                        <span class="font-mono text-[10px] text-ink-3">{{ $stats['places_visited'] ?? 0 }}</span>
                    </div>
                    <div class="flex items-center gap-3 px-3 py-2.5">
                        <span class="w-2 h-2 bg-warm inline-block" style="background: var(--warm)"></span>
                        <span class="text-[12px] text-ink flex-1">Wishlist</span>
                        <span class="font-mono text-[10px] text-ink-3">{{ $stats['places_wishlist'] ?? 0 }}</span>
                    </div>
                </div>
            </div>
        </div>
    </div>
</x-filament-panels::page>
