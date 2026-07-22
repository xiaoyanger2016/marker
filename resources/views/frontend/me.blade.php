@extends('frontend.layout')

@section('title', '我的 · Marker 公路杂志')

@section('main_class')
pb-60 sm:pb-44
@endsection

@section('content')

{{-- 杂志式 profile 头部 --}}
<section class="border-b border-line-2">
    <div class="max-w-6xl mx-auto px-5 sm:px-8 py-6 sm:py-16">
        <div class="flex items-center gap-3 font-mono text-[10px] uppercase tracking-[0.2em] text-ink-3 mb-4 sm:mb-6">
            <span>PROFILE</span>
            <span class="w-px h-3 bg-line-2"></span>
            <span>N°01 · 读者档案</span>
        </div>

        <div class="grid grid-cols-12 gap-6 sm:gap-12">
            <div class="col-span-12 sm:col-span-7 flex items-start gap-4 sm:gap-5">
                <div class="w-16 h-16 sm:w-24 sm:h-24 rounded-full bg-ink text-paper font-display text-2xl sm:text-4xl flex items-center justify-center flex-shrink-0">
                    {{ mb_substr($user->name, 0, 1) }}
                </div>
                <div class="min-w-0">
                    <h1 class="font-display font-medium text-3xl sm:text-5xl text-ink leading-none truncate">{{ $user->name }}</h1>
                    <p class="font-mono text-[11px] text-ink-3 mt-2 tracking-wider truncate">{{ $user->email }}</p>
                    <p class="font-display italic text-sm sm:text-base text-ink-2 mt-2 sm:mt-4">Joined {{ $user->created_at->format('M Y') }}</p>
                </div>
            </div>

            <div class="col-span-12 sm:col-span-5 sm:pt-2">
                <div class="eyebrow mb-3">CONTRIBUTIONS</div>
                <div class="grid grid-cols-3 gap-2">
                    <div class="border border-line p-2 sm:p-3">
                        <div class="font-display text-2xl sm:text-3xl text-ink">{{ $stats['contents_total'] }}</div>
                        <div class="font-mono text-[10px] uppercase tracking-[0.2em] text-ink-3 mt-1">内容</div>
                    </div>
                    <div class="border border-line p-2 sm:p-3">
                        <div class="font-display text-2xl sm:text-3xl text-ink">{{ $stats['places_total'] }}</div>
                        <div class="font-mono text-[10px] uppercase tracking-[0.2em] text-ink-3 mt-1">地点</div>
                    </div>
                    <div class="border border-line p-2 sm:p-3">
                        <div class="font-display text-2xl sm:text-3xl text-ink">{{ $stats['collections_total'] }}</div>
                        <div class="font-mono text-[10px] uppercase tracking-[0.2em] text-ink-3 mt-1">收藏集</div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>

{{-- 索引 --}}
<section class="border-b border-line">
    <div class="max-w-6xl mx-auto px-5 sm:px-8 py-6 sm:py-8">
        <div class="eyebrow mb-4">SECTIONS</div>
        <div class="grid grid-cols-2 sm:grid-cols-4 gap-0 border-t border-b border-line">
            <a href="/me/contents" class="p-3 sm:p-5 border-r border-line hover:bg-paper-2 transition-colors">
                <div class="font-mono text-[10px] text-ink-3">N°01</div>
                <div class="font-display text-base sm:text-lg text-ink mt-1">我的内容</div>
                <div class="font-mono text-[10px] text-ink-3 mt-1">{{ $stats['contents_total'] }} items</div>
            </a>
            <a href="/me/places" class="p-3 sm:p-5 border-r border-line hover:bg-paper-2 transition-colors">
                <div class="font-mono text-[10px] text-ink-3">N°02</div>
                <div class="font-display text-base sm:text-lg text-ink mt-1">我的地点</div>
                <div class="font-mono text-[10px] text-ink-3 mt-1">{{ $stats['places_total'] }} items</div>
            </a>
            <a href="/me/collections" class="p-3 sm:p-5 border-r border-line hover:bg-paper-2 transition-colors">
                <div class="font-mono text-[10px] text-ink-3">N°03</div>
                <div class="font-display text-base sm:text-lg text-ink mt-1">收藏集</div>
                <div class="font-mono text-[10px] text-ink-3 mt-1">{{ $stats['collections_total'] }} items</div>
            </a>
            <a href="/me/activities" class="p-3 sm:p-5 hover:bg-paper-2 transition-colors">
                <div class="font-mono text-[10px] text-ink-3">N°04</div>
                <div class="font-display text-base sm:text-lg text-ink mt-1">我的活动</div>
                <div class="font-mono text-[10px] text-ink-3 mt-1">—</div>
            </a>
        </div>
    </div>
</section>

{{-- 最近地点 --}}
@if($recentPlaces->isNotEmpty())
<section class="border-b border-line">
    <div class="max-w-6xl mx-auto px-5 sm:px-8 py-8">
        <div class="flex items-baseline justify-between mb-4">
            <div class="eyebrow">§ RECENT PLACES</div>
            <a href="/me/places" class="font-mono text-[10px] uppercase tracking-[0.2em] text-ink-2 hover:text-ink">查看全部 →</a>
        </div>
        <div class="grid grid-cols-2 sm:grid-cols-3 gap-3">
            @foreach($recentPlaces as $p)
                <a href="/place/{{ $p->id }}" class="block border border-line p-3 hover:border-ink transition-colors">
                    <div class="font-mono text-[10px] text-ink-3 uppercase tracking-[0.2em]">N°{{ str_pad($p->id, 2, '0', STR_PAD_LEFT) }} · LOCATION</div>
                    <div class="font-display text-base text-ink mt-1 line-clamp-1">{{ $p->name }}</div>
                    <div class="font-mono text-[10px] text-ink-3 mt-1">{{ $p->city ?? '—' }} · {{ $p->created_at->diffForHumans() }}</div>
                </a>
            @endforeach
        </div>
    </div>
</section>
@endif

{{-- 设置 --}}
<section class="border-b border-line">
    <div class="max-w-6xl mx-auto px-5 sm:px-8 py-8">
        <div class="eyebrow mb-4">SETTINGS</div>
        <div class="space-y-px">
            <a href="/admin" class="flex items-center justify-between p-4 border border-line hover:bg-paper-2 transition-colors">
                <div>
                    <div class="font-display text-base text-ink">后台管理</div>
                    <div class="font-mono text-[10px] text-ink-3 mt-0.5">Data console</div>
                </div>
                <span class="font-mono text-xs text-ink-3">→</span>
            </a>
            @auth
                <form action="/logout" method="POST">
                    @csrf
                    <button type="submit" class="w-full flex items-center justify-between p-4 border border-line hover:bg-paper-2 transition-colors text-left">
                        <div>
                            <div class="font-display text-base text-blood">退出登录</div>
                            <div class="font-mono text-[10px] text-ink-3 mt-0.5">Sign out</div>
                        </div>
                        <span class="font-mono text-xs text-ink-3">→</span>
                    </button>
                </form>
            @endauth
        </div>
    </div>
</section>

@endsection
