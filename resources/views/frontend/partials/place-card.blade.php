{{-- 单点卡片（与线路卡片区别：显示 place_type icon + 城市）--}}
@php
    $ratios = ['aspect-[3/4]', 'aspect-square', 'aspect-[4/5]', 'aspect-[3/4]', 'aspect-[4/3]', 'aspect-[2/3]'];
    $ratio = $ratios[$place->id % count($ratios)];

    $gradients = [
        ['#fda4af', '#fb923c'], ['#86efac', '#22d3ee'], ['#a78bfa', '#f472b6'],
        ['#fcd34d', '#fb7185'], ['#5eead4', '#818cf8'], ['#fca5a5', '#a855f7'],
    ];
    $gradient = $gradients[$place->id % count($gradients)];
    $hasCover = false; // 这里默认 false 让 emoji 显示
    $icon = \App\Models\Place::PLACE_TYPES[$place->place_type]['icon'] ?? '📍';
    $typeLabel = \App\Models\Place::PLACE_TYPES[$place->place_type]['label'] ?? '';
@endphp

<a href="{{ url('/place/' . $place->id) }}" class="masonry-item group block bg-white rounded-2xl overflow-hidden shadow-sm hover:shadow-md transition-shadow">
    <div class="{{ $ratio }} relative overflow-hidden" style="background: linear-gradient(135deg, {{ $gradient[0] }}, {{ $gradient[1] }});">
        <div class="w-full h-full flex items-center justify-center">
            <div class="text-7xl sm:text-8xl opacity-90 group-hover:scale-110 transition-transform duration-300">
                {{ $icon }}
            </div>
        </div>

        {{-- 类型角标 --}}
        @if($typeLabel)
            <div class="absolute top-2 left-2">
                <span class="px-2 py-0.5 text-[10px] font-medium text-gray-700 bg-white/90 rounded-full">
                    {{ $typeLabel }}
                </span>
            </div>
        @endif

        {{-- 评分 --}}
        @if($place->rating_label)
            @php $rl = \App\Models\Place::RATING_LABELS[$place->rating_label] ?? null; @endphp
            @if($rl)
                <div class="absolute top-2 right-2">
                    <span class="px-2 py-0.5 text-[10px] font-bold text-white rounded-full shadow" style="background: {{ $rl['color'] }}">
                        {{ $rl['icon'] }} {{ $rl['label'] }}
                    </span>
                </div>
            @endif
        @endif
    </div>

    <div class="p-3">
        <h3 class="font-semibold text-sm text-gray-900 line-clamp-1">{{ $place->name }}</h3>
        @if($place->description)
            <p class="text-xs text-gray-500 mt-0.5 line-clamp-2">{{ mb_substr(strip_tags($place->description), 0, 60) }}</p>
        @endif
        <div class="mt-2 flex items-center justify-between text-[10px] text-gray-400">
            <span>{{ $place->city ?? '—' }}</span>
        </div>
    </div>
</a>
