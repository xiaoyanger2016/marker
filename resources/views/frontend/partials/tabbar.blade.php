{{-- 浮动 dock 底栏：mobile 全宽铺满，PC 居中 + 缩窄
   - mobile: 几乎全宽，只留少量左右 padding（让圆角可见）
   - PC (sm+): 居中 max-w-sm pill
   - pointer-events: none 包外层，不挡内容点击
   - 顶部拖拽条 (drag handle) 暗示悬浮感
   - safe-bottom：iPhone home indicator 不压字
--}}
<div class="fixed bottom-0 left-0 right-0 z-50 pointer-events-none safe-bottom">
    <div class="px-3 sm:px-8 pb-3 sm:pb-5 pointer-events-auto">
        <nav class="relative mx-auto max-w-full sm:max-w-sm bg-paper/90 backdrop-blur-2xl border border-ink/15 shadow-dock sm:rounded-2xl rounded-xl">
            {{-- 顶部拖拽条（视觉提示：这是浮起来的 sheet） --}}
            <div class="flex justify-center pt-1.5 pb-0.5 sm:hidden">
                <span class="block w-8 h-[3px] rounded-full bg-ink/15"></span>
            </div>
            <div class="grid grid-cols-4 px-1 sm:px-2 py-1.5 sm:py-2">
                <a href="{{ url('/') }}" class="flex flex-col items-center gap-1 py-1.5 sm:py-2 rounded-xl {{ request()->is('/') || request()->path() === '/' ? 'text-ink bg-paper-2' : 'text-ink-3' }} hover:text-ink hover:bg-paper-2 transition-colors">
                    <svg class="w-[18px] h-[18px]" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5">
                        <path d="M3 12l9-9 9 9"/>
                        <path d="M5 10v10a1 1 0 001 1h4v-7h4v7h4a1 1 0 001-1V10"/>
                    </svg>
                    <span class="font-mono text-[9px] uppercase tracking-[0.15em]">{{ __('ui.tab_home') }}</span>
                </a>

                <a href="{{ url('/activities') }}" class="flex flex-col items-center gap-1 py-1.5 sm:py-2 rounded-xl {{ request()->is('activities*') ? 'text-ink bg-paper-2' : 'text-ink-3' }} hover:text-ink hover:bg-paper-2 transition-colors">
                    <svg class="w-[18px] h-[18px]" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5">
                        <rect x="3" y="5" width="18" height="16" rx="1"/>
                        <path d="M3 9h18M8 3v4M16 3v4"/>
                        <circle cx="8" cy="14" r="0.5" fill="currentColor"/>
                        <circle cx="12" cy="14" r="0.5" fill="currentColor"/>
                        <circle cx="16" cy="14" r="0.5" fill="currentColor"/>
                    </svg>
                    <span class="font-mono text-[9px] uppercase tracking-[0.15em]">{{ __('ui.tab_activity') }}</span>
                </a>

                <a href="{{ url('/radar') }}" class="flex flex-col items-center gap-1 py-1.5 sm:py-2 rounded-xl {{ request()->is('radar') ? 'text-ink bg-paper-2' : 'text-ink-3' }} hover:text-ink hover:bg-paper-2 transition-colors">
                    <svg class="w-[18px] h-[18px]" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5">
                        <circle cx="12" cy="12" r="9"/>
                        <circle cx="12" cy="12" r="5"/>
                        <circle cx="12" cy="12" r="1" fill="currentColor"/>
                        <path d="M12 3v3M12 18v3M3 12h3M18 12h3"/>
                    </svg>
                    <span class="font-mono text-[9px] uppercase tracking-[0.15em]">{{ __('ui.tab_radar') }}</span>
                </a>

                <a href="{{ url('/me') }}" class="flex flex-col items-center gap-1 py-1.5 sm:py-2 rounded-xl {{ request()->is('me*') ? 'text-ink bg-paper-2' : 'text-ink-3' }} hover:text-ink hover:bg-paper-2 transition-colors">
                    <svg class="w-[18px] h-[18px]" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5">
                        <circle cx="12" cy="8" r="4"/>
                        <path d="M4 21a8 8 0 0116 0"/>
                    </svg>
                    <span class="font-mono text-[9px] uppercase tracking-[0.15em]">{{ __('ui.tab_me') }}</span>
                </a>
            </div>
        </nav>
    </div>
</div>
