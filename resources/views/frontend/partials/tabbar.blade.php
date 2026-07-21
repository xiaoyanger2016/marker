<nav class="fixed bottom-0 left-0 right-0 z-50 bg-white/95 backdrop-blur-sm border-t border-gray-100 pb-safe">
    <div class="grid grid-cols-4 max-w-2xl mx-auto">
        <a href="{{ url('/') }}" class="flex flex-col items-center gap-0.5 py-2.5 {{ request()->path() === '/' ? 'text-emerald-600' : 'text-gray-500' }}">
            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6"></path></svg>
            <span class="text-[10px]">首页</span>
        </a>
        <a href="{{ url('/map') }}" class="flex flex-col items-center gap-0.5 py-2.5 {{ request()->path() === 'map' ? 'text-emerald-600' : 'text-gray-500' }}">
            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 20l-5.447-2.724A1 1 0 013 16.382V5.618a1 1 0 011.447-.894L9 7m0 13l6-3m-6 3V7m6 10l4.553 2.276A1 1 0 0021 18.382V7.618a1 1 0 00-.553-.894L15 4m0 13V4m0 0L9 7"></path></svg>
            <span class="text-[10px]">地图</span>
        </a>
        <a href="{{ url('/radar') }}" class="flex flex-col items-center gap-0.5 py-2.5 {{ request()->path() === 'radar' ? 'text-emerald-600' : 'text-gray-500' }}">
            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><circle cx="12" cy="12" r="9" stroke-width="2"/><circle cx="12" cy="12" r="5" stroke-width="2"/><circle cx="12" cy="12" r="1" fill="currentColor"/></svg>
            <span class="text-[10px]">雷达</span>
        </a>
        <a href="{{ url('/me') }}" class="flex flex-col items-center gap-0.5 py-2.5 {{ request()->path() === 'me' ? 'text-emerald-600' : 'text-gray-500' }}">
            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"></path></svg>
            <span class="text-[10px]">我的</span>
        </a>
    </div>
</nav>
