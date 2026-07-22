@extends('frontend.layout')

@section('title', '标签 · Marker')

@section('content')

<section class="border-b border-line-2">
    <div class="max-w-6xl mx-auto px-5 sm:px-8 pt-6 pb-2">
        <div class="flex items-center gap-3 font-mono text-[10px] uppercase tracking-[0.2em] text-ink-3">
            <span>§ TAG</span>
            <span class="w-px h-3 bg-line-2"></span>
            <span>INDEX</span>
        </div>
    </div>
</section>

<section class="border-b border-line">
    <div class="max-w-6xl mx-auto px-5 sm:px-8 py-6 sm:py-14">
        <h1 class="font-display font-medium text-4xl sm:text-6xl text-ink leading-none">标签</h1>
        <p class="font-display italic text-base sm:text-xl text-ink-2 mt-3">按内容数倒序 · {{ $tags->count() }} 个</p>
    </div>
</section>

<div class="max-w-6xl mx-auto px-5 sm:px-8 py-8 sm:py-12">
    @if($tags->count() > 0)
        <div class="flex flex-wrap gap-2">
            @php $max = $tags->max('contents_count') ?: 1; @endphp
            @foreach($tags as $t)
                @php
                    $weight = $t->contents_count / max($max, 1);
                    $size = 11 + (int) ($weight * 8); // 11px → 19px
                @endphp
                <a href="{{ url('/tags/' . $t->slug) }}"
                   class="border border-line px-3 py-1.5 hover:border-ink hover:bg-ink hover:text-paper transition-colors font-mono"
                   style="font-size: {{ $size }}px">
                    #{{ $t->name }}<span class="opacity-60 ml-1.5" style="font-size: 10px">{{ $t->contents_count }}</span>
                </a>
            @endforeach
        </div>
    @else
        <div class="py-16 text-center text-ink-3">还没有标签</div>
    @endif
</div>

@endsection
