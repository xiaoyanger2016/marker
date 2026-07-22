@extends('frontend.layout')

@section('title', $q ? '搜索: ' . $q : '搜索 · Marker')

@section('content')

<section class="border-b border-line-2">
    <div class="max-w-6xl mx-auto px-5 sm:px-8 pt-6 pb-2">
        <div class="flex items-center gap-3 font-mono text-[10px] uppercase tracking-[0.2em] text-ink-3">
            <span>§ 00</span>
            <span class="w-px h-3 bg-line-2"></span>
            <span>SEARCH</span>
        </div>
    </div>
</section>

<section class="border-b border-line">
    <div class="max-w-6xl mx-auto px-5 sm:px-8 py-6 sm:py-10">
        <h1 class="font-display font-medium text-3xl sm:text-5xl text-ink leading-none">Search</h1>
        <form method="GET" action="{{ url('/search') }}" class="mt-5 flex items-center gap-3 border border-ink bg-paper px-3 py-2">
            <svg class="w-4 h-4 text-ink-3 flex-shrink-0" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5">
                <circle cx="11" cy="11" r="7"/><path d="M21 21l-4.5-4.5"/>
            </svg>
            <input type="search" name="q" value="{{ $q }}" autofocus
                   placeholder="搜：千岛湖 / 自驾 / 莫干山 / 美食..."
                   class="bg-transparent border-0 outline-none flex-1 font-mono text-base text-ink placeholder:text-ink-3">
            @if($type)<input type="hidden" name="type" value="{{ $type }}">@endif
            <button type="submit" class="font-mono text-[10px] uppercase tracking-[0.15em] px-3 py-1.5 bg-ink text-paper hover:bg-warm transition-colors">GO</button>
        </form>

        <div class="mt-3 flex items-center gap-2 text-[10px] font-mono uppercase tracking-[0.15em] text-ink-3">
            <span>FILTER:</span>
            <a href="{{ url('/search?q=' . urlencode($q)) }}" class="px-2 py-0.5 border {{ empty($type) ? 'border-ink bg-ink text-paper' : 'border-line hover:border-ink' }}">All</a>
            <a href="{{ url('/search?q=' . urlencode($q) . '&type=content') }}" class="px-2 py-0.5 border {{ $type === 'content' ? 'border-ink bg-ink text-paper' : 'border-line hover:border-ink' }}">Content</a>
            <a href="{{ url('/search?q=' . urlencode($q) . '&type=place') }}" class="px-2 py-0.5 border {{ $type === 'place' ? 'border-ink bg-ink text-paper' : 'border-line hover:border-ink' }}">Place</a>
        </div>
    </div>
</section>

@if($q)
<div class="max-w-6xl mx-auto px-5 sm:px-8 py-8 sm:py-12 space-y-12">
    {{-- 内容贴结果 --}}
    @if($results['contents']->count() > 0)
        <section>
            <div class="flex items-baseline justify-between mb-4 border-b border-ink pb-2">
                <div class="flex items-baseline gap-3">
                    <span class="eyebrow">§ CONTENT</span>
                    <span class="font-mono text-[10px] text-ink-3">{{ $results['contents']->count() }} items</span>
                </div>
            </div>
            <div class="grid grid-cols-2 sm:grid-cols-3 gap-4">
                @foreach($results['contents'] as $c)
                    @php
                        $cover = $c['cover'] ?? null;
                        $hasCover = $cover && ! str_contains($cover, 'placeholder');
                    @endphp
                    <a href="{{ $c['url'] }}" class="group block bg-paper border border-line hover:border-ink transition-colors">
                        <div class="aspect-[3/4] relative overflow-hidden" style="background: linear-gradient(135deg, {{ $c['type_color'] ?? '#114B5F' }} 0%, #1A1814 100%);">
                            @if($hasCover)
                                <img src="{{ $cover }}" alt="{{ $c['title'] }}" loading="lazy" class="absolute inset-0 w-full h-full object-cover">
                            @else
                                <div class="absolute inset-0 flex items-center justify-center text-paper/20 font-display text-5xl sm:text-7xl select-none">{{ str_pad($c['id'], 2, '0', STR_PAD_LEFT) }}</div>
                            @endif
                            <div class="absolute top-2 left-2 font-mono text-[9px] uppercase tracking-[0.2em] text-paper/85">{{ $c['type_icon'] }} {{ $c['type_label'] }}</div>
                        </div>
                        <div class="px-3 py-2.5">
                            <h3 class="font-display text-sm text-ink line-clamp-2 leading-tight group-hover:text-warm transition-colors">{{ $c['title'] }}</h3>
                            <div class="font-mono text-[10px] text-ink-3 mt-1">{{ $c['city'] ?? '—' }}</div>
                        </div>
                    </a>
                @endforeach
            </div>
        </section>
    @endif

    {{-- 地点结果 --}}
    @if($results['places']->count() > 0)
        <section>
            <div class="flex items-baseline justify-between mb-4 border-b border-ink pb-2">
                <div class="flex items-baseline gap-3">
                    <span class="eyebrow">§ PLACE</span>
                    <span class="font-mono text-[10px] text-ink-3">{{ $results['places']->count() }} items</span>
                </div>
            </div>
            <div class="grid grid-cols-2 sm:grid-cols-4 gap-3">
                @foreach($results['places'] as $p)
                    <a href="{{ $p['url'] }}" class="group block border border-line p-3 hover:border-ink transition-colors">
                        <div class="font-mono text-[9px] text-ink-3 mb-1">N°{{ str_pad($p['id'], 2, '0', STR_PAD_LEFT) }}</div>
                        <h3 class="font-display text-sm text-ink line-clamp-1 group-hover:text-warm transition-colors">{{ $p['name'] }}</h3>
                        @if($p['city'])
                            <div class="font-mono text-[10px] text-ink-3 mt-1">{{ $p['city'] }}</div>
                        @endif
                    </a>
                @endforeach
            </div>
        </section>
    @endif

    @if($results['contents']->count() === 0 && $results['places']->count() === 0)
        <div class="py-16 text-center">
            <div class="font-display text-3xl text-ink-2 mb-2">没找到 "{{ $q }}"</div>
            <p class="text-sm text-ink-3">试试 莫干山 / 千岛湖 / 自驾 / 露营 / 美食 这些关键词</p>
        </div>
    @endif
</div>
@else
<div class="max-w-6xl mx-auto px-5 sm:px-8 py-20 text-center">
    <div class="font-display text-3xl text-ink-2 mb-3">输入关键词开始搜索</div>
    <p class="text-sm text-ink-3 mb-6">支持中文分词 · 全文匹配内容/地点/描述</p>
    <div class="flex flex-wrap gap-2 justify-center max-w-lg mx-auto">
        @foreach(['千岛湖', '莫干山', '自驾', '露营', '美食', '徒步', '拍照', '日出'] as $kw)
            <a href="{{ url('/search?q=' . urlencode($kw)) }}" class="px-3 py-1.5 border border-line hover:border-ink font-mono text-xs text-ink-2 hover:text-ink transition-colors">{{ $kw }}</a>
        @endforeach
    </div>
</div>
@endif

@endsection
