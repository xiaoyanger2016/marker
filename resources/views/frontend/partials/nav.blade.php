<nav class="sticky top-0 z-40 bg-white/95 backdrop-blur-sm border-b border-gray-100 px-4 py-3 flex items-center gap-3 {{ ($isPC ?? false) ? 'max-w-7xl mx-auto' : '' }}">
    <a href="{{ url('/') }}" class="flex items-center gap-2 text-lg font-bold text-emerald-600 hover:opacity-80 active:scale-95 transition cursor-pointer select-none">
        <span class="text-2xl">📍</span>
        <span>{{ __('ui.app_name') }}</span>
    </a>
    <div class="flex-1"></div>

    {{-- 语种切换 --}}
    <div class="relative" x-data="{ open: false }">
        <button onclick="this.nextElementSibling.classList.toggle('hidden')"
                class="text-xs px-2 py-1 rounded border border-gray-200 hover:border-emerald-500 text-gray-600 hover:text-emerald-600">
            🌐 {{ strtoupper(str_replace('-', '_', $locale ?? 'zh-CN')) }}
        </button>
        <div class="hidden absolute right-0 mt-1 bg-white border border-gray-200 rounded-lg shadow-lg text-sm z-50 min-w-[120px]">
            @foreach(['zh-CN' => '简体中文', 'zh-TW' => '繁體中文', 'en' => 'English', 'ja' => '日本語', 'ko' => '한국어'] as $code => $label)
                <a href="?lang={{ $code }}" class="block px-3 py-2 hover:bg-gray-50 {{ ($locale ?? '') === $code ? 'text-emerald-600 font-medium' : 'text-gray-700' }}">
                    {{ $label }}
                </a>
            @endforeach
        </div>
    </div>

    @auth
        <a href="{{ url('/me') }}" class="text-sm text-gray-600 hover:text-emerald-600 cursor-pointer">
            <span class="sm:hidden">👤</span>
            <span class="hidden sm:inline">{{ auth()->user()->name }}</span>
        </a>
    @else
        <a href="{{ url('/login') }}" class="text-sm text-gray-600 hover:text-emerald-600 cursor-pointer">{{ __('ui.login') }}</a>
    @endauth
</nav>
