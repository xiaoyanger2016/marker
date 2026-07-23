<x-filament-panels::page>
    @php
        $amapKey = config('services.amap.key');
        $amapKeyMissing = empty($amapKey) || $amapKey === 'your_amap_web_key_here';
    @endphp

    {{-- Phase 22.2: 整个 page 限宽 900px + 居中，避免侧栏折叠后内容占满 viewport 显得稀疏 --}}
    <div class="max-w-[900px] mx-auto">

        {{-- 未配置高德 API Key 时给个友好提示 + 申请指引 --}}
        @if($amapKeyMissing)
            <div class="mb-5 border border-warm bg-warm/5 p-4">
                <div class="flex items-baseline gap-2 mb-2 font-mono text-[10px] uppercase tracking-[0.2em] text-warm">
                    <span>⚠</span>
                    <span>API KEY NOT CONFIGURED</span>
                </div>
                <h3 class="font-display text-base text-ink mb-2">先申请高德 Web 服务 Key（个人可用，5 分钟）</h3>
                <ol class="text-sm text-ink-2 leading-relaxed space-y-1 list-decimal pl-5">
                    <li>打开 <a href="https://lbs.amap.com/" target="_blank" class="underline underline-offset-2 hover:text-warm">lbs.amap.com</a> → 右上角「注册」→ 用支付宝扫码实名认证（个人账户即可，配额 2000 次/日）</li>
                    <li>控制台 → 应用管理 → 我的应用 → 「创建新应用」→ 类型选「其它」</li>
                    <li>点进新应用 → 「添加 Key」→ 服务平台选「Web 服务」→ 白名单填 <code class="font-mono text-xs bg-paper-2 px-1.5 py-0.5">localhost, 127.0.0.1</code> + 你的域名</li>
                    <li>复制生成的 Key → 写到项目 <code class="font-mono text-xs bg-paper-2 px-1.5 py-0.5">.env</code> 的 <code class="font-mono text-xs bg-paper-2 px-1.5 py-0.5">AMAP_WEB_KEY=...</code> → <code class="font-mono text-xs bg-paper-2 px-1.5 py-0.5">php artisan config:clear</code> → 刷新本页</li>
                </ol>
            </div>
        @endif

        {{-- 头版：编辑感标题 + 步骤编号 --}}
        <div class="mb-5 pb-4 border-b border-ink/15">
            <div class="flex items-baseline gap-3 mb-1.5 font-mono text-[10px] uppercase tracking-[0.2em] text-ink-3">
                <span>§ 01</span>
                <span class="w-px h-3 bg-ink/15"></span>
                <span>从高德地图导入 POI</span>
                <span class="w-px h-3 bg-ink/15"></span>
                <span>2026 · 高德公开 API</span>
            </div>
            <h1 class="font-display text-2xl sm:text-3xl text-ink leading-tight">
                把高德收藏夹的地点，<br>
                <span class="serif-italic text-warm">搬进你的 Marker</span>
            </h1>
            <p class="mt-2 text-sm text-ink-2 leading-relaxed">
                两种方式：粘链接自动识别 ugcId，或直接搜地点。勾选后批量导入位置库。
            </p>
        </div>

        {{-- Phase 20: 状态 banner (替代堆叠的 Notification) --}}
        @if($bannerType)
            @php
                $bannerStyles = [
                    'success' => 'border-grass/40 bg-grass/5 text-ink',
                    'warning' => 'border-warm/50 bg-warm/5 text-ink',
                    'danger'  => 'border-blood/50 bg-blood/5 text-ink',
                    'info'    => 'border-accent/40 bg-accent/5 text-ink',
                ];
                $bannerIcons = [
                    'success' => 'OK',
                    'warning' => '!',
                    'danger'  => 'ERR',
                    'info'    => 'i',
                ];
                $cls = $bannerStyles[$bannerType] ?? $bannerStyles['info'];
                $ico = $bannerIcons[$bannerType] ?? 'ⓘ';
            @endphp
            <div class="mb-5 border {{ $cls }} px-4 py-3 relative">
                <div class="flex items-start gap-3 pr-8">
                    <div class="font-mono text-base mt-0.5 leading-none">{{ $ico }}</div>
                    <div class="flex-1 min-w-0">
                        <div class="font-display text-sm mb-0.5">{{ $bannerTitle }}</div>
                        @if($bannerBody)
                            <div class="text-xs text-ink-2 whitespace-pre-line leading-relaxed">{{ $bannerBody }}</div>
                        @endif
                        @if($bannerExtra)
                            <div class="mt-1.5 text-[11px] text-ink-3 font-mono break-all">{{ $bannerExtra }}</div>
                        @endif
                    </div>
                </div>
                <button
                    type="button"
                    wire:click="dismissBanner"
                    class="absolute top-2.5 right-2.5 w-5 h-5 flex items-center justify-center text-ink-3 hover:text-ink hover:bg-ink/5 transition-colors font-mono text-xs"
                    aria-label="关闭"
                >✕</button>
            </div>
        @endif

        {{-- 方式 A：分享链接 --}}
        <div class="mb-4 border border-ink/20 bg-paper">
            <div class="flex items-center justify-between px-4 py-2.5 border-b border-ink/15 bg-paper-2">
                <div class="flex items-baseline gap-2.5">
                    <span class="font-mono text-[10px] uppercase tracking-[0.2em] text-ink-3">A</span>
                    <span class="font-display text-sm text-ink">分享链接解析</span>
                </div>
                <span class="font-mono text-[9px] uppercase tracking-[0.15em] text-ink-3">步骤 1/3 · 识别 ugcId</span>
            </div>
            <div class="p-4">
                <p class="text-xs text-ink-2 leading-relaxed mb-3">
                    粘高德 APP 复制的一条收藏夹分享链接 → 解析。<span class="text-ink-3">（高德 web 端要登录态拿不到列表 API，下面需要手动输 1-2 个典型地点名 + 城市）</span>
                </p>
                <div class="flex gap-2 max-w-[640px]">
                    <input
                        type="text"
                        wire:model="shareUrl"
                        placeholder="https://guinness.autonavi.com/activity/... 或 amapuri://..."
                        class="flex-1 bg-transparent border-0 border-b border-ink/30 focus:border-ink focus:ring-0 outline-none px-0 py-2 text-sm text-ink placeholder:text-ink-3 font-mono"
                    >
                    <button
                        wire:click="parseShareUrl"
                        wire:loading.attr="disabled"
                        class="font-mono text-[10px] uppercase tracking-[0.18em] px-4 py-2 bg-ink text-paper border border-ink hover:bg-ink-2 transition-colors disabled:opacity-50 shrink-0"
                    >
                        <span wire:loading.remove wire:target="parseShareUrl">解析</span>
                        <span wire:loading wire:target="parseShareUrl">解析中…</span>
                    </button>
                </div>
                @if($lastUgcId)
                    <div class="mt-2.5 text-xs text-ink-3 font-mono">
                        上次识别的 ugcId：<span class="text-ink-2">{{ $lastUgcId }}</span>
                    </div>
                @endif
            </div>
        </div>

        {{-- 方式 B：关键字搜索 --}}
        <div class="mb-4 border border-ink/20 bg-paper">
            <div class="flex items-center justify-between px-4 py-2.5 border-b border-ink/15 bg-paper-2">
                <div class="flex items-baseline gap-2.5">
                    <span class="font-mono text-[10px] uppercase tracking-[0.2em] text-ink-3">B</span>
                    <span class="font-display text-sm text-ink">关键字搜索</span>
                </div>
                <span class="font-mono text-[9px] uppercase tracking-[0.15em] text-ink-3">步骤 2/3</span>
            </div>
            <div class="p-4">
                <p class="text-xs text-ink-2 leading-relaxed mb-3">
                    @if($lastUgcId)
                        ugcId 是数字，搜不到。<strong class="text-ink">输收藏夹里 1-2 个最典型地点名 + 城市</strong>（如：<span class="font-mono text-warm">莫干山 西施岩村</span>），再搜。
                    @else
                        输入地点名（1-2 个最典型）+ 城市。匹配出高德 POI 列表。
                    @endif
                </p>
                <div class="grid grid-cols-1 md:grid-cols-[1fr_180px] gap-3 mb-3 max-w-[640px]">
                    <input
                        type="text"
                        wire:model="keywords"
                        placeholder="{{ $lastUgcId ? '如：莫干山、西施岩村、千岛湖' : '如：莫干山、兰州拉面、千岛湖' }}"
                        x-ref="kwInput"
                        data-amap-keywords-input
                        x-on:amap-parsed.window="
                            const el = $el;
                            if (el) {
                                el.scrollIntoView({ behavior: 'smooth', block: 'center' });
                                setTimeout(() => el.focus(), 350);
                            }
                        "
                        class="bg-transparent border-0 border-b border-ink/30 focus:border-ink focus:ring-0 outline-none px-0 py-2 text-sm text-ink placeholder:text-ink-3"
                    >
                    <input
                        type="text"
                        wire:model="city"
                        placeholder="城市（可选）"
                        class="bg-transparent border-0 border-b border-ink/30 focus:border-ink focus:ring-0 outline-none px-0 py-2 text-sm text-ink placeholder:text-ink-3"
                    >
                </div>
                <div class="flex items-center gap-4">
                    <button
                        wire:click="search"
                        wire:loading.attr="disabled"
                        class="font-mono text-[10px] uppercase tracking-[0.18em] px-4 py-2 bg-ink text-paper border border-ink hover:bg-ink-2 transition-colors disabled:opacity-50"
                    >
                        <span wire:loading.remove wire:target="search">搜索 POI</span>
                        <span wire:loading wire:target="search">搜索中…</span>
                    </button>

                    @if($searched && count($results) > 0)
                        <span class="font-mono text-[10px] uppercase tracking-[0.15em] text-ink-3">
                            共 {{ count($results) }} 个结果 · 勾选 → 选类型 → 批量导入
                        </span>
                    @endif
                </div>
            </div>
        </div>

        {{-- 搜索结果：批量操作 + 列表 --}}
        @if($searched && count($results) > 0)
            <div class="mb-2 flex items-baseline gap-2">
                <span class="font-mono text-[10px] uppercase tracking-[0.2em] text-ink-3">§ 03</span>
                <span class="w-px h-3 bg-ink/15"></span>
                <span class="font-mono text-[10px] uppercase tracking-[0.2em] text-ink-3">步骤 3/3 · 批量导入</span>
            </div>
            <div class="mb-4 flex items-center gap-3 px-4 py-2.5 border border-ink/20 bg-paper-2 flex-wrap">
                <span class="font-mono text-[10px] uppercase tracking-[0.18em] text-ink-3">批量归类</span>
                <select
                    wire:model.live="placeType"
                    class="bg-transparent border-0 border-b border-ink/30 focus:border-ink focus:ring-0 outline-none px-0 py-1 text-sm text-ink"
                >
                    <option value="">— 导入后类型待定 —</option>
                    @foreach($placeTypes as $key => $meta)
                        <option value="{{ $key }}">{{ $meta['icon'] ?? '·' }} {{ $meta['label'] }}</option>
                    @endforeach
                </select>
                <span class="font-mono text-[10px] text-ink-3">已选 {{ count($selected) }} / {{ count($results) }}</span>
                <button
                    wire:click="toggleAll(true)"
                    class="text-xs font-mono text-ink-3 hover:text-ink underline-offset-2 hover:underline"
                >全选</button>
                <button
                    wire:click="toggleAll(false)"
                    class="text-xs font-mono text-ink-3 hover:text-ink underline-offset-2 hover:underline"
                >清空</button>
                <button
                    wire:click="importSelected"
                    wire:loading.attr="disabled"
                    class="ml-auto font-mono text-[10px] uppercase tracking-[0.18em] px-4 py-2 bg-warm text-paper border border-warm hover:bg-warm/90 transition-colors disabled:opacity-50"
                >
                    导入所选（{{ count($selected) }}）
                </button>
            </div>

            <div class="border border-ink/20 divide-y divide-ink/10 max-h-[560px] overflow-y-auto">
                @foreach($results as $idx => $poi)
                    <label class="flex items-start gap-4 px-4 py-2.5 {{ in_array($idx, $selected) ? 'bg-ink text-paper' : 'hover:bg-paper-2' }} cursor-pointer transition-colors">
                        <input
                            type="checkbox"
                            wire:model.live="selected"
                            value="{{ $idx }}"
                            class="mt-1 w-4 h-4 accent-current {{ in_array($idx, $selected) ? '' : 'opacity-60' }}"
                        >
                        <div class="flex-1 min-w-0">
                            <div class="font-display text-base {{ in_array($idx, $selected) ? 'text-paper' : 'text-ink' }}">{{ $poi['name'] }}</div>
                            <div class="font-mono text-[10px] mt-1 {{ in_array($idx, $selected) ? 'text-paper/70' : 'text-ink-3' }} tracking-wide">
                                {{ trim(($poi['pname'] ?? '') . ' ' . ($poi['cityname'] ?? '') . ' ' . ($poi['adname'] ?? '') . ' ' . ($poi['address'] ?? '')) }}
                            </div>
                            <div class="font-mono text-[9px] mt-1 {{ in_array($idx, $selected) ? 'text-paper/50' : 'text-ink-3' }} uppercase tracking-wider">
                                {{ $poi['type'] ?: '未分类' }}
                                @if($poi['tel']) · {{ $poi['tel'] }} @endif
                                · {{ $poi['id'] }}
                            </div>
                        </div>
                        <div class="font-mono text-[10px] whitespace-nowrap {{ in_array($idx, $selected) ? 'text-paper/70' : 'text-ink-3' }}">
                            {{ round($poi['longitude'], 5) }}<br>{{ round($poi['latitude'], 5) }}
                        </div>
                    </label>
                @endforeach
            </div>
        @elseif($searched)
            <div class="border border-ink/20 bg-paper p-10 text-center">
                <div class="font-display text-xl text-ink-2 mb-1">没有结果</div>
                <p class="text-sm text-ink-3">换个关键字或城市试试</p>
            </div>
        @endif
    </div>
</x-filament-panels::page>
