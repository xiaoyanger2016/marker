{{-- 编辑感底部导航：图标 + 编号 + 标签（无 emoji） --}}
<nav class="fixed bottom-0 left-0 right-0 z-50 bg-paper/95 backdrop-blur-sm border-t border-line">
    <div class="grid grid-cols-4 max-w-2xl mx-auto">
        <a href="{{ url('/') }}" class="flex flex-col items-center gap-1 py-2.5 {{ request()->is('/') || request()->path() === '/' ? 'text-ink' : 'text-ink-3' }} hover:text-ink transition-colors">
            <svg class="w-5 h-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5">
                <path d="M3 12l9-9 9 9"/>
                <path d="M5 10v10a1 1 0 001 1h4v-7h4v7h4a1 1 0 001-1V10"/>
            </svg>
            <span class="font-mono text-[9px] uppercase tracking-[0.15em]">{{ __('ui.tab_home') }}</span>
        </a>

        <a href="{{ url('/activities') }}" class="flex flex-col items-center gap-1 py-2.5 {{ request()->is('activities*') ? 'text-ink' : 'text-ink-3' }} hover:text-ink transition-colors">
            <svg class="w-5 h-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5">
                <rect x="3" y="5" width="18" height="16" rx="1"/>
                <path d="M3 9h18M8 3v4M16 3v4"/>
                <circle cx="8" cy="14" r="0.5" fill="currentColor"/>
                <circle cx="12" cy="14" r="0.5" fill="currentColor"/>
                <circle cx="16" cy="14" r="0.5" fill="currentColor"/>
            </svg>
            <span class="font-mono text-[9px] uppercase tracking-[0.15em]">{{ __('ui.tab_activity') }}</span>
        </a>

        <a href="{{ url('/radar') }}" class="flex flex-col items-center gap-1 py-2.5 {{ request()->is('radar') ? 'text-ink' : 'text-ink-3' }} hover:text-ink transition-colors">
            <svg class="w-5 h-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5">
                <circle cx="12" cy="12" r="9"/>
                <circle cx="12" cy="12" r="5"/>
                <circle cx="12" cy="12" r="1" fill="currentColor"/>
                <path d="M12 3v3M12 18v3M3 12h3M18 12h3"/>
            </svg>
            <span class="font-mono text-[9px] uppercase tracking-[0.15em]">{{ __('ui.tab_radar') }}</span>
        </a>

        <a href="{{ url('/me') }}" class="flex flex-col items-center gap-1 py-2.5 {{ request()->is('me*') ? 'text-ink' : 'text-ink-3' }} hover:text-ink transition-colors">
            <svg class="w-5 h-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5">
                <circle cx="12" cy="8" r="4"/>
                <path d="M4 21a8 8 0 0116 0"/>
            </svg>
            <span class="font-mono text-[9px] uppercase tracking-[0.15em]">{{ __('ui.tab_me') }}</span>
        </a>
    </div>
</nav>
