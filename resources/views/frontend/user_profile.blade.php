@extends('frontend.layout')

@section('title', '@' . $profile->name . ' · Marker')

@section('content')

<section class="border-b border-line-2">
    <div class="max-w-6xl mx-auto px-5 sm:px-8 pt-6 pb-2">
        <div class="flex items-center gap-3 font-mono text-[10px] uppercase tracking-[0.2em] text-ink-3">
            <a href="/" class="hover:text-ink">← HOME</a>
            <span class="w-px h-3 bg-line-2"></span>
            <span>PROFILE</span>
        </div>
    </div>
</section>

<section class="border-b border-line">
    <div class="max-w-6xl mx-auto px-5 sm:px-8 py-8 sm:py-14">
        <div class="grid grid-cols-12 gap-6 sm:gap-12">
            {{-- 头像 + name --}}
            <div class="col-span-12 sm:col-span-7 flex items-start gap-4 sm:gap-5">
                <div class="w-20 h-20 sm:w-28 sm:h-28 flex-shrink-0 bg-ink text-paper font-display text-4xl sm:text-6xl flex items-center justify-center">
                    {{ mb_substr($profile->name, 0, 1) }}
                </div>
                <div class="min-w-0 flex-1">
                    <h1 class="font-display font-medium text-3xl sm:text-5xl text-ink leading-none truncate">{{ $profile->name }}</h1>
                    <p class="font-mono text-[11px] text-ink-3 mt-2 tracking-wider">@<span>{{ $profile->name }}</span></p>
                    @if($profile->bio)
                        <p class="font-display italic text-base sm:text-lg text-ink-2 mt-3 sm:mt-4 leading-relaxed max-w-2xl">{{ $profile->bio }}</p>
                    @endif
                    <p class="font-mono text-[10px] text-ink-3 mt-3 uppercase tracking-[0.15em]">Joined {{ $profile->created_at->format('M Y') }}</p>
                </div>
            </div>

            {{-- stats + follow --}}
            <div class="col-span-12 sm:col-span-5 sm:pt-2">
                <div class="grid grid-cols-2 gap-2 mb-4">
                    <div class="border border-line p-2 sm:p-3">
                        <div class="font-display text-2xl sm:text-3xl text-ink">{{ $stats['contents'] }}</div>
                        <div class="font-mono text-[10px] uppercase tracking-[0.2em] text-ink-3 mt-1">内容</div>
                    </div>
                    <div class="border border-line p-2 sm:p-3">
                        <div class="font-display text-2xl sm:text-3xl text-ink">{{ $stats['places'] }}</div>
                        <div class="font-mono text-[10px] uppercase tracking-[0.2em] text-ink-3 mt-1">地点</div>
                    </div>
                    <div class="border border-line p-2 sm:p-3">
                        <div class="font-display text-2xl sm:text-3xl text-ink">{{ $stats['followers'] }}</div>
                        <div class="font-mono text-[10px] uppercase tracking-[0.2em] text-ink-3 mt-1">粉丝</div>
                    </div>
                    <div class="border border-line p-2 sm:p-3">
                        <div class="font-display text-2xl sm:text-3xl text-ink">{{ $stats['followings'] }}</div>
                        <div class="font-mono text-[10px] uppercase tracking-[0.2em] text-ink-3 mt-1">关注</div>
                    </div>
                </div>
                @auth
                    @if(auth()->id() !== $profile->id)
                        <button id="follow-btn" data-user-id="{{ $profile->id }}" data-following="{{ $isFollowing ? '1' : '0' }}"
                                class="w-full font-mono text-[10px] uppercase tracking-[0.15em] py-2.5 border {{ $isFollowing ? 'border-line-2 text-ink-2 bg-paper-2' : 'border-ink bg-ink text-paper hover:bg-warm hover:border-warm' }} transition-colors">
                            {{ $isFollowing ? '已关注 · 点击取消' : '+ 关注' }}
                        </button>
                    @endif
                @else
                    <a href="{{ url('/login') }}" class="block w-full text-center font-mono text-[10px] uppercase tracking-[0.15em] py-2.5 border border-ink hover:bg-ink hover:text-paper transition-colors">登录后关注</a>
                @endauth
            </div>
        </div>
    </div>
</section>

{{-- 内容列表 --}}
<section class="border-b border-line-2">
    <div class="max-w-6xl mx-auto px-5 sm:px-8 py-8 sm:py-12">
        <div class="flex items-baseline justify-between mb-6 border-b border-ink pb-2">
            <span class="eyebrow">§ 01 — {{ $profile->name }} 的内容</span>
            <span class="font-mono text-[10px] text-ink-3">{{ $contents->count() }} items</span>
        </div>
        @if($contents->count() > 0)
            <div class="masonry">
                @foreach($contents as $c)
                    @php
                        $g = ['#114B5F','#0D3A4A','#2D5F3F','#0D5C5C','#A1461E','#C45626','#1A3A3A','#7A4A1A'];
                        $ratio = ['aspect-[3/4]','aspect-square','aspect-[4/5]','aspect-[3/4]','aspect-[4/3]','aspect-[2/3]'];
                        $hasCover = ! empty($c['cover']) && ! str_contains($c['cover'], 'placeholder');
                    @endphp
                    <a href="{{ $c['url'] }}" class="masonry-item group block bg-paper border border-line hover:border-ink transition-colors">
                        <div class="{{ $ratio[$c['id'] % 6] }} relative overflow-hidden" style="background: linear-gradient(135deg, {{ $c['type_color'] ?? $g[$c['id'] % 8] }} 0%, #1A1814 100%);">
                            @if($hasCover)
                                <img src="{{ $c['cover'] }}" alt="{{ $c['title'] }}" loading="lazy" class="absolute inset-0 w-full h-full object-cover">
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
        @else
            <div class="py-16 text-center text-ink-3">
                <div class="font-display text-2xl text-ink-2 mb-2">还没有内容</div>
            </div>
        @endif
    </div>
</section>

@endsection

@push('scripts')
@auth
<script>
    document.getElementById('follow-btn')?.addEventListener('click', async function () {
        const uid = this.dataset.userId;
        const wasFollowing = this.dataset.following === '1';
        const r = await fetch(`/users/${uid}/follow`, {
            method: 'POST',
            headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': document.querySelector('meta[name=csrf-token]').content, 'Accept': 'application/json' },
        });
        if (!r.ok) { if (r.status === 401) window.location = '/login'; return; }
        const j = await r.json();
        if (j.ok) {
            this.dataset.following = j.following ? '1' : '0';
            this.textContent = j.following ? '已关注 · 点击取消' : '+ 关注';
            this.className = this.className.replace(/border-line-2|text-ink-2|bg-paper-2|border-ink|bg-ink|text-paper|hover:bg-warm|hover:border-warm/g, '').trim();
            this.className += ' ' + (j.following
                ? 'border border-line-2 text-ink-2 bg-paper-2'
                : 'border border-ink bg-ink text-paper hover:bg-warm hover:border-warm');
            // update followers count
            const fEl = document.querySelectorAll('.font-display.text-3xl')[2];
            if (fEl) fEl.textContent = j.followers_count;
        }
    });
</script>
@endauth
@endpush
