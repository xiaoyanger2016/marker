{{-- Admin 主题切换器 — 复用前端 theme 系统，但只暴露 light/dark 两档 --}}
@once
@push('styles')
<style>
    .marker-admin-theme {
        position: relative;
    }
    .marker-admin-theme-btn {
        font-family: var(--font-family-mono, 'JetBrains Mono', monospace);
        font-size: 10px;
        text-transform: uppercase;
        letter-spacing: 0.18em;
        padding: 6px 12px;
        border: 1px solid rgba(0,0,0,0.12);
        background: transparent;
        cursor: pointer;
        color: inherit;
        display: inline-flex;
        align-items: center;
        gap: 8px;
        transition: all 0.2s;
    }
    .dark .marker-admin-theme-btn {
        border-color: rgba(255,255,255,0.18);
    }
    .marker-admin-theme-btn:hover {
        border-color: currentColor;
    }
    .marker-admin-theme-panel {
        position: absolute;
        right: 0;
        top: calc(100% + 8px);
        min-width: 200px;
        background: var(--paper, #F2EDE2);
        border: 1px solid rgba(0,0,0,0.12);
        z-index: 9999;
        box-shadow: 0 8px 24px -8px rgba(0,0,0,0.18);
    }
    .dark .marker-admin-theme-panel {
        background: var(--paper, #0E0D0B);
        border-color: rgba(255,255,255,0.18);
        box-shadow: 0 8px 24px -8px rgba(0,0,0,0.6);
    }
    .marker-admin-theme-opt {
        display: block;
        width: 100%;
        text-align: left;
        padding: 10px 14px;
        font-family: inherit;
        font-size: 11px;
        background: transparent;
        border: 0;
        cursor: pointer;
        color: inherit;
        text-transform: uppercase;
        letter-spacing: 0.1em;
    }
    .marker-admin-theme-opt:hover {
        background: rgba(0,0,0,0.04);
    }
    .dark .marker-admin-theme-opt:hover {
        background: rgba(255,255,255,0.05);
    }
    .marker-admin-theme-opt.active {
        background: rgba(0,0,0,0.88);
        color: var(--paper, #F2EDE2);
    }
    .dark .marker-admin-theme-opt.active {
        background: rgba(255,255,255,0.95);
        color: var(--ink, #1A1814);
    }
    .marker-admin-theme-opt-label {
        display: block;
        font-family: var(--font-family-serif, serif);
        font-size: 13px;
        text-transform: none;
        letter-spacing: 0;
    }
    .marker-admin-theme-opt-desc {
        display: block;
        font-family: var(--font-family-mono, monospace);
        font-size: 9px;
        opacity: 0.7;
        margin-top: 2px;
    }
</style>
@endpush
@endonce

<div class="marker-admin-theme" x-data="{ open: false }">
    <button type="button" class="marker-admin-theme-btn" @click="open = !open" data-toggle="admin-theme">
        <span>主题</span>
        <span x-text="(localStorage.getItem('marker.theme') || '{{ $theme ?? 'paper' }}').toUpperCase()"></span>
    </button>
    <div class="marker-admin-theme-panel" x-show="open" @click.outside="open = false" x-cloak style="display:none">
        @php
            $opts = [
                'paper'  => ['label' => '纸刊',  'en' => 'Paper', 'desc' => '暖米白 · 杂志感'],
                'sand'   => ['label' => '沙黄',  'en' => 'Sand',  'desc' => '傍晚沙漠 · 暖沉'],
                'ink'    => ['label' => '夜读',  'en' => 'Ink',   'desc' => '深夜墨 · 沉浸'],
                'mono'   => ['label' => '高对比','en' => 'Mono',  'desc' => '纯黑白 · 极简'],
                'auto'   => ['label' => '跟随系统','en' => 'Auto','desc' => '跟随系统 · 自动切'],
            ];
        @endphp
        @foreach($opts as $code => $t)
            <button type="button" data-admin-theme-set="{{ $code }}"
                    class="marker-admin-theme-opt {{ ($theme ?? 'paper') === $code ? 'active' : '' }}">
                <span class="marker-admin-theme-opt-label">{{ $t['label'] }} · {{ $t['en'] }}</span>
                <span class="marker-admin-theme-opt-desc">{{ $t['desc'] }}</span>
            </button>
        @endforeach
    </div>
</div>

@once
@push('scripts')
<script>
(function() {
    const STORAGE_KEY = 'marker.theme';
    const VALID = ['paper', 'sand', 'ink', 'mono', 'auto'];

    function applyTheme(name) {
        if (!VALID.includes(name)) name = 'paper';
        document.documentElement.setAttribute('data-theme', name);
        // Filament 用 .dark class
        const isDark = name === 'ink' || (name === 'auto' && window.matchMedia && window.matchMedia('(prefers-color-scheme: dark)').matches);
        if (isDark) {
            document.documentElement.classList.add('dark');
        } else {
            document.documentElement.classList.remove('dark');
        }
        // 同步 server
        const csrf = document.querySelector('meta[name="csrf-token"]')?.content || '';
        fetch('/theme', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': csrf, 'Accept': 'application/json' },
            body: JSON.stringify({ theme: name })
        }).catch(() => {});

        // 更新 active 状态
        document.querySelectorAll('[data-admin-theme-set]').forEach(btn => {
            btn.classList.toggle('active', btn.dataset.adminThemeSet === name);
        });
    }

    document.addEventListener('click', (e) => {
        const t = e.target.closest('[data-admin-theme-set]');
        if (t) {
            e.stopPropagation();
            applyTheme(t.dataset.adminThemeSet);
        }
    });

    // 启动时应用 localStorage 主题
    try {
        const saved = localStorage.getItem(STORAGE_KEY);
        if (saved && VALID.includes(saved)) applyTheme(saved);
    } catch (e) {}

    // auto 模式跟随系统
    if (window.matchMedia) {
        const mq = window.matchMedia('(prefers-color-scheme: dark)');
        mq.addEventListener('change', () => {
            try {
                const cur = localStorage.getItem(STORAGE_KEY);
                if (cur === 'auto') applyTheme('auto');
            } catch (e) {}
        });
    }
})();
</script>
@endpush
@endonce
