<nav class="fixed bottom-0 left-0 right-0 z-50 bg-white/95 backdrop-blur-sm border-t border-gray-100 pb-safe">
    <div class="grid grid-cols-4 max-w-2xl mx-auto">
        <a href="{{ url('/') }}" class="flex flex-col items-center gap-0.5 py-2.5 {{ request()->path() === '/' ? 'text-emerald-600' : 'text-gray-500' }} cursor-pointer">
            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6"></path></svg>
            <span class="text-[10px]">首页</span>
        </a>
        <a href="{{ url('/activities') }}" class="flex flex-col items-center gap-0.5 py-2.5 {{ request()->path() === 'activities' ? 'text-emerald-600' : 'text-gray-500' }} cursor-pointer">
            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"></path></svg>
            <span class="text-[10px]">活动</span>
        </a>
        <a href="{{ url('/radar') }}" class="flex flex-col items-center gap-0.5 py-2.5 {{ request()->path() === 'radar' ? 'text-emerald-600' : 'text-gray-500' }} cursor-pointer">
            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><circle cx="12" cy="12" r="9" stroke-width="2"/><circle cx="12" cy="12" r="5" stroke-width="2"/><circle cx="12" cy="12" r="1" fill="currentColor"/></svg>
            <span class="text-[10px]">雷达</span>
        </a>
        <a href="{{ url('/me') }}" class="flex flex-col items-center gap-0.5 py-2.5 {{ request()->path() === 'me' || str_starts_with(request()->path(), 'me/') ? 'text-emerald-600' : 'text-gray-500' }} cursor-pointer">
            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"></path></svg>
            <span class="text-[10px]">我的</span>
        </a>
    </div>
</nav>
