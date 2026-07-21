<nav class="sticky top-0 z-40 border-b border-line bg-paper/90 backdrop-blur-sm {{ ($isPC ?? false) ? 'max-w-7xl mx-auto' : '' }}">
    <div class="px-4 sm:px-6 h-14 flex items-center gap-4">
        <a href="{{ url('/') }}" class="flex items-center gap-2 group">
            {{-- Marker 标志：定制 SVG（无 emoji） --}}
            <svg class="w-5 h-5 text-ink" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5">
                <path d="M12 22s-7-6.5-7-12a7 7 0 1 1 14 0c0 5.5-7 12-7 12z"/>
                <circle cx="12" cy="10" r="2.5"/>
            </svg>
            <span class="font-display text-lg font-semibold tracking-tight text-ink group-hover:text-warm transition-colors">Marker</span>
        </a>

        {{-- 副标（编辑感） --}}
        <span class="hidden md:inline font-mono text-[10px] uppercase tracking-[0.2em] text-ink-3 border-l border-line pl-3 ml-1">
            公路杂志 · Est. 2026
        </span>

        <div class="flex-1"></div>

        {{-- 语种切换（编辑感下拉） --}}
        <div class="relative" x-data="{ open: false }">
            <button onclick="this.nextElementSibling.classList.toggle('hidden')"
                    class="font-mono text-[10px] uppercase tracking-[0.15em] text-ink-2 hover:text-ink transition-colors">
                {{ strtoupper(str_replace('-', '_', $locale ?? 'zh-CN')) }}
            </button>
            <div class="hidden absolute right-0 mt-2 bg-paper border border-line min-w-[140px] z-50">
                @foreach(['zh-CN' => '简体中文', 'zh-TW' => '繁體中文', 'en' => 'English', 'ja' => '日本語', 'ko' => '한국어'] as $code => $label)
                    <a href="?lang={{ $code }}" class="block px-3 py-2 font-mono text-[11px] uppercase tracking-wider {{ ($locale ?? '') === $code ? 'bg-ink text-paper' : 'text-ink-2 hover:bg-paper-2' }}">
                        {{ $label }}
                    </a>
                @endforeach
            </div>
        </div>

        @auth
            <a href="{{ url('/me') }}" class="font-sans text-sm text-ink hover:text-warm transition-colors">
                {{ auth()->user()->name }}
            </a>
        @else
            <a href="{{ url('/login') }}" class="btn btn-ghost btn-sm">{{ __('ui.login') }}</a>
        @endauth
    </div>
</nav>
