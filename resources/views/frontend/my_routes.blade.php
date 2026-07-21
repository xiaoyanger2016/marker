@extends('frontend.layout')

@section('title', '我的线路 · Marker')

@section('content')

<section class="border-b border-line-2">
    <div class="max-w-6xl mx-auto px-5 sm:px-8 pt-4 pb-2">
        <div class="flex items-center gap-3 font-mono text-[10px] uppercase tracking-[0.2em] text-ink-3">
            <a href="/me" class="hover:text-ink transition-colors">← PROFILE</a>
            <span class="w-px h-3 bg-line-2"></span>
            <span>N°02 · ROUTES</span>
        </div>
    </div>
</section>

<section class="border-b border-line">
    <div class="max-w-6xl mx-auto px-5 sm:px-8 py-8 sm:py-10">
        <h1 class="font-display font-medium text-3xl sm:text-5xl text-ink leading-none">我的线路</h1>
        <p class="font-display italic text-lg text-ink-2 mt-3">你规划过的每一条公路。</p>
    </div>
</section>

<div class="max-w-6xl mx-auto px-5 sm:px-8 py-8">
    @forelse($routes as $i => $r)
        <a href="{{ url('/route/' . $r->id) }}" class="block border-b border-line py-5 group hover:bg-paper-2 transition-colors -mx-2 px-2">
            <div class="grid grid-cols-12 gap-3 items-baseline">
                <div class="col-span-2 sm:col-span-1 font-mono text-[10px] text-ink-3 uppercase tracking-[0.15em]">
                    N°{{ str_pad($i + 1, 2, '0', STR_PAD_LEFT) }}
                </div>
                <div class="col-span-7 sm:col-span-7">
                    <h3 class="font-display text-xl text-ink group-hover:text-warm transition-colors leading-tight">{{ $r->name }}</h3>
                    <div class="font-mono text-[10px] text-ink-3 mt-1.5 uppercase tracking-[0.15em]">
                        {{ \App\Models\Route::TYPES[$r->type]['label'] ?? $r->type }} · {{ $r->distance_km ?? '—' }}KM · {{ $r->places_count ?? 0 }} stops
                    </div>
                </div>
                <div class="col-span-3 text-right font-mono text-[10px] text-ink-3 group-hover:text-ink">→</div>
            </div>
        </a>
    @empty
        <div class="py-20 text-center">
            <div class="font-display text-2xl text-ink-2 mb-2">还没有线路</div>
            <p class="text-sm text-ink-3 mb-3">在后台或前端都可以新建线路</p>
        </div>
    @endforelse
</div>

@if($routes->hasPages())
    <div class="max-w-6xl mx-auto px-5 sm:px-8 py-4">
        {{ $routes->links() }}
    </div>
@endif
@endsection
