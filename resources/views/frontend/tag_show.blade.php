@extends('frontend.layout')

@section('title', '#' . $tag->name . ' · Marker')

@section('content')

<section class="border-b border-line-2">
    <div class="max-w-6xl mx-auto px-5 sm:px-8 pt-6 pb-2">
        <div class="flex items-center gap-3 font-mono text-[10px] uppercase tracking-[0.2em] text-ink-3">
            <a href="/tags" class="hover:text-ink">← ALL TAGS</a>
            <span class="w-px h-3 bg-line-2"></span>
            <span>TAG</span>
        </div>
    </div>
</section>

<section class="border-b border-line">
    <div class="max-w-6xl mx-auto px-5 sm:px-8 py-6 sm:py-14">
        <div class="flex items-baseline gap-3">
            <span class="font-display text-3xl sm:text-5xl text-ink-3">#</span>
            <h1 class="font-display font-medium text-4xl sm:text-6xl text-ink leading-none">{{ $tag->name }}</h1>
        </div>
        <p class="font-display italic text-base sm:text-xl text-ink-2 mt-3">{{ $contents->total() }} 个内容</p>
    </div>
</section>

@if($contents->count() > 0)
<div class="max-w-6xl mx-auto px-5 sm:px-8 py-8 sm:py-12">
    <div class="masonry">
        @foreach($contents as $c)
            @php
                $g = ['#114B5F','#0D3A4A','#2D5F3F','#0D5C5C','#A1461E','#C45626','#1A3A3A','#7A4A1A'];
                $ratio = ['aspect-[3/4]','aspect-square','aspect-[4/5]','aspect-[3/4]','aspect-[4/3]','aspect-[2/3]'];
                $cover = $c->coverMedia ?: $c->gallery->first();
                $hasCover = $cover && ! str_contains($cover->url ?? '', 'placeholder');
            @endphp
            <a href="{{ url('/content/' . $c->id) }}" class="masonry-item group block bg-paper border border-line hover:border-ink transition-colors">
                <div class="{{ $ratio[$c->id % 6] }} relative overflow-hidden" style="background: linear-gradient(135deg, {{ $c->typeMeta()['color'] ?? $g[$c->id % 8] }} 0%, #1A1814 100%);">
                    @if($hasCover)
                        <img src="{{ $cover->url }}" alt="{{ $c->title }}" loading="lazy" class="absolute inset-0 w-full h-full object-cover">
                    @else
                        <div class="absolute inset-0 flex items-center justify-center text-paper/20 font-display text-5xl sm:text-7xl select-none">{{ str_pad($c->id, 2, '0', STR_PAD_LEFT) }}</div>
                    @endif
                    <div class="absolute top-2 left-2 font-mono text-[9px] uppercase tracking-[0.2em] text-paper/85">{{ $c->typeMeta()['icon'] }} {{ $c->typeMeta()['label'] }}</div>
                </div>
                <div class="px-3 py-2.5">
                    <h3 class="font-display text-sm text-ink line-clamp-2 leading-tight group-hover:text-warm transition-colors">{{ $c->title }}</h3>
                    <div class="font-mono text-[10px] text-ink-3 mt-1">@<span>{{ $c->user?->name ?? '匿名' }}</span> · {{ $c->places->first()?->city ?? '—' }}</div>
                </div>
            </a>
        @endforeach
    </div>
    <div class="pt-8 text-center">{{ $contents->links() }}</div>
</div>
@else
<div class="max-w-6xl mx-auto px-5 sm:px-8 py-20 text-center text-ink-3">
    <div class="font-display text-3xl text-ink-2 mb-2">这个标签下还没有内容</div>
</div>
@endif

@endsection
