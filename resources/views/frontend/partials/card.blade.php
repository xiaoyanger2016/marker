{{-- 编辑感卡片：单点 / 线路通用 · v2
     风格：杂志期刊
     - 渐变封面 + 巨型 N° 编号（替代 emoji）
     - mono 标签 + 衬线标题
     - 4px 圆角 + 细线
--}}
@php
    $isRoute = ($item['kind'] ?? null) === 'route';

    // 错开高度（瀑布流效果）
    $ratios = ['aspect-[3/4]', 'aspect-square', 'aspect-[4/5]', 'aspect-[3/4]', 'aspect-[4/3]', 'aspect-[2/3]'];
    $ratio = $ratios[$item['id'] % count($ratios)];

    // 封面用 type 颜色（编辑感低饱和度）
    $typeColor = $isRoute
        ? ($item['type_color'] ?? '#114B5F')
        : (\App\Models\Place::PLACE_TYPES[$item['place_type'] ?? '']['color'] ?? '#4A4640');
    $typeLabel = $isRoute ? ($item['type_label'] ?? '') : ($item['place_type_label'] ?? '');

    $numStr = str_pad((string) ($item['id'] % 100), 2, '0', STR_PAD_LEFT);
    $hasCover = !empty($item['cover']) && $item['cover'] !== url('/images/placeholder.png');

    // 评分（从 meta 拿，icon 已无 emoji）
    $rating = $item['rating_meta'] ?? null;
@endphp

<a href="{{ $item['url'] }}" class="masonry-item group block bg-paper border border-line hover:border-ink transition-colors">

    {{-- 封面 --}}
    <div class="{{ $ratio }} relative overflow-hidden" style="@if(!$hasCover) background: linear-gradient(135deg, {{ $typeColor }} 0%, #1A1814 100%); @else background: var(--color-paper-2); @endif">
        @if($hasCover)
            <img src="{{ $item['cover'] }}" alt="{{ $item['name'] }}"
                 class="w-full h-full object-cover group-hover:scale-105 transition-transform duration-300"
                 loading="lazy" onerror="this.style.display='none'; this.parentElement.style.background='linear-gradient(135deg, {{ $typeColor }} 0%, #1A1814 100%)';">
        @else
            {{-- 编辑感大字编号（替代 emoji） --}}
            <div class="absolute inset-0 flex items-center justify-center">
                <div class="font-display text-[8rem] sm:text-[10rem] leading-none text-paper/15 group-hover:text-paper/25 transition-colors select-none">{{ $numStr }}</div>
            </div>
        @endif

        {{-- 类型角标（mono 大写） --}}
        @if($typeLabel)
            <div class="absolute top-2 left-2">
                <span class="font-mono text-[9px] uppercase tracking-[0.2em] text-paper/85 border border-paper/40 px-1.5 py-0.5">{{ $typeLabel }}</span>
            </div>
        @endif

        {{-- 评分角标 --}}
        @if($rating && !empty($rating['label']))
            <div class="absolute top-2 right-2">
                <span class="font-mono text-[9px] uppercase tracking-[0.2em] px-1.5 py-0.5 border border-paper/50 text-paper" style="background: rgba(26, 24, 20, 0.4);">
                    {{ $rating['label'] }}
                </span>
            </div>
        @endif

        {{-- 线路：底部数据条 --}}
        @if($isRoute)
            <div class="absolute bottom-0 left-0 right-0 p-2 bg-gradient-to-t from-ink/85 to-transparent">
                <div class="flex items-center gap-2 text-paper font-mono text-[10px]">
                    @if(!empty($item['distance_km']))
                        <span>{{ $item['distance_km'] }}KM</span>
                    @endif
                    @if(!empty($item['duration_hours']))
                        <span>{{ $item['duration_hours'] }}H</span>
                    @endif
                    @if(!empty($item['places_count']) && $item['places_count'] > 0)
                        <span>{{ $item['places_count'] }} STOPS</span>
                    @endif
                </div>
            </div>
        @endif
    </div>

    {{-- 文字信息 --}}
    <div class="px-3 py-3 border-t border-line">
        <h3 class="font-display text-base text-ink leading-tight line-clamp-1">{{ $item['name'] }}</h3>
        @if(!empty($item['subtitle']))
            <p class="font-mono text-[10px] text-ink-3 mt-1 line-clamp-1 italic">{{ $item['subtitle'] }}</p>
        @elseif(!empty($item['summary']))
            <p class="text-xs text-ink-3 mt-1 line-clamp-2 leading-relaxed">{{ $item['summary'] }}</p>
        @endif

        <div class="mt-2 flex items-center justify-between font-mono text-[10px] text-ink-3">
            <span>{{ $item['city'] ?? '—' }}</span>
            @if($isRoute && ($item['like_count'] ?? 0) > 0)
                <span>+{{ $item['like_count'] }}</span>
            @elseif(!$isRoute && !empty($item['is_wishlist']))
                <span class="text-warm">种草中</span>
            @else
                <span class="opacity-0 group-hover:opacity-100 text-warm transition-opacity">→</span>
            @endif
        </div>
    </div>
</a>
