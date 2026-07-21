{{-- 瀑布流卡片 - 单点 / 线路通用 --}}
@php
    $isRoute = $item['kind'] === 'route';
    // 错开高度（瀑布流效果）
    $ratios = ['aspect-[3/4]', 'aspect-square', 'aspect-[4/5]', 'aspect-[3/4]', 'aspect-[4/3]', 'aspect-[2/3]'];
    $ratio = $ratios[$item['id'] % count($ratios)];

    // 渐变 fallback（无封面图时）
    $gradients = [
        ['from' => '#fda4af', 'to' => '#fb923c'],  // 橙红
        ['from' => '#86efac', 'to' => '#22d3ee'],  // 绿青
        ['from' => '#a78bfa', 'to' => '#f472b6'],  // 紫粉
        ['from' => '#fcd34d', 'to' => '#fb7185'],  // 黄粉
        ['from' => '#5eead4', 'to' => '#818cf8'],  // 青蓝
        ['from' => '#fca5a5', 'to' => '#a855f7'],  // 红紫
    ];
    $gradient = $gradients[$item['id'] % count($gradients)];
    $hasCover = !empty($item['cover']) && $item['cover'] !== url('/images/placeholder.png');
@endphp

<a href="{{ $item['url'] }}" class="masonry-item group block bg-white rounded-2xl overflow-hidden shadow-sm hover:shadow-md transition-shadow">
    {{-- 封面图 --}}
    <div class="{{ $ratio }} relative overflow-hidden" style="@if(!$hasCover) background: linear-gradient(135deg, {{ $gradient['from'] }}, {{ $gradient['to'] }}); @else background: #f3f4f6; @endif">
        @if($hasCover)
            <img src="{{ $item['cover'] }}" alt="{{ $item['name'] }}"
                 class="w-full h-full object-cover group-hover:scale-105 transition-transform duration-300"
                 loading="lazy" onerror="this.style.display='none'; this.parentElement.style.background='linear-gradient(135deg, {{ $gradient['from'] }}, {{ $gradient['to'] }})';">
        @else
            {{-- Emoji 大封面 --}}
            <div class="w-full h-full flex items-center justify-center">
                <div class="text-7xl sm:text-8xl opacity-90 group-hover:scale-110 transition-transform duration-300">
                    {{ $isRoute ? ($item['type_icon'] ?? '🚗') : ($item['place_type_icon'] ?? '📍') }}
                </div>
            </div>
        @endif

        {{-- 标签角标 --}}
        <div class="absolute top-2 left-2 flex flex-col gap-1">
            @if($isRoute)
                <span class="px-2 py-0.5 text-[10px] font-semibold text-white rounded-full shadow" style="background: {{ $item['type_color'] }}">
                    {{ $item['type_icon'] }} {{ $item['type_label'] }}
                </span>
                @if(($item['places_count'] ?? 0) > 0)
                    <span class="px-2 py-0.5 text-[10px] font-medium text-white bg-black/60 rounded-full">
                        📍 {{ $item['places_count'] }} 个点
                    </span>
                @endif
            @else
                @if(!empty($item['place_type_icon']))
                    <span class="px-2 py-0.5 text-[10px] font-medium text-gray-700 bg-white/90 rounded-full">
                        {{ $item['place_type_icon'] }} {{ $item['place_type_label'] }}
                    </span>
                @endif
                @if(!empty($item['is_wishlist']))
                    <span class="px-2 py-0.5 text-[10px] font-semibold text-white bg-rose-500 rounded-full">
                        ❤️ 种草
                    </span>
                @endif
            @endif
        </div>

        {{-- 评分角标 --}}
        @if(!empty($item['rating_label_text']))
            <div class="absolute top-2 right-2">
                <span class="px-2 py-0.5 text-[10px] font-bold text-white rounded-full shadow"
                      style="background: {{ \App\Models\Place::RATING_LABELS[$item['rating_label']]['color'] ?? '#6b7280' }}">
                    {{ \App\Models\Place::RATING_LABELS[$item['rating_label']]['icon'] ?? '' }} {{ $item['rating_label_text'] }}
                </span>
            </div>
        @elseif(!empty($item['rating_meta']))
            <div class="absolute top-2 right-2">
                <span class="px-2 py-0.5 text-[10px] font-bold text-white rounded-full shadow"
                      style="background: {{ $item['rating_meta']['color'] }}">
                    {{ $item['rating_meta']['icon'] }} {{ $item['rating_meta']['label'] }}
                </span>
            </div>
        @endif

        {{-- 底部小信息 --}}
        @if($isRoute)
            <div class="absolute bottom-0 left-0 right-0 p-2 bg-gradient-to-t from-black/70 to-transparent">
                <div class="flex items-center gap-2 text-white text-[10px]">
                    @if($item['distance_km'])
                        <span>🛣️ {{ $item['distance_km'] }}km</span>
                    @endif
                    @if($item['duration_hours'])
                        <span>⏱️ {{ $item['duration_hours'] }}h</span>
                    @endif
                    @if($item['view_count'] > 0)
                        <span>👁️ {{ $item['view_count'] }}</span>
                    @endif
                </div>
            </div>
        @endif
    </div>

    {{-- 文字信息 --}}
    <div class="p-3">
        <h3 class="font-semibold text-sm text-gray-900 line-clamp-1">{{ $item['name'] }}</h3>
        @if(!empty($item['subtitle']))
            <p class="text-xs text-gray-500 mt-0.5 line-clamp-1">{{ $item['subtitle'] }}</p>
        @elseif(!empty($item['summary']))
            <p class="text-xs text-gray-500 mt-0.5 line-clamp-2">{{ $item['summary'] }}</p>
        @endif

        <div class="mt-2 flex items-center justify-between text-[10px] text-gray-400">
            <span>{{ $item['city'] ?? '—' }}</span>
            @if($isRoute && $item['like_count'] > 0)
                <span>❤️ {{ $item['like_count'] }}</span>
            @endif
        </div>
    </div>
</a>
