@extends('frontend.layout')

@section('title', 'Marker · 公路旅行私人地图')

@section('content')

{{-- =================================================================
   01 · MASTHEAD
   编辑感 masthead：编号 + 期刊感 + 真实文案（不"Hi 开车人"）
   ================================================================= --}}
<section class="border-b border-line-2">
    <div class="max-w-6xl mx-auto px-5 sm:px-8 pt-6 pb-2">
        {{-- 期刊标头：期数 / 日期 / 坐标 --}}
        <div class="flex items-center justify-between text-[10px] font-mono uppercase tracking-[0.2em] text-ink-3 mb-6">
            <div class="flex items-center gap-3">
                <span>{{ __('ui.home_masthead_vol') }}</span>
                <span class="w-px h-3 bg-line-2"></span>
                <span>{{ now()->format('Y/m/d') }}</span>
            </div>
            <div class="hidden sm:flex items-center gap-3">
                <span>30°15'N</span>
                <span class="w-px h-3 bg-line-2"></span>
                <span>120°10'E</span>
                <span class="w-px h-3 bg-line-2"></span>
                <span>EDITED BY YOU</span>
            </div>
        </div>
    </div>
</section>

{{-- =================================================================
   02 · HERO 标题（大衬线 + 不对称布局）
   标题左 7/12，引语右 4/12 错位下行
   ================================================================= --}}
<section class="border-b border-line">
    <div class="max-w-6xl mx-auto px-5 sm:px-8 py-6 sm:py-14">
        <div class="grid grid-cols-12 gap-4 sm:gap-8">
            <div class="col-span-12 sm:col-span-8">
                <h1 class="font-display font-medium text-[2.125rem] sm:text-[4rem] leading-[1.05] tracking-tight text-ink">
                    {{ __('ui.home_hero_h1_1') }}<br>
                    {{ __('ui.home_hero_h1_2') }}<br>
                    <span class="serif-italic text-warm">{{ __('ui.home_hero_h1_3') }}</span>
                </h1>
            </div>
            <div class="col-span-12 sm:col-span-4 sm:pt-12 flex flex-col justify-end">
                <p class="text-sm leading-relaxed text-ink-2 border-l border-line-2 pl-4">
                    {{ __('ui.home_hero_quote') }}
                </p>
                <div class="mt-4 flex items-center gap-2 text-[10px] font-mono uppercase tracking-[0.2em] text-ink-3">
                    <span class="bullet-warm"></span>
                    <span>39 places · 6 routes · 2 readers</span>
                </div>
            </div>
        </div>
    </div>
</section>

{{-- =================================================================
   03 · SEARCH（编辑感搜索框，胶囊 + mono 占位）
   ================================================================= --}}
<section class="border-b border-line">
    <div class="max-w-6xl mx-auto px-5 sm:px-8 py-6">
        <div class="flex items-center gap-3 border border-ink px-4 py-2.5 bg-paper">
            <svg class="w-4 h-4 text-ink-3" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5">
                <circle cx="11" cy="11" r="7"/>
                <path d="M21 21l-4.5-4.5"/>
            </svg>
            <input type="search" placeholder="{{ __('ui.home_search_ph') }}"
                   class="bg-transparent border-0 outline-none flex-1 font-mono text-sm placeholder:text-ink-3 text-ink" id="search-input">
            <span class="hidden sm:inline font-mono text-[10px] text-ink-3 border border-line-2 px-1.5 py-0.5">⌘ K</span>
        </div>
    </div>
</section>

{{-- =================================================================
   04 · TYPE INDEX（编辑感索引：8 个类型水平排列 + 数字编号）
   不用 8 个圆角色块，改成报纸目录样式
   ================================================================= --}}
<section class="border-b border-line">
    <div class="max-w-6xl mx-auto px-5 sm:px-8 py-6">
        <div class="flex items-baseline justify-between mb-4">
            <span class="eyebrow">{{ __('ui.home_section_types') }}</span>
            <span class="font-mono text-[10px] text-ink-3">{{ __('ui.home_picks_counter') }}</span>
        </div>
        <div class="grid grid-cols-4 lg:grid-cols-8 gap-px bg-line border border-line">
            @foreach($types as $i => $t)
                <a href="{{ url('/type/' . $t['key']) }}"
                   class="group min-w-0 bg-paper hover:bg-paper-2 transition-colors px-2 sm:px-4 py-3 sm:py-5 flex flex-col">
                    <span class="font-mono text-[9px] sm:text-[10px] tracking-wider text-ink-3 mb-1.5 sm:mb-2">{{ $t['icon'] }}</span>
                    <span class="font-display text-[13px] sm:text-lg text-ink group-hover:text-warm transition-colors leading-tight truncate">{{ $t['label'] }}</span>
                    <span class="font-mono text-[8px] sm:text-[10px] text-ink-3 mt-1 sm:mt-1.5 italic truncate hidden sm:block">{{ $t['en'] }}</span>
                </a>
            @endforeach
        </div>
    </div>
</section>

{{-- =================================================================
   05 · EDITORIAL PICKS（编辑精选 - 不对称网格）
   Phase 18 · Bug 1: 改成 admin 手动 pinned
   - 有人工 picks → 按 sort 排序, 无限量
   - 没有 picks → 随机 10 条 (视图会有 "AUTO" 标识)
   - 首页只展示前 3 条, 其余 "查看更多 →" 链到 /picks
   ================================================================= --}}
@php
    $hero = $picks->first();
    $side = $picks->slice(1, 2);
@endphp
@if($hero)
<section class="border-b border-line">
    <div class="max-w-6xl mx-auto px-5 sm:px-8 py-8 sm:py-12">
        <div class="flex items-baseline justify-between mb-6">
            <div class="flex items-baseline gap-3">
                <span class="eyebrow">{{ __('ui.home_section_picks') }}</span>
                @if($picksRandom)
                    <span class="font-mono text-[9px] uppercase tracking-[0.15em] text-ink-3/70">· AUTO (no picks yet)</span>
                @endif
            </div>
            <a href="{{ url('/picks') }}" class="font-mono text-[10px] text-ink-2 hover:text-ink transition-colors underline underline-offset-4">VIEW ALL · {{ $picks->count() }} →</a>
        </div>

        <div class="grid grid-cols-12 gap-4 sm:gap-6">
            {{-- 大卡 7 列 --}}
            <a href="{{ url('/content/' . $hero['id']) }}" class="col-span-12 sm:col-span-7 group block">
                <div class="aspect-[4/5] sm:aspect-[5/6] overflow-hidden border border-line">
                    <div class="w-full h-full relative" style="background: linear-gradient(135deg, {{ $hero['type_color'] ?? '#114B5F' }} 0%, #1A1814 100%);">
                        @if(! empty($hero['cover']) && ! str_contains($hero['cover'], 'placeholder'))
                            <img src="{{ $hero['cover'] }}" alt="{{ $hero['title'] }}" class="absolute inset-0 w-full h-full object-cover">
                        @else
                            <div class="absolute inset-0 flex items-center justify-center">
                                <span class="font-display text-[6rem] sm:text-[12rem] text-paper/15 leading-none select-none">N°{{ str_pad($hero['id'], 2, '0', STR_PAD_LEFT) }}</span>
                            </div>
                        @endif
                        <div class="absolute top-3 left-3 flex items-center gap-2">
                            <span class="font-mono text-[10px] uppercase tracking-[0.2em] text-paper/85">{{ $hero['type_icon'] }}</span>
                            <span class="w-px h-3 bg-paper/30"></span>
                            <span class="font-mono text-[10px] uppercase tracking-[0.2em] text-paper/85">{{ $hero['type_label'] }}</span>
                        </div>
                        @if(! empty($hero['rating_label_text']))
                            <div class="absolute top-3 right-3 font-mono text-[10px] uppercase tracking-[0.2em] px-2 py-1 border border-paper/50 text-paper bg-ink/30">
                                {{ $hero['rating_label_text'] }}
                            </div>
                        @endif
                        <div class="absolute bottom-0 left-0 right-0 p-5 sm:p-6 bg-gradient-to-t from-ink/80 to-transparent">
                            <h2 class="font-display text-2xl sm:text-4xl text-paper leading-tight mb-1">{{ $hero['title'] }}</h2>
                            @if(! empty($hero['summary']))
                                <p class="font-sans text-sm text-paper/80 line-clamp-2 max-w-md">{{ \Illuminate\Support\Str::limit(strip_tags($hero['summary']), 80) }}</p>
                            @endif
                        </div>
                    </div>
                </div>
                <div class="mt-3 flex items-center justify-between font-mono text-[10px] text-ink-3">
                    <span>{{ $hero['city'] ?? '—' }}</span>
                    <span class="text-warm underline underline-offset-4">READ MORE →</span>
                </div>
            </a>

            {{-- 右侧 2 小卡 5 列 --}}
            <div class="col-span-12 sm:col-span-5 flex flex-col gap-4 sm:gap-6">
                @foreach($side as $i => $item)
                    <a href="{{ url('/content/' . $item['id']) }}" class="group flex-1 flex gap-3 sm:gap-4 border border-line p-3 hover:border-ink transition-colors">
                        <div class="w-20 sm:w-28 flex-shrink-0 aspect-square relative" style="background: linear-gradient(135deg, {{ $item['type_color'] ?? '#114B5F' }} 0%, #1A1814 100%);">
                            <div class="absolute inset-0 flex items-center justify-center text-paper/25 font-display text-4xl sm:text-3xl select-none">
                                N°{{ str_pad($item['id'], 2, '0', STR_PAD_LEFT) }}
                            </div>
                            <div class="absolute top-1 left-1 font-mono text-[8px] text-paper/70">{{ $item['type_icon'] }}</div>
                        </div>
                        <div class="flex-1 min-w-0 flex flex-col">
                            <span class="eyebrow">{{ $item['type_label'] }}</span>
                            <h3 class="font-display text-lg sm:text-xl text-ink mt-1 line-clamp-2 leading-tight">{{ $item['title'] }}</h3>
                            <div class="mt-auto pt-2 flex items-center justify-between font-mono text-[10px] text-ink-3">
                                <span>{{ $item['city'] ?? '—' }}</span>
                                <span class="text-ink-2 group-hover:text-warm">→</span>
                            </div>
                        </div>
                    </a>
                @endforeach
            </div>
        </div>
    </div>
</section>
@endif

{{-- =================================================================
   06 · ALL FEED（8 大类 tab 切换 + 编辑感瀑布流）
   Phase 17：改成 server-rendered 30 条，?feed=type 切换
   ================================================================= --}}
<section class="border-b border-line-2" id="feed-section">
    <div class="max-w-6xl mx-auto px-5 sm:px-8 py-8 sm:py-12">
        <div class="flex items-baseline justify-between mb-6">
            <span class="eyebrow">{{ __('ui.home_section_feed') }}</span>
            <span class="font-mono text-[10px] text-ink-3">{{ count($feedItems) }} {{ __('ui.home_feed_items') }}</span>
        </div>

        {{-- 8 大类 tab：左滑滚动 + active 边框 --}}
        <div class="flex items-stretch gap-0 border border-line mb-6 overflow-x-auto no-scrollbar">
            <a href="{{ url('/') }}"
               class="feed-tab flex-shrink-0 px-3 sm:px-4 py-2 text-center font-mono text-[10px] sm:text-[11px] uppercase tracking-[0.15em] border-r border-line whitespace-nowrap {{ empty($feedType) ? 'bg-ink text-paper' : 'text-ink-2 hover:text-ink hover:bg-paper-2' }}">
                {{ __('ui.home_feed_all') }}
            </a>
            @foreach($types as $t)
                <a href="{{ url('/?feed=' . $t['key']) }}"
                   class="feed-tab flex-shrink-0 px-3 sm:px-4 py-2 text-center font-mono text-[10px] sm:text-[11px] uppercase tracking-[0.15em] border-r border-line whitespace-nowrap {{ ($feedType ?? '') === $t['key'] ? 'bg-ink text-paper' : 'text-ink-2 hover:text-ink hover:bg-paper-2' }}">
                    <span class="text-[9px] opacity-60 mr-1">{{ $t['icon'] }}</span>{{ $t['label'] }}
                </a>
            @endforeach
        </div>

        @if(count($feedItems) > 0)
            <div class="masonry">
                @foreach($feedItems as $item)
                    @php
                        $g = ['#114B5F', '#0D3A4A', '#2D5F3F', '#0D5C5C', '#A1461E', '#C45626', '#1A3A3A', '#7A4A1A'];
                        $ratio = ['aspect-[3/4]','aspect-square','aspect-[4/5]','aspect-[3/4]','aspect-[4/3]','aspect-[2/3]'];
                        $gi = $item['id'] % count($g);
                        $ri = $item['id'] % count($ratio);
                        $numStr = str_pad($item['id'], 2, '0', STR_PAD_LEFT);
                    @endphp
                    <a href="{{ $item['url'] }}" class="masonry-item group block bg-paper border border-line hover:border-ink transition-colors">
                        <div class="{{ $ratio[$ri] }} relative overflow-hidden" style="background: linear-gradient(135deg, {{ $item['type_color'] ?? $g[$gi] }} 0%, #1A1814 100%);">
                            {{-- cover (if has) --}}
                            @if(! empty($item['cover']) && ! str_contains($item['cover'], 'placeholder'))
                                <img src="{{ $item['cover'] }}" alt="{{ $item['title'] }}" loading="lazy" class="absolute inset-0 w-full h-full object-cover group-hover:scale-105 transition-transform duration-500">
                            @else
                                <div class="absolute inset-0 flex items-center justify-center">
                                    <span class="font-display text-[5rem] sm:text-[10rem] leading-none text-paper/15 group-hover:text-paper/25 transition-colors select-none">{{ $numStr }}</span>
                                </div>
                            @endif
                            <div class="absolute top-2 left-2 flex items-center gap-1.5 font-mono text-[9px] uppercase tracking-[0.2em] text-paper/85">
                                <span>{{ $item['type_icon'] }}</span>
                                <span class="w-px h-2.5 bg-paper/30"></span>
                                <span>{{ $item['type_label'] }}</span>
                            </div>
                            @if(! empty($item['rating_label_text']))
                                <div class="absolute top-2 right-2 font-mono text-[9px] uppercase tracking-[0.2em] px-1.5 py-0.5 border border-paper/50 text-paper bg-ink/30">
                                    {{ $item['rating_label_text'] }}
                                </div>
                            @endif
                            @if($item['is_multiple'])
                                <div class="absolute bottom-2 left-2 font-mono text-[9px] uppercase tracking-[0.2em] text-paper/85 bg-ink/30 px-1.5 py-0.5">
                                    {{ __('ui.home_feed_multi_places') }} · {{ $item['places_count'] }}
                                </div>
                            @endif
                        </div>
                        <div class="px-3 py-3 border-t border-line">
                            <h3 class="font-display text-base text-ink leading-tight line-clamp-1">{{ $item['title'] }}</h3>
                            @if(! empty($item['summary']))
                                <p class="text-xs text-ink-3 mt-1 line-clamp-2 leading-relaxed">{{ \Illuminate\Support\Str::limit(strip_tags($item['summary']), 60) }}</p>
                            @endif
                            <div class="mt-2 flex items-center justify-between font-mono text-[10px] text-ink-3">
                                <span>{{ $item['city'] ?? '—' }}</span>
                                <span class="opacity-0 group-hover:opacity-100 text-warm transition-opacity">→</span>
                            </div>
                        </div>
                    </a>
                @endforeach
            </div>
            <div class="py-8 text-center font-mono text-[10px] uppercase tracking-[0.2em] text-ink-3">{{ __('ui.home_feed_end') }}</div>
        @else
            <div class="py-16 text-center text-ink-3">
                <div class="font-display text-3xl text-ink-2 mb-2">{{ __('ui.home_feed_empty_title') }}</div>
                <p class="text-sm mb-4">{{ __('ui.home_feed_empty_desc') }}</p>
                @auth
                    <a href="/admin/contents/create" class="font-mono text-[11px] text-warm uppercase tracking-[0.2em] underline underline-offset-4">{{ __('ui.home_feed_empty_cta') }}</a>
                @else
                    <a href="/login" class="font-mono text-[11px] text-warm uppercase tracking-[0.2em] underline underline-offset-4">{{ __('ui.home_feed_empty_login') }}</a>
                @endauth
            </div>
        @endif
    </div>
</section>

{{-- =================================================================
   07 · FOOTER NOTE（编辑感底注）
   ================================================================= --}}
<footer class="border-t border-line-2">
    <div class="max-w-6xl mx-auto px-5 sm:px-8 py-8 flex flex-col sm:flex-row items-start sm:items-center justify-between gap-3">
        <div class="font-mono text-[10px] uppercase tracking-[0.2em] text-ink-3">
            Marker · 公路杂志 · Vol. 01
        </div>
        <div class="font-mono text-[10px] uppercase tracking-[0.2em] text-ink-3">
            Made with hands · No algorithm
        </div>
    </div>
</footer>

@endsection

@push('scripts')
<style>
/* Hide scrollbar for tab strip on mobile */
.no-scrollbar::-webkit-scrollbar { display: none; }
.no-scrollbar { -ms-overflow-style: none; scrollbar-width: none; }
</style>
@endpush
