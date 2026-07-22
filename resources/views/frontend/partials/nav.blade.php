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

        {{-- 主题切换（编辑感：色卡 + 文字 + 点选面板）
             注意：不用 bg-ink text-paper 反色（在 ink 主题下两者都是亮色，看不见）
             改用：左 3px 黑边 + 加粗 + bg-paper-2 高亮
        --}}
        {{-- 主题切换：用 fixed 定位，避开父容器宽度问题（父容器只 51px 宽） --}}
        <div class="relative">
            <button type="button" data-toggle="theme-panel"
                    class="font-mono text-[10px] uppercase tracking-[0.15em] text-ink-2 hover:text-ink transition-colors px-1.5 py-1 border border-transparent hover:border-line-2"
                    title="切换主题">
                <span id="theme-label">{{ strtoupper(($theme ?? 'paper')) }}</span>
            </button>
            <div id="theme-panel" data-panel
                 class="hidden fixed right-3 top-[60px] sm:right-8 sm:top-[60px] bg-paper-2 backdrop-blur-md border border-ink/20 w-[280px] z-[60] shadow-dock"
                 style="max-width: calc(100vw - 24px);">
                <div class="px-3 py-2 border-b border-line flex items-baseline justify-between">
                    <span class="eyebrow">THEME</span>
                    <span class="font-mono text-[9px] text-ink-3">5 / 5</span>
                </div>
                @php
                    $themes = [
                        'paper'  => ['label' => __('ui.theme_paper'), 'en' => 'Paper', 'desc' => '暖米白 · Magazine', 'bg' => '#F2EDE2', 'fg' => '#1A1814', 'swatch' => '#F2EDE2'],
                        'sand'   => ['label' => __('ui.theme_sand'),  'en' => 'Sand',  'desc' => '傍晚沙漠 · 暖沉',   'bg' => '#E8DCC4', 'fg' => '#2A1F0F', 'swatch' => '#E8DCC4'],
                        'ink'    => ['label' => __('ui.theme_ink'),   'en' => 'Ink',   'desc' => '深夜墨 · 沉浸阅读', 'bg' => '#0E0D0B', 'fg' => '#E8E2D4', 'swatch' => '#0E0D0B'],
                        'mono'   => ['label' => __('ui.theme_mono'),  'en' => 'Mono',  'desc' => '纯黑白 · 极简',     'bg' => '#FFFFFF', 'fg' => '#000000', 'swatch' => '#FFFFFF'],
                        'auto'   => ['label' => __('ui.theme_auto'),  'en' => 'Auto',  'desc' => '跟随系统 · 自动切', 'bg' => 'linear-gradient(135deg, #F2EDE2 50%, #0E0D0B 50%)', 'fg' => '#1A1814', 'swatch' => 'linear-gradient(135deg, #F2EDE2 50%, #0E0D0B 50%)'],
                    ];
                @endphp
                @foreach($themes as $code => $t)
                    <button type="button" data-theme-set="{{ $code }}"
                            data-panel-close
                            class="theme-opt w-full text-left flex items-center gap-3 px-3 py-2.5 hover:bg-paper-2 transition-colors border-l-[3px] {{ ($theme ?? 'paper') === $code ? 'border-ink bg-paper-2' : 'border-transparent' }}">
                        <span class="block w-5 h-5 border border-ink/30 flex-shrink-0" style="background: {{ $t['swatch'] }}"></span>
                        <div class="flex-1 min-w-0">
                            <div class="flex items-baseline gap-2">
                                <span class="text-[12px] font-display not-italic tracking-tight {{ ($theme ?? 'paper') === $code ? 'font-bold text-ink' : 'font-medium text-ink-2' }}">{{ $t['label'] }}</span>
                                <span class="font-mono text-[10px] uppercase tracking-wider text-ink-3">{{ $t['en'] }}</span>
                            </div>
                            <div class="font-sans text-[10px] mt-0.5 text-ink-3 tracking-normal">{{ $t['desc'] }}</div>
                        </div>
                        {{-- SSR 渲染时也加 theme-opt-dot class，JS 才能 remove --}}
                        @if(($theme ?? 'paper') === $code)
                            <span class="font-mono text-[9px] text-ink theme-opt-dot">●</span>
                        @endif
                    </button>
                @endforeach
            </div>
        </div>

        {{-- 语种切换：同样 fixed 定位 --}}
        <div class="relative">
            <button type="button" data-toggle="lang-panel"
                    class="font-mono text-[10px] uppercase tracking-[0.15em] text-ink-2 hover:text-ink transition-colors px-1.5 py-1">
                {{ strtoupper(str_replace('-', '_', $locale ?? 'zh-CN')) }}
            </button>
            <div id="lang-panel" data-panel
                 class="hidden fixed right-3 top-[60px] sm:right-8 sm:top-[60px] bg-paper border border-ink/20 w-[180px] z-[60] shadow-dock"
                 style="max-width: calc(100vw - 24px);">
                @foreach(['zh-CN' => '简体中文', 'zh-TW' => '繁體中文', 'en' => 'English', 'ja' => '日本語', 'ko' => '한국어'] as $code => $label)
                    <a href="?lang={{ $code }}" data-panel-close
                       class="block px-3 py-2 font-mono text-[11px] uppercase tracking-wider hover:bg-paper-2 {{ ($locale ?? '') === $code ? 'bg-ink text-paper' : 'text-ink-2' }}">
                        {{ $label }}
                    </a>
                @endforeach
            </div>
        </div>

        @auth
            {{-- 通知 icon (Phase 18.4) --}}
            <a href="{{ url('/notifications') }}" class="relative font-mono text-[10px] uppercase tracking-[0.15em] text-ink-2 hover:text-ink transition-colors px-1.5 py-1 border border-transparent hover:border-line-2" title="通知">
                <svg class="w-4 h-4 inline" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5">
                    <path d="M6 8a6 6 0 0 1 12 0c0 7 3 9 3 9H3s3-2 3-9M10 21a2 2 0 0 0 4 0"/>
                </svg>
                <span id="notif-dot" class="hidden absolute -top-0.5 -right-0.5 w-2 h-2 bg-warm"></span>
            </a>
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
    const VALID = ['paper', 'sand', 'ink', 'mono', 'auto'];

    function applyTheme(name) {
        const html = document.documentElement;
        if (!VALID.includes(name)) name = 'paper';
        html.setAttribute('data-theme', name);
        // meta theme-color 跟随（适配移动浏览器顶栏）
        const m = document.querySelector('meta[name="theme-color"]:not([media])');
        if (m) {
            const map = { paper: '#F2EDE2', sand: '#E8DCC4', ink: '#0E0D0B', mono: '#FFFFFF', auto: '#F2EDE2' };
            m.setAttribute('content', map[name] || map.paper);
        }
    }

    function setTheme(name) {
        applyTheme(name);
        try { localStorage.setItem(STORAGE_KEY, name); } catch (e) {}
        // 更新下拉激活态 + 按钮 label
        const lbl = document.getElementById('theme-label');
        if (lbl) lbl.textContent = name.toUpperCase();
        // 新主题面板：active 状态用 border-l-[3px] border-ink + bg-paper-2 + 右侧 ●
        document.querySelectorAll('[data-theme-set]').forEach(btn => {
            const isActive = btn.dataset.themeSet === name;
            btn.classList.toggle('border-ink', isActive);
            btn.classList.toggle('border-transparent', !isActive);
            btn.classList.toggle('bg-paper-2', isActive);
            // 内部 label 字体加粗
            const label = btn.querySelector('.font-display');
            if (label) {
                label.classList.toggle('font-bold', isActive);
                label.classList.toggle('text-ink', isActive);
                label.classList.toggle('font-medium', !isActive);
                label.classList.toggle('text-ink-2', !isActive);
            }
            // 右侧激活圆点
            let dot = btn.querySelector('.theme-opt-dot');
            if (isActive && !dot) {
                dot = document.createElement('span');
                dot.className = 'font-mono text-[9px] text-ink theme-opt-dot';
                dot.textContent = '●';
                btn.appendChild(dot);
            } else if (!isActive && dot) {
                dot.remove();
            }
        });
        // 同步到 server session
        const csrf = document.querySelector('meta[name="csrf-token"]')?.content || '';
        fetch('/theme', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': csrf, 'Accept': 'application/json' },
            body: JSON.stringify({ theme: name })
        }).catch(() => {});
    }

    window.MarkerSetTheme = setTheme;

    // 初始化按钮 label
    const init = document.documentElement.getAttribute('data-theme') || 'paper';
    const initLbl = document.getElementById('theme-label');
    if (initLbl) initLbl.textContent = init.toUpperCase();

    // 系统主题变化时，auto 模式自动跟随
    if (window.matchMedia) {
        const mq = window.matchMedia('(prefers-color-scheme: dark)');
        const onSysChange = () => {
            const cur = document.documentElement.getAttribute('data-theme');
            if (cur === 'auto') applyTheme('auto');
        };
        if (mq.addEventListener) mq.addEventListener('change', onSysChange);
        else if (mq.addListener) mq.addListener(onSysChange);
    }

    // 通用下拉切换：data-toggle 按钮 → 兄弟面板 data-panel
    document.addEventListener('click', (e) => {
        const toggle = e.target.closest('[data-toggle]');
        if (toggle) {
            e.stopPropagation();
            const panelId = toggle.getAttribute('data-toggle');
            const panel = document.getElementById(panelId) || toggle.nextElementSibling;
            if (panel) {
                panel.classList.toggle('hidden');
            }
            return;
        }
        // 点击主题按钮（在下拉里）
        const themeBtn = e.target.closest('[data-theme-set]');
        if (themeBtn) {
            e.stopPropagation();
            setTheme(themeBtn.dataset.themeSet);
            // 关闭所有 data-panel 下拉
            document.querySelectorAll('[data-panel]:not(.hidden)').forEach(p => p.classList.add('hidden'));
            return;
        }
        // 点击外部关闭
        document.querySelectorAll('[data-panel]:not(.hidden)').forEach(panel => {
            if (!panel.contains(e.target)) {
                panel.classList.add('hidden');
            }
        });
    });
})();
</script>
@endpush
@endonce
