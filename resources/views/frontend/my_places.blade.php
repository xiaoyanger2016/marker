@extends('frontend.layout')

@section('title', '我的地点 · Marker')

@section('content')
<section class="px-4 py-3 bg-white border-b border-gray-100 sticky top-[52px] z-30">
    <div class="max-w-2xl mx-auto flex gap-2 overflow-x-auto scrollbar-hide">
        <a href="?filter=all" class="px-3 py-1.5 text-xs rounded-full {{ $filter==='all' ? 'bg-emerald-500 text-white' : 'bg-gray-100 text-gray-700' }} whitespace-nowrap">全部</a>
        <a href="?filter=wishlist" class="px-3 py-1.5 text-xs rounded-full {{ $filter==='wishlist' ? 'bg-emerald-500 text-white' : 'bg-gray-100 text-gray-700' }} whitespace-nowrap">❤️ 种草中</a>
        <a href="?filter=visited" class="px-3 py-1.5 text-xs rounded-full {{ $filter==='visited' ? 'bg-emerald-500 text-white' : 'bg-gray-100 text-gray-700' }} whitespace-nowrap">✅ 已去过</a>
        <a href="?filter=unpublic" class="px-3 py-1.5 text-xs rounded-full {{ $filter==='unpublic' ? 'bg-emerald-500 text-white' : 'bg-gray-100 text-gray-700' }} whitespace-nowrap">🔒 私有</a>
    </div>
</section>

<div class="masonry max-w-2xl mx-auto pt-3">
    @forelse($places as $p)
        @php
            $ratios = ['aspect-[3/4]','aspect-square','aspect-[4/5]','aspect-[3/4]','aspect-[4/3]','aspect-[2/3]'];
            $ratio = $ratios[$p->id % count($ratios)];
            $gradients = [['#fda4af','#fb923c'],['#86efac','#22d3ee'],['#a78bfa','#f472b6'],['#fcd34d','#fb7185'],['#5eead4','#818cf8'],['#fca5a5','#a855f7']];
            $gradient = $gradients[$p->id % count($gradients)];
            $icon = \App\Models\Place::PLACE_TYPES[$p->place_type]['icon'] ?? '📍';
        @endphp
        <a href="{{ url('/place/' . $p->id) }}" class="masonry-item group block bg-white rounded-2xl overflow-hidden shadow-sm">
            <div class="{{ $ratio }} relative" style="background: linear-gradient(135deg, {{ $gradient[0] }}, {{ $gradient[1] }});">
                <div class="w-full h-full flex items-center justify-center text-7xl opacity-90 group-hover:scale-110 transition-transform duration-300">{{ $icon }}</div>
                <div class="absolute top-2 left-2 flex flex-col gap-1">
                    @if($p->is_wishlist)
                        <span class="px-2 py-0.5 text-[10px] font-semibold text-white bg-rose-500 rounded-full">❤️ 种草</span>
                    @endif
                    @if(!$p->is_public)
                        <span class="px-2 py-0.5 text-[10px] font-medium text-gray-700 bg-white/90 rounded-full">🔒 私有</span>
                    @endif
                </div>
                @if($p->rating_label)
                    @php $rl = \App\Models\Place::RATING_LABELS[$p->rating_label] ?? null; @endphp
                    @if($rl)
                        <div class="absolute top-2 right-2">
                            <span class="px-2 py-0.5 text-[10px] font-bold text-white rounded-full" style="background:{{ $rl['color'] }}">{{ $rl['icon'] }} {{ $rl['label'] }}</span>
                        </div>
                    @endif
                @endif
            </div>
            <div class="p-3">
                <h3 class="font-semibold text-sm line-clamp-1">{{ $p->name }}</h3>
                <div class="text-[10px] text-gray-400 mt-1">{{ $p->city ?? '—' }} · {{ $p->created_at->diffForHumans() }}</div>
            </div>
        </a>
    @empty
        <div class="col-span-full py-20 text-center text-gray-400">
            <div class="text-4xl mb-2">📭</div>
            <p class="text-sm">还没有收藏地点</p>
            <a href="/" class="text-emerald-600 text-sm">去添加一个 →</a>
        </div>
    @endforelse
</div>

@if($places->hasPages())
    <div class="px-4 py-4 max-w-2xl mx-auto">
        {{ $places->links() }}
    </div>
@endif
@endsection
