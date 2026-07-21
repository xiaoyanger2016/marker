@extends('frontend.layout')

@section('title', $activity->title . ' · 活动 · Marker')

@section('content')

{{-- 杂志式 hero：编辑感排版 --}}
<section class="border-b border-line-2 bg-paper-2">
    <div class="max-w-5xl mx-auto px-5 sm:px-8 py-6 sm:py-12">
        <div class="flex items-center gap-3 font-mono text-[10px] uppercase tracking-[0.2em] text-ink-3 mb-4">
            <a href="/activities" class="hover:text-ink transition-colors">← BACK</a>
            <span class="w-px h-3 bg-line-2"></span>
            <span>N°{{ str_pad($activity->id, 3, '0', STR_PAD_LEFT) }}</span>
            <span class="w-px h-3 bg-line-2"></span>
            <span>EVENT</span>
        </div>

        <h1 class="font-display font-medium text-2xl sm:text-5xl leading-[1.15] sm:leading-[1.1] text-ink max-w-3xl">
            {{ $activity->title }}
        </h1>

        <div class="mt-6 grid grid-cols-2 sm:grid-cols-4 gap-4 sm:gap-6 max-w-3xl">
            <div>
                <div class="eyebrow">出发</div>
                <div class="font-display text-base sm:text-xl text-ink mt-1">{{ $activity->start_at?->format('m/d H:i') }}</div>
            </div>
            <div>
                <div class="eyebrow">报名截止</div>
                <div class="font-display text-base sm:text-xl text-warm mt-1">{{ $activity->signup_deadline?->format('m/d H:i') ?? '—' }}</div>
            </div>
            <div>
                <div class="eyebrow">人数</div>
                <div class="font-display text-base sm:text-xl text-ink mt-1">
                    {{ $activity->joined_participants_count }}<span class="text-ink-3 text-sm">/{{ $activity->max_participants > 0 ? $activity->max_participants : '∞' }}</span>
                </div>
            </div>
            <div>
                <div class="eyebrow">状态</div>
                <div class="font-display text-base sm:text-xl text-ink mt-1">{{ $activity->status_label }}</div>
            </div>
        </div>

        <div class="mt-6 pt-6 border-t border-line flex items-center justify-between font-mono text-[10px] uppercase tracking-[0.15em] text-ink-3">
            <span>BY {{ $activity->user->name ?? '匿名' }}</span>
            <span>{{ $activity->view_count }} views</span>
        </div>

        @auth
            @if($activity->user_id === auth()->id())
                <div class="mt-3 font-mono text-[10px]">
                    <a href="/admin/activities/{{ $activity->id }}/edit" class="text-ink-2 underline underline-offset-4 hover:text-ink">后台编辑 →</a>
                </div>
            @endif
        @endauth
    </div>
</section>

<div class="max-w-5xl mx-auto px-5 sm:px-8 py-6 sm:py-12 space-y-6 sm:space-y-12">

    {{-- 详情 --}}
    @if($activity->description)
    <section>
        <div class="eyebrow mb-3">§ DETAILS</div>
        <div class="border-l-2 border-warm pl-4 sm:pl-5 max-w-3xl">
            <p class="font-display text-base sm:text-xl leading-relaxed text-ink whitespace-pre-line">{{ $activity->description }}</p>
        </div>
    </section>
    @endif

    {{-- 关键信息：两栏对照（编辑感） --}}
    <section class="grid grid-cols-1 sm:grid-cols-2 gap-6 sm:gap-12">
        <div>
            <div class="eyebrow mb-3">§ LOGISTICS</div>
            <dl class="space-y-3 font-mono text-sm">
                @if($activity->meeting_point)
                <div class="flex justify-between border-b border-line pb-2">
                    <dt class="text-ink-3 flex-shrink-0">集合</dt>
                    <dd class="text-ink text-right ml-3">{{ $activity->meeting_point }}</dd>
                </div>
                @endif
                @if($activity->transport)
                <div class="flex justify-between border-b border-line pb-2">
                    <dt class="text-ink-3">方式</dt>
                    <dd class="text-ink">{{ $activity->transport }}</dd>
                </div>
                @endif
                <div class="flex justify-between border-b border-line pb-2">
                    <dt class="text-ink-3">城市</dt>
                    <dd class="text-ink">{{ $activity->region_name ?: '不限' }}</dd>
                </div>
            </dl>
        </div>

        {{-- 费用 --}}
        <div>
            <div class="eyebrow mb-3">§ FEE</div>
            <div class="font-display text-3xl sm:text-5xl {{ $activity->fee > 0 ? 'text-warm' : 'text-grass' }} mb-1">
                {{ $activity->fee > 0 ? '¥' . number_format($activity->fee, 0) : '免费' }}
            </div>
            @if($activity->fee > 0)
                <div class="font-mono text-[10px] uppercase tracking-[0.2em] text-ink-3 mb-3">/ person</div>
            @endif
            <dl class="space-y-2 font-mono text-xs">
                @if($activity->fee_includes)
                <div>
                    <span class="text-grass">+</span>
                    <span class="text-ink-2"> 包含：</span>
                    <span class="text-ink">{{ $activity->fee_includes }}</span>
                </div>
                @endif
                @if($activity->fee_excludes)
                <div>
                    <span class="text-blood">−</span>
                    <span class="text-ink-2"> 不含：</span>
                    <span class="text-ink">{{ $activity->fee_excludes }}</span>
                </div>
                @endif
            </dl>
        </div>
    </section>

    {{-- 关联内容 --}}
    @if($activity->place || $activity->route)
    <section>
        <div class="eyebrow mb-3">§ LINKED</div>
        <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
            @if($activity->place)
                <a href="/place/{{ $activity->place->id }}" class="block border border-line p-4 hover:border-ink transition-colors">
                    <div class="font-mono text-[10px] uppercase tracking-[0.2em] text-ink-3">地点 / PLACE</div>
                    <div class="font-display text-lg text-ink mt-1">{{ $activity->place->name }}</div>
                </a>
            @endif
            @if($activity->route)
                <a href="/route/{{ $activity->route->id }}" class="block border border-line p-4 hover:border-ink transition-colors">
                    <div class="font-mono text-[10px] uppercase tracking-[0.2em] text-ink-3">线路 / ROUTE</div>
                    <div class="font-display text-lg text-ink mt-1">{{ $activity->route->name }}</div>
                </a>
            @endif
        </div>
    </section>
    @endif

    {{-- 已报名名单：编辑感横排 + 编号 --}}
    <section>
        <div class="flex items-baseline justify-between mb-3">
            <div class="eyebrow">§ ROSTER</div>
            <span class="font-mono text-[10px] text-ink-3">{{ $participants->count() }} 已报名</span>
        </div>
        @if($participants->isNotEmpty())
            <div class="border-t border-line">
                @foreach($participants as $i => $p)
                    <div class="flex items-center gap-3 py-3 border-b border-line">
                        <span class="font-mono text-[10px] text-ink-3 w-8">N°{{ str_pad($i + 1, 2, '0', STR_PAD_LEFT) }}</span>
                        <div class="w-8 h-8 rounded-full bg-ink text-paper font-mono text-xs flex items-center justify-center">
                            {{ mb_substr($p->user->name ?? '?', 0, 1) }}
                        </div>
                        <div class="flex-1 min-w-0">
                            <div class="font-display text-sm text-ink">{{ $p->user->name ?? '匿名' }}</div>
                            @if($p->note)
                                <div class="font-mono text-[10px] text-ink-3 italic mt-0.5">"{{ $p->note }}"</div>
                            @endif
                        </div>
                        @if($p->people_count > 1)
                            <span class="font-mono text-[10px] text-warm">×{{ $p->people_count }}</span>
                        @endif
                    </div>
                @endforeach
            </div>
        @else
            <p class="text-sm text-ink-3 italic font-display">还没有人报名，做第一个吧。</p>
        @endif
    </section>
</div>

{{-- 底部报名按钮 --}}
<div class="fixed bottom-[64px] sm:bottom-[68px] left-0 right-0 z-40 px-4 pb-3 safe-bottom bg-gradient-to-t from-paper via-paper to-transparent pt-4">
@auth
    @if($activity->user_id === auth()->id())
        <div class="max-w-5xl mx-auto flex gap-2">
            <div class="flex-1 text-center font-mono text-[10px] uppercase tracking-[0.2em] text-ink-3 py-3 border border-line">你发起的活动</div>
            @if(! $activity->is_expired)
                <form method="POST" action="/activities/{{ $activity->id }}" onsubmit="return confirm('确定取消活动？')">
                    @csrf
                    @method('DELETE')
                    <button type="submit" class="btn btn-ghost btn-sm">取消</button>
                </form>
            @endif
        </div>
    @elseif($activity->is_expired)
        <div class="max-w-5xl mx-auto">
            <div class="text-center font-mono text-[10px] uppercase tracking-[0.2em] text-ink-3 py-3 border border-line">活动已截止 / EXPIRED</div>
        </div>
    @else
        <div class="max-w-5xl mx-auto flex gap-2">
            @if($isJoined)
                <form method="POST" action="/activities/{{ $activity->id }}/leave" class="flex-1">
                    @csrf
                    <button type="submit" class="w-full py-3 border border-ink text-ink font-mono text-[10px] uppercase tracking-[0.2em]">已报名 · 点击取消</button>
                </form>
            @else
                <form method="POST" action="/activities/{{ $activity->id }}/join" class="flex-1 flex gap-2">
                    @csrf
                    <select name="people_count" class="input max-w-[100px] py-2 text-sm border-b border-ink">
                        @for($i=1; $i<=5; $i++)<option value="{{ $i }}">{{ $i }} 人</option>@endfor
                    </select>
                    <button type="submit" class="flex-1 btn btn-warm">报名 FOLLOW</button>
                </form>
            @endif
        </div>
    @endif
@else
    <div class="max-w-5xl mx-auto">
        <a href="/login" class="btn btn-primary w-full">登录后报名</a>
    </div>
@endauth
</div>

@if(session('ok'))
    <div class="fixed top-16 left-0 right-0 z-50 px-4">
        <div class="max-w-sm mx-auto p-3 bg-ink text-paper font-mono text-[10px] uppercase tracking-[0.2em] text-center">{{ session('ok') }}</div>
    </div>
@endif
@if(session('error'))
    <div class="fixed top-16 left-0 right-0 z-50 px-4">
        <div class="max-w-sm mx-auto p-3 bg-blood text-paper font-mono text-[10px] uppercase tracking-[0.2em] text-center">{{ session('error') }}</div>
    </div>
@endif
@endsection

@section('main_class', 'pb-32')
