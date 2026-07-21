{{-- 线路卡片 v2 · 编辑感：渐变 + 巨型 N° 编号（替代 emoji） --}}
@php
    $ratios = ['aspect-[3/4]', 'aspect-square', 'aspect-[4/5]', 'aspect-[3/4]', 'aspect-[4/3]', 'aspect-[2/3]'];
    $ratio = $ratios[$route->id % count($ratios)];

    $typeMeta = $route->typeMeta();
    $typeColor = $typeMeta['color'] ?? '#114B5F';
    $typeLabel = $typeMeta['label'] ?? '';
    $numStr = str_pad((string) ($route->id % 100), 2, '0', STR_PAD_LEFT);

    $rl = $route->rating_label ? (\App\Models\Route::RATING_LABELS[$route->rating_label] ?? null) : null;
@endphp

<a href="{{ url('/route/' . $route->id) }}" class="masonry-item group block bg-paper border border-line hover:border-ink transition-colors">
    <div class="{{ $ratio }} relative overflow-hidden" style="background: linear-gradient(135deg, {{ $typeColor }} 0%, #1A1814 100%);">
        {{-- 编辑感大字编号 --}}
        <div class="absolute inset-0 flex items-center justify-center">
            <div class="font-display text-[8rem] sm:text-[10rem] leading-none text-paper/15 group-hover:text-paper/25 transition-colors select-none">{{ $numStr }}</div>
        </div>

        {{-- 类型角标 --}}
        <div class="absolute top-2 left-2">
            <span class="font-mono text-[9px] uppercase tracking-[0.2em] text-paper/85 border border-paper/40 px-1.5 py-0.5">{{ $typeLabel }}</span>
        </div>

        {{-- 评分 --}}
        @if($rl && !empty($rl['label']))
            <div class="absolute top-2 right-2">
                <span class="font-mono text-[9px] uppercase tracking-[0.2em] px-1.5 py-0.5 border border-paper/50 text-paper" style="background: rgba(26, 24, 20, 0.4);">
                    {{ $rl['label'] }}
                </span>
            </div>
        @endif

        {{-- 底部数据 --}}
        <div class="absolute bottom-0 left-0 right-0 p-2 bg-gradient-to-t from-ink/85 to-transparent">
            <div class="flex items-center gap-2 text-paper font-mono text-[10px]">
                @if($route->distance_km)<span>{{ $route->distance_km }}KM</span>@endif
                @if($route->duration_hours)<span>{{ $route->duration_hours }}H</span>@endif
                @if($route->places_count > 0)<span>{{ $route->places_count }} STOPS</span>@endif
            </div>
        </div>
    </div>

    <div class="px-3 py-3 border-t border-line">
        <h3 class="font-display text-base text-ink leading-tight line-clamp-1">{{ $route->name }}</h3>
        @if($route->subtitle)
            <p class="font-mono text-[10px] text-ink-3 mt-1 line-clamp-1 italic">{{ $route->subtitle }}</p>
        @elseif($route->summary)
            <p class="text-xs text-ink-3 mt-1 line-clamp-2 leading-relaxed">{{ $route->summary }}</p>
        @endif
        <div class="mt-2 flex items-center justify-between font-mono text-[10px] text-ink-3">
            <span>{{ $route->city ?? '—' }}</span>
            @if($route->like_count > 0)
                <span>+{{ $route->like_count }}</span>
            @else
                <span class="opacity-0 group-hover:opacity-100 text-warm transition-opacity">→</span>
            @endif
        </div>
    </div>
</a>
