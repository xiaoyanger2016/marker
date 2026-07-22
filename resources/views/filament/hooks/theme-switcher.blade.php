{{-- Admin 主题切换器 — Linear 商务风 (5 主题统一同步) --}}
@once
@push('styles')
<style>
    .marker-admin-theme {
        position: relative;
    }
    /* Linear: 紧凑 mono 按钮 + hairline border */
    .marker-admin-theme-btn {
        font-family: var(--font-family-mono, 'JetBrains Mono', monospace);
        font-size: 11px;
        text-transform: none;
        letter-spacing: 0;
        padding: 5px 10px;
        border: 1px solid var(--line, #E4E4E7);
        background: transparent;
        cursor: pointer;
        color: var(--ink, #0A0A0A);
        display: inline-flex;
        align-items: center;
        gap: 6px;
        transition: border-color 0.1s, color 0.1s;
        border-radius: 0;
        line-height: 1;
    }
    .marker-admin-theme-btn:hover {
        border-color: var(--ink, #0A0A0A);
    }
    /* Linear: panel 固定到 viewport 右上角 (不依赖父容器宽度) */
    .marker-admin-theme-panel {
        position: fixed;
        right: 12px;
        top: 60px;
        width: 240px;
        max-width: calc(100vw - 24px);
        background: var(--paper, #FFFFFF);
        border: 1px solid var(--line, #E4E4E7);
        z-index: 9999;
        box-shadow: 0 10px 30px -10px rgba(0,0,0,0.2);
        border-radius: 0;
    }
    .marker-admin-theme-opt {
        display: block;
        width: 100%;
        text-align: left;
        padding: 8px 12px;
        font-family: var(--font-family, 'Inter', sans-serif);
        font-size: 12px;
        background: transparent;
        border: 0;
        cursor: pointer;
        color: var(--ink, #0A0A0A);
        text-transform: none;
        letter-spacing: 0;
        line-height: 1.3;
        transition: background 0.1s;
    }
    .marker-admin-theme-opt:hover {
        background: var(--paper-2, #FAFAFA);
    }
    .marker-admin-theme-opt.active {
        background: var(--ink, #0A0A0A);
        color: var(--paper, #FFFFFF);
    }
    .marker-admin-theme-opt-label {
        display: block;
        font-family: var(--font-family, 'Inter', sans-serif);
        font-size: 12px;
        font-weight: 500;
        text-transform: none;
        letter-spacing: 0;
    }
    .marker-admin-theme-opt-desc {
        display: block;
        font-family: var(--font-family-mono, 'JetBrains Mono', monospace);
        font-size: 10px;
        opacity: 0.7;
        margin-top: 2px;
    }
    .marker-admin-theme-opt.active .marker-admin-theme-opt-desc {
        opacity: 0.7;
    }
</style>
@endpush
@endonce

<div class="marker-admin-theme" x-data="{ open: false }">
    <button type="button" class="marker-admin-theme-btn" @click="open = !open" data-toggle="admin-theme">
        <span class="text-ink-3">主题</span>
        <span class="font-medium" x-text="(localStorage.getItem('marker.theme') || document.documentElement.getAttribute('data-theme') || 'paper').toUpperCase()"></span>
    </button>
    <div class="marker-admin-theme-panel" x-show="open" @click.outside="open = false" x-cloak style="display:none">
        @php
            $opts = [
                'paper'  => ['label' => 'Paper',   'en' => '纸刊',   'desc' => 'Light · 默认'],
                'sand'   => ['label' => 'Sand',    'en' => '沙黄',   'desc' => 'Light · 暖沉'],
                'ink'    => ['label' => 'Ink',     'en' => '夜读',   'desc' => 'Dark · 沉浸'],
                'mono'   => ['label' => 'Mono',    'en' => '高对比', 'desc' => 'Light · 极简'],
                'auto'   => ['label' => 'Auto',    'en' => '跟随系统', 'desc' => '跟随系统'],
            ];
            $cur = $theme ?? 'paper';
        @endphp
        @foreach($opts as $code => $t)
            <button type="button" data-admin-theme-set="{{ $code }}"
                    class="marker-admin-theme-opt {{ $cur === $code ? 'active' : '' }}">
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

    // 启动时应用 localStorage 主题 (page reload 时从 localStorage 重新设置)
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
