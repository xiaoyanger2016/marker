<nav class="sticky top-0 z-40 bg-white/95 backdrop-blur-sm border-b border-gray-100 px-4 py-3 flex items-center gap-3">
    <a href="{{ url('/') }}" class="flex items-center gap-2 text-lg font-bold text-emerald-600 hover:opacity-80 active:scale-95 transition cursor-pointer select-none">
        <span class="text-2xl">📍</span>
        <span>Marker</span>
    </a>
    <div class="flex-1"></div>
    @auth
        <a href="{{ url('/me') }}" class="text-sm text-gray-600 hover:text-emerald-600 cursor-pointer">
            <span class="sm:hidden">👤</span>
            <span class="hidden sm:inline">{{ auth()->user()->name }}</span>
        </a>
    @else
        <a href="{{ url('/login') }}" class="text-sm text-gray-600 hover:text-emerald-600 cursor-pointer">登录</a>
    @endauth
</nav>
