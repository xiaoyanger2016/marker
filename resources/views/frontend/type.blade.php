@extends('frontend.layout')

@section('title', $type['label'] . ' · Marker')

@section('content')

{{-- 期刊 header --}}
<section class="border-b border-line-2">
    <div class="max-w-6xl mx-auto px-5 sm:px-8 pt-4 pb-2">
        <div class="flex items-center gap-3 font-mono text-[10px] uppercase tracking-[0.2em] text-ink-3">
            <a href="/" class="hover:text-ink transition-colors">← BACK</a>
            <span class="w-px h-3 bg-line-2"></span>
            <span>{{ $type['icon'] }} · {{ strtoupper($type['label']) }}</span>
        </div>
    </div>
</section>

{{-- HERO --}}
<section class="border-b border-line">
    <div class="max-w-6xl mx-auto px-5 sm:px-8 py-8 sm:py-12">
        <h1 class="font-display font-medium text-4xl sm:text-6xl text-ink leading-[1.05]">
            <span class="font-mono text-[14px] sm:text-[16px] text-ink-3 align-top block mb-2 sm:mb-3">{{ $type['icon'] }}</span>
            {{ $type['label'] }}
        </h1>
        <p class="font-display italic text-lg sm:text-xl text-ink-2 mt-4 max-w-2xl">{{ $type['desc'] }}</p>
        <div class="font-mono text-[10px] text-ink-3 mt-4 uppercase tracking-[0.2em]">
            共 {{ $items->count() }} 条 · 按热度排序
        </div>
    </div>
</section>

{{-- 列表 --}}
<div class="max-w-6xl mx-auto px-5 sm:px-8 py-8">
    @if($items->isNotEmpty())
        <div class="masonry grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-4 sm:gap-6">
            @foreach($items as $item)
                @include('frontend.partials.card', ['item' => $item])
            @endforeach
        </div>
    @else
        <div class="py-20 text-center">
            <div class="font-display text-5xl text-ink-2 mb-3">{{ $type['icon'] }}</div>
            <div class="font-display text-2xl text-ink-2 mb-2">还没有{{ $type['label'] }}内容</div>
            <a href="/admin/contents/create" class="font-mono text-[11px] text-warm uppercase tracking-[0.2em] underline underline-offset-4">+ 添加一个</a>
        </div>
    @endif
</div>
@endsection
