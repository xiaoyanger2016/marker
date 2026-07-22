@extends('frontend.layout')

@section('title', '我的地点 · Marker')

@section('content')

{{-- 期刊 header --}}
<section class="border-b border-line-2">
    <div class="max-w-6xl mx-auto px-5 sm:px-8 pt-4 pb-2">
        <div class="flex items-center gap-3 font-mono text-[10px] uppercase tracking-[0.2em] text-ink-3">
            <a href="/me" class="hover:text-ink transition-colors">← PROFILE</a>
            <span class="w-px h-3 bg-line-2"></span>
            <span>N°01 · PLACES</span>
        </div>
    </div>
</section>

<section class="border-b border-line">
    <div class="max-w-6xl mx-auto px-5 sm:px-8 py-6 sm:py-10">
        <h1 class="font-display font-medium text-3xl sm:text-5xl text-ink leading-none">我的地点</h1>
        <p class="font-display italic text-base sm:text-lg text-ink-2 mt-3">所有你收藏过的地方。</p>
    </div>
</section>

{{-- 筛选条 --}}
<section class="sticky top-14 z-30 bg-paper/95 backdrop-blur-sm border-b border-line">
    <div class="max-w-6xl mx-auto px-5 sm:px-8 py-3">
        <div class="flex gap-2 overflow-x-auto scrollbar-hide">
            <a href="?filter=all" class="px-3 py-1.5 font-mono text-[10px] uppercase tracking-[0.15em] border {{ $filter==='all' ? 'bg-ink text-paper border-ink' : 'border-line-2 text-ink-2 hover:border-ink' }} whitespace-nowrap">全部</a>
            <a href="?filter=wishlist" class="px-3 py-1.5 font-mono text-[10px] uppercase tracking-[0.15em] border {{ $filter==='wishlist' ? 'bg-ink text-paper border-ink' : 'border-line-2 text-ink-2 hover:border-ink' }} whitespace-nowrap">种草中</a>
            <a href="?filter=visited" class="px-3 py-1.5 font-mono text-[10px] uppercase tracking-[0.15em] border {{ $filter==='visited' ? 'bg-ink text-paper border-ink' : 'border-line-2 text-ink-2 hover:border-ink' }} whitespace-nowrap">已去过</a>
            <a href="?filter=unpublic" class="px-3 py-1.5 font-mono text-[10px] uppercase tracking-[0.15em] border {{ $filter==='unpublic' ? 'bg-ink text-paper border-ink' : 'border-line-2 text-ink-2 hover:border-ink' }} whitespace-nowrap">私有</a>
        </div>
    </div>
</section>

{{-- 目录式列表（不是瀑布流，更编辑感） --}}
<div class="max-w-6xl mx-auto px-5 sm:px-8 py-8">
    @forelse($places as $i => $p)
        <a href="{{ url('/place/' . $p->id) }}" class="block border-b border-line py-4 group hover:bg-paper-2 transition-colors -mx-2 px-2">
            <div class="grid grid-cols-12 gap-3 items-baseline">
                <div class="col-span-2 sm:col-span-1 font-mono text-[10px] text-ink-3 uppercase tracking-[0.15em]">
                    N°{{ str_pad($i + 1, 2, '0', STR_PAD_LEFT) }}
                </div>
                <div class="col-span-7 sm:col-span-7">
                    <h3 class="font-display text-lg text-ink group-hover:text-warm transition-colors leading-tight">{{ $p->name }}</h3>
                    <div class="font-mono text-[10px] text-ink-3 mt-1.5 uppercase tracking-[0.15em]">
                        N°{{ str_pad($p->id, 2, '0', STR_PAD_LEFT) }} · LOCATION · {{ $p->city ?? '—' }} · {{ $p->created_at->diffForHumans() }}
                    </div>
                </div>
                <div class="col-span-3 text-right flex flex-col items-end gap-1">
                    @if($p->is_wishlist)
                        <span class="tag tag-warm">种草</span>
                    @endif
                    @if($p->is_visited)
                        <span class="tag tag-grass">去过</span>
                    @endif
                    @if(! $p->is_public)
                        <span class="tag">私有</span>
                    @endif
                </div>
            </div>
        </a>
    @empty
        <div class="py-20 text-center">
            <div class="font-display text-2xl text-ink-2 mb-2">还没有收藏地点</div>
            <a href="/" class="font-mono text-[11px] text-warm uppercase tracking-[0.2em]">去添加一个 →</a>
        </div>
    @endforelse
</div>

@if($places->hasPages())
    <div class="max-w-6xl mx-auto px-5 sm:px-8 py-4">
        {{ $places->links() }}
    </div>
@endif
@endsection
