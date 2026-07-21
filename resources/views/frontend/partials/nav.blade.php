<nav class="sticky top-0 z-40 bg-white/95 backdrop-blur-sm border-b border-gray-100 px-4 py-3 flex items-center gap-3">
    <a href="{{ url('/') }}" class="flex items-center gap-2 text-lg font-bold text-emerald-600">
        <span class="text-2xl">📍</span>
        <span>Marker</span>
    </a>
    <div class="flex-1"></div>
    @auth
        <a href="{{ url('/admin') }}" class="text-sm text-gray-600 hover:text-emerald-600">管理</a>
        <span class="text-sm text-gray-400 hidden sm:inline">|</span>
        <a href="{{ url('/profile') }}" class="text-sm text-gray-600 hover:text-emerald-600">{{ auth()->user()->name }}</a>
    @else
        <a href="{{ url('/login') }}" class="text-sm text-gray-600 hover:text-emerald-600">登录</a>
    @endauth
</nav>
