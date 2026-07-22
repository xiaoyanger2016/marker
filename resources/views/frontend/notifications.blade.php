@extends('frontend.layout')

@section('title', '通知 · Marker')

@section('content')

<section class="border-b border-line-2">
    <div class="max-w-3xl mx-auto px-5 sm:px-8 pt-6 pb-2">
        <div class="flex items-center gap-3 font-mono text-[10px] uppercase tracking-[0.2em] text-ink-3">
            <a href="/me" class="hover:text-ink">← PROFILE</a>
            <span class="w-px h-3 bg-line-2"></span>
            <span>N°07 · NOTIFICATIONS</span>
        </div>
    </div>
</section>

<section class="border-b border-line">
    <div class="max-w-3xl mx-auto px-5 sm:px-8 py-6 sm:py-10">
        <div class="flex items-baseline justify-between">
            <h1 class="font-display font-medium text-3xl sm:text-5xl text-ink leading-none">通知</h1>
            @if($unreadCount > 0)
                <form method="POST" action="{{ url('/notifications/mark-all-read') }}">
                    @csrf
                    <button type="submit" class="font-mono text-[10px] uppercase tracking-[0.15em] border border-ink px-3 py-1.5 hover:bg-ink hover:text-paper transition-colors">
                        全部标记已读
                    </button>
                </form>
            @endif
        </div>
        <p class="font-display italic text-base sm:text-lg text-ink-2 mt-3">{{ $unreadCount }} 条未读 · 共 {{ $notifs->total() }} 条</p>
    </div>
</section>

<div class="max-w-3xl mx-auto px-5 sm:px-8 py-8">
    @forelse($notifs as $n)
        @php $d = $n->data; $isUnread = $n->read_at === null; @endphp
        <a href="{{ url('/notifications/' . $n->id . '/read') }}" class="block border-b border-line py-4 px-2 hover:bg-paper-2 transition-colors group {{ $isUnread ? 'border-l-2 border-l-warm' : '' }}">
            <div class="flex items-start gap-3">
                {{-- icon --}}
                <div class="w-8 h-8 flex-shrink-0 bg-ink text-paper flex items-center justify-center font-mono text-[10px]">
                    @php
                        $icon = match($d['type'] ?? '') {
                            'comment' => '💬',
                            'vote' => '★',
                            'activity_joined' => '→',
                            'followed' => '+',
                            default => '·',
                        };
                    @endphp
                    {{ $icon }}
                </div>
                <div class="flex-1 min-w-0">
                    <div class="flex items-baseline justify-between gap-2">
                        <h3 class="font-display text-base text-ink group-hover:text-warm transition-colors">{{ $d['title'] ?? '通知' }}</h3>
                        <span class="font-mono text-[10px] text-ink-3 flex-shrink-0">{{ $n->created_at->diffForHumans() }}</span>
                    </div>
                    @if(! empty($d['message']))
                        <p class="text-sm text-ink-2 mt-1 line-clamp-2">{{ $d['message'] }}</p>
                    @endif
                    @if($isUnread)
                        <span class="inline-block mt-1.5 w-2 h-2 bg-warm"></span>
                    @endif
                </div>
            </div>
        </a>
    @empty
        <div class="py-20 text-center">
            <div class="font-display text-3xl text-ink-2 mb-2">还没有通知</div>
            <p class="text-sm text-ink-3">有人评论/投票/报名你的内容时，会出现在这里</p>
        </div>
    @endforelse

    @if($notifs->hasPages())
        <div class="pt-6">{{ $notifs->links() }}</div>
    @endif
</div>

@endsection
