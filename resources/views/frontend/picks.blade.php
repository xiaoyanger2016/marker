@extends('frontend.layout')

@section('title', '本期精选 · Marker')

@section('content')

<section class="border-b border-line-2">
    <div class="max-w-6xl mx-auto px-5 sm:px-8 pt-6 pb-2">
        <div class="flex items-baseline justify-between text-[10px] font-mono uppercase tracking-[0.2em] text-ink-3">
            <div class="flex items-center gap-3">
                <a href="/" class="hover:text-ink transition-colors">← HOME</a>
                <span class="w-px h-3 bg-line-2"></span>
                <span>§ 02 · ALL PICKS</span>
            </div>
        </div>
    </div>
</section>

<section class="border-b border-line">
    <div class="max-w-6xl mx-auto px-5 sm:px-8 py-8 sm:py-14">
        <h1 class="font-display font-medium text-4xl sm:text-6xl text-ink leading-[1.05]">
            本期精选，<br>
            <span class="serif-italic text-warm">{{ $picks->total() }} 条</span>
        </h1>
        <p class="font-display italic text-base sm:text-xl text-ink-2 mt-4">
            编辑手动 pin 到首页的精选，按 sort 排序。
        </p>
    </div>
</section>

@if($picks->count() > 0)
<div class="max-w-6xl mx-auto px-5 sm:px-8 py-8 sm:py-12">
    <div class="masonry">
        @foreach($picks as $item)
            @php
                $numStr = str_pad($item->id, 2, '0', STR_PAD_LEFT);
                $ratio = ['aspect-[3/4]','aspect-square','aspect-[4/5]','aspect-[3/4]','aspect-[4/3]','aspect-[2/3]'][$item->id % 6];
                $cover = $item->coverMedia ?: $item->gallery->first();
            @endphp
            <a href="{{ url('/content/' . $item->id) }}" class="masonry-item group block bg-paper border border-line hover:border-ink transition-colors">
                <div class="{{ $ratio }} relative overflow-hidden" style="background: linear-gradient(135deg, {{ $item->typeMeta()['color'] ?? '#114B5F' }} 0%, #1A1814 100%);">
                    @if($cover && ! str_contains($cover->url ?? '', 'placeholder'))
                        <img src="{{ $cover->url }}" alt="{{ $item->title }}" loading="lazy" class="absolute inset-0 w-full h-full object-cover">
                    @else
                        <div class="absolute inset-0 flex items-center justify-center">
                            <span class="font-display text-[5rem] sm:text-[10rem] leading-none text-paper/15 group-hover:text-paper/25 transition-colors select-none">{{ $numStr }}</span>
                        </div>
                    @endif
                    <div class="absolute top-2 left-2 flex items-center gap-1.5 font-mono text-[9px] uppercase tracking-[0.2em] text-paper/85">
                        <span>{{ $item->typeMeta()['icon'] }}</span>
                        <span class="w-px h-2.5 bg-paper/30"></span>
                        <span>{{ $item->typeMeta()['label'] }}</span>
                    </div>
                    @if($item->rating_label)
                        <div class="absolute top-2 right-2 font-mono text-[9px] uppercase tracking-[0.2em] px-1.5 py-0.5 border border-paper/50 text-paper bg-ink/30">
                            {{ \App\Models\Content::RATING_LABELS[$item->rating_label]['label'] ?? '' }}
                        </div>
                    @endif
                </div>
                <div class="px-3 py-3 border-t border-line">
                    <h3 class="font-display text-base text-ink leading-tight line-clamp-1">{{ $item->title }}</h3>
                    @if($item->summary)
                        <p class="text-xs text-ink-3 mt-1 line-clamp-2 leading-relaxed">{{ \Illuminate\Support\Str::limit(strip_tags($item->summary), 60) }}</p>
                    @endif
                    <div class="mt-2 flex items-center justify-between font-mono text-[10px] text-ink-3">
                        <span>{{ $item->places->first()?->city ?? '—' }}</span>
                        <span class="opacity-0 group-hover:opacity-100 text-warm transition-opacity">→</span>
                    </div>
                </div>
            </a>
        @endforeach
    </div>
    <div class="py-8 text-center">{{ $picks->links() }}</div>
</div>
@else
<div class="max-w-6xl mx-auto px-5 sm:px-8 py-20 text-center">
    <div class="font-display text-3xl text-ink-2 mb-2">还没有精选内容</div>
    <p class="text-sm text-ink-3">admin 可在 内容管理 → 列表 勾选「推送到首页」</p>
</div>
@endif

@endsection
