@extends('frontend.layout')

@section('title', '收藏集 · Marker')

@section('content')

<section class="border-b border-line-2">
    <div class="max-w-6xl mx-auto px-5 sm:px-8 pt-4 pb-2">
        <div class="flex items-center gap-3 font-mono text-[10px] uppercase tracking-[0.2em] text-ink-3">
            <a href="/me" class="hover:text-ink transition-colors">← PROFILE</a>
            <span class="w-px h-3 bg-line-2"></span>
            <span>N°03 · COLLECTIONS</span>
        </div>
    </div>
</section>

<section class="border-b border-line">
    <div class="max-w-6xl mx-auto px-5 sm:px-8 py-6 sm:py-10">
        <h1 class="font-display font-medium text-3xl sm:text-5xl text-ink leading-none">收藏集</h1>
        <p class="font-display italic text-base sm:text-lg text-ink-2 mt-3">把好地方按主题归类。</p>
    </div>
</section>

<div class="max-w-6xl mx-auto px-5 sm:px-8 py-8">
    @forelse($collections as $i => $c)
        <a href="/me/collections" class="block border-b border-line py-4 group hover:bg-paper-2 transition-colors -mx-2 px-2">
            <div class="grid grid-cols-12 gap-3 items-baseline">
                <div class="col-span-2 sm:col-span-1 font-mono text-[10px] text-ink-3 uppercase tracking-[0.15em]">
                    N°{{ str_pad($i + 1, 2, '0', STR_PAD_LEFT) }}
                </div>
                <div class="col-span-7 sm:col-span-7">
                    <h3 class="font-display text-lg text-ink group-hover:text-warm transition-colors">{{ $c->name }}</h3>
                    <div class="font-mono text-[10px] text-ink-3 mt-1 uppercase tracking-[0.15em]">
                        {{ $c->places_count }} 个地点
                        @if($c->is_public) · 公开 @else · 私有 @endif
                    </div>
                </div>
                <div class="col-span-3 text-right font-mono text-[10px] text-ink-3 group-hover:text-ink">→</div>
            </div>
        </a>
    @empty
        <div class="py-20 text-center">
            <div class="font-display text-2xl text-ink-2 mb-2">还没有收藏集</div>
        </div>
    @endforelse
</div>
@endsection
