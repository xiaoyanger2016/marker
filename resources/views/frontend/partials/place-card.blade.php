{{-- 单点卡片 v2 · 编辑感：渐变 + 巨型 N° 编号（替代 emoji） --}}
@php
    $ratios = ['aspect-[3/4]', 'aspect-square', 'aspect-[4/5]', 'aspect-[3/4]', 'aspect-[4/3]', 'aspect-[2/3]'];
    $ratio = $ratios[$place->id % count($ratios)];

    $typeMeta = \App\Models\Place::PLACE_TYPES[$place->place_type] ?? null;
    $typeColor = $typeMeta['color'] ?? '#4A4640';
    $typeLabel = $typeMeta['label'] ?? '';
    $numStr = str_pad((string) ($place->id % 100), 2, '0', STR_PAD_LEFT);

    $rl = $place->rating_label ? (\App\Models\Place::RATING_LABELS[$place->rating_label] ?? null) : null;
@endphp

<a href="{{ url('/place/' . $place->id) }}" class="masonry-item group block bg-paper border border-line hover:border-ink transition-colors">
    <div class="{{ $ratio }} relative overflow-hidden" style="background: linear-gradient(135deg, {{ $typeColor }} 0%, #1A1814 100%);">
        {{-- 编辑感大字编号 --}}
        <div class="absolute inset-0 flex items-center justify-center">
            <div class="font-display text-[8rem] sm:text-[10rem] leading-none text-paper/15 group-hover:text-paper/25 transition-colors select-none">{{ $numStr }}</div>
        </div>

        {{-- 类型角标 --}}
        @if($typeLabel)
            <div class="absolute top-2 left-2">
                <span class="font-mono text-[9px] uppercase tracking-[0.2em] text-paper/85 border border-paper/40 px-1.5 py-0.5">{{ $typeLabel }}</span>
            </div>
        @endif

        {{-- 评分角标 --}}
        @if($rl && !empty($rl['label']))
            <div class="absolute top-2 right-2">
                <span class="font-mono text-[9px] uppercase tracking-[0.2em] px-1.5 py-0.5 border border-paper/50 text-paper" style="background: rgba(26, 24, 20, 0.4);">
                    {{ $rl['label'] }}
                </span>
            </div>
        @endif
    </div>

    <div class="px-3 py-3 border-t border-line">
        <h3 class="font-display text-base text-ink leading-tight line-clamp-1">{{ $place->name }}</h3>
        @if($place->description)
            <p class="text-xs text-ink-3 mt-1 line-clamp-2 leading-relaxed">{{ mb_substr(strip_tags($place->description), 0, 60) }}</p>
        @endif
        <div class="mt-2 flex items-center justify-between font-mono text-[10px] text-ink-3">
            <span>{{ $place->city ?? '—' }}</span>
            <span class="opacity-0 group-hover:opacity-100 text-warm transition-opacity">→</span>
        </div>
    </div>
</a>
