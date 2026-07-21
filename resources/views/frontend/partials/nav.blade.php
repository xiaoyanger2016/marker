<nav class="sticky top-0 z-40 border-b border-line bg-paper/90 backdrop-blur-sm {{ ($isPC ?? false) ? 'max-w-7xl mx-auto' : '' }}">
    <div class="px-4 sm:px-6 h-14 flex items-center gap-3 sm:gap-4">
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

        {{-- 主题切换（编辑感：纯文字 + 点选面板） --}}
        <div class="relative">
            <button onclick="this.nextElementSibling.classList.toggle('hidden'); event.stopPropagation();"
                    class="font-mono text-[10px] uppercase tracking-[0.15em] text-ink-2 hover:text-ink transition-colors px-1.5 py-1 border border-transparent hover:border-line-2"
                    title="切换主题">
                <span id="theme-label">{{ strtoupper(($theme ?? 'paper')) }}</span>
            </button>
            <div class="hidden absolute right-0 mt-2 bg-paper border border-line min-w-[160px] z-50 shadow-paper">
                <div class="px-3 py-2 border-b border-line">
                    <span class="eyebrow">THEME</span>
                </div>
                @php
                    $themes = [
                        'paper'  => ['label' => '纸刊',     'en' => 'Paper',  'desc' => '暖米白 · 杂志感'],
                        'ink'    => ['label' => '夜读',     'en' => 'Ink',    'desc' => '深夜墨 · 沉浸'],
                        'mono'   => ['label' => '高对比',   'en' => 'Mono',   'desc' => '纯黑白 · 极简'],
                    ];
                @endphp
                @foreach($themes as $code => $t)
                    <button type="button" data-theme-set="{{ $code }}"
                            class="w-full text-left block px-3 py-2.5 font-mono text-[11px] uppercase tracking-wider hover:bg-paper-2 {{ ($theme ?? 'paper') === $code ? 'bg-ink text-paper' : 'text-ink-2' }}">
                        <div class="flex items-baseline gap-2">
                            <span class="text-[12px] font-display not-italic tracking-tight">{{ $t['label'] }}</span>
                            <span class="opacity-70">{{ $t['en'] }}</span>
                        </div>
                        <div class="font-sans text-[10px] mt-0.5 opacity-70 normal-case tracking-normal">{{ $t['desc'] }}</div>
                    </button>
                @endforeach
            </div>
        </div>

        {{-- 语种切换 --}}
        <div class="relative">
            <button onclick="this.nextElementSibling.classList.toggle('hidden'); event.stopPropagation();"
                    class="font-mono text-[10px] uppercase tracking-[0.15em] text-ink-2 hover:text-ink transition-colors px-1.5 py-1">
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
            <a href="{{ url('/me') }}" class="font-sans text-sm text-ink hover:text-warm transition-colors hidden sm:inline">
                {{ auth()->user()->name }}
            </a>
        @else
            <a href="{{ url('/login') }}" class="btn btn-ghost btn-sm hidden sm:inline-flex">{{ __('ui.login') }}</a>
        @endauth
    </div>
</nav>

{{-- 主题切换 + 点击外部关闭下拉（一次性绑定到全局） --}}
@once
@push('scripts')
<script>
(function() {
    const STORAGE_KEY = 'marker.theme';

    function setTheme(name) {
        const html = document.documentElement;
        const valid = ['paper', 'ink', 'mono'];
        if (!valid.includes(name)) name = 'paper';
        html.setAttribute('data-theme', name);
        try { localStorage.setItem(STORAGE_KEY, name); } catch (e) {}
        // 更新下拉激活态 + 按钮 label
        const lbl = document.getElementById('theme-label');
        if (lbl) lbl.textContent = name.toUpperCase();
        document.querySelectorAll('[data-theme-set]').forEach(btn => {
            const isActive = btn.dataset.themeSet === name;
            btn.classList.toggle('bg-ink', isActive);
            btn.classList.toggle('text-paper', isActive);
            btn.classList.toggle('text-ink-2', !isActive);
        });
        // 同步到 server session（避免下次刷新掉）
        const csrf = document.querySelector('meta[name="csrf-token"]')?.content || '';
        fetch('/theme', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': csrf, 'Accept': 'application/json' },
            body: JSON.stringify({ theme: name })
        }).catch(() => {});
    }

    // 暴露到全局
    window.MarkerSetTheme = setTheme;

    // 初始化按钮 label
    const init = document.documentElement.getAttribute('data-theme') || 'paper';
    const initLbl = document.getElementById('theme-label');
    if (initLbl) initLbl.textContent = init.toUpperCase();

    // 绑定点击外部关闭（统一处理所有下拉）
    document.addEventListener('click', (e) => {
        document.querySelectorAll('.relative > .hidden').forEach(panel => {
            if (!panel.classList.contains('hidden') && !panel.contains(e.target) && !panel.previousElementSibling.contains(e.target)) {
                panel.classList.add('hidden');
            }
        });
    });

    // 绑定主题按钮
    document.querySelectorAll('[data-theme-set]').forEach(btn => {
        btn.addEventListener('click', (e) => {
            e.stopPropagation();
            setTheme(btn.dataset.themeSet);
            btn.closest('.hidden')?.classList.add('hidden');
        });
    });
})();
</script>
@endpush
@endonce
