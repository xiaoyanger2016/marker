{{-- 线路卡片 --}}
@php
    $ratios = ['aspect-[3/4]', 'aspect-square', 'aspect-[4/5]', 'aspect-[3/4]', 'aspect-[4/3]', 'aspect-[2/3]'];
    $ratio = $ratios[$route->id % count($ratios)];

    $typeMeta = $route->typeMeta();
    $ratingMeta = $route->ratingMeta();
    $icon = $typeMeta['icon'] ?? '🚗';
    $color = $typeMeta['color'] ?? '#10b981';
@endphp

<a href="{{ url('/route/' . $route->id) }}" class="masonry-item group block bg-white rounded-2xl overflow-hidden shadow-sm hover:shadow-md transition-shadow">
    <div class="{{ $ratio }} relative overflow-hidden" style="background: linear-gradient(135deg, {{ $color }}, {{ $color }}aa);">
        <div class="w-full h-full flex items-center justify-center">
            <div class="text-7xl sm:text-8xl opacity-90 group-hover:scale-110 transition-transform duration-300">
                {{ $icon }}
            </div>
        </div>

        <div class="absolute top-2 left-2">
            <span class="px-2 py-0.5 text-[10px] font-semibold text-white rounded-full shadow" style="background: {{ $color }}">
                {{ $typeMeta['label'] ?? '' }}
            </span>
        </div>

        @if($route->places_count > 0)
            <div class="absolute top-2 left-2 mt-6">
                <span class="px-2 py-0.5 text-[10px] font-medium text-white bg-black/60 rounded-full">
                    📍 {{ $route->places_count }} 个点
                </span>
            </div>
        @endif

        @if($ratingMeta)
            <div class="absolute top-2 right-2">
                <span class="px-2 py-0.5 text-[10px] font-bold text-white rounded-full shadow" style="background: {{ $ratingMeta['color'] }}">
                    {{ $ratingMeta['icon'] }} {{ $ratingMeta['label'] }}
                </span>
            </div>
        @endif

        <div class="absolute bottom-0 left-0 right-0 p-2 bg-gradient-to-t from-black/70 to-transparent">
            <div class="flex items-center gap-2 text-white text-[10px]">
                @if($route->distance_km)
                    <span>🛣️ {{ $route->distance_km }}km</span>
                @endif
                @if($route->duration_hours)
                    <span>⏱️ {{ $route->duration_hours }}h</span>
                @endif
                @if($route->view_count > 0)
                    <span>👁️ {{ $route->view_count }}</span>
                @endif
            </div>
        </div>
    </div>

    <div class="p-3">
        <h3 class="font-semibold text-sm text-gray-900 line-clamp-1">{{ $route->name }}</h3>
        @if($route->subtitle)
            <p class="text-xs text-gray-500 mt-0.5 line-clamp-1">{{ $route->subtitle }}</p>
        @elseif($route->summary)
            <p class="text-xs text-gray-500 mt-0.5 line-clamp-2">{{ $route->summary }}</p>
        @endif
        <div class="mt-2 flex items-center justify-between text-[10px] text-gray-400">
            <span>{{ $route->city ?? '—' }}</span>
            @if($route->like_count > 0)
                <span>❤️ {{ $route->like_count }}</span>
            @endif
        </div>
    </div>
</a>
