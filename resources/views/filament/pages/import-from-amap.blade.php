<x-filament-panels::page>
    {{-- 未配置高德 API Key 时给个友好提示 + 申请指引 --}}
    @php
        $amapKey = config('services.amap.key');
        $amapKeyMissing = empty($amapKey) || $amapKey === 'your_amap_web_key_here';
    @endphp
    @if($amapKeyMissing)
        <div class="mb-6 border border-warm bg-warm/5 p-5">
            <div class="flex items-baseline gap-2 mb-2 font-mono text-[10px] uppercase tracking-[0.2em] text-warm">
                <span>⚠</span>
                <span>API KEY NOT CONFIGURED</span>
            </div>
            <h3 class="font-display text-lg text-ink mb-2">先申请高德 Web 服务 Key（个人可用，5 分钟）</h3>
            <ol class="text-sm text-ink-2 leading-relaxed space-y-1.5 list-decimal pl-5">
                <li>打开 <a href="https://lbs.amap.com/" target="_blank" class="underline underline-offset-2 hover:text-warm">lbs.amap.com</a> → 右上角「注册」→ 用支付宝扫码实名认证（个人账户即可，配额 2000 次/日）</li>
                <li>控制台 → 应用管理 → 我的应用 → 「创建新应用」→ 类型选「其它」</li>
                <li>点进新应用 → 「添加 Key」→ 服务平台选「Web 服务」→ 白名单填 <code class="font-mono text-xs bg-paper-2 px-1.5 py-0.5">localhost, 127.0.0.1</code> + 你的域名</li>
                <li>复制生成的 Key → 写到项目 <code class="font-mono text-xs bg-paper-2 px-1.5 py-0.5">.env</code> 的 <code class="font-mono text-xs bg-paper-2 px-1.5 py-0.5">AMAP_WEB_KEY=...</code> → <code class="font-mono text-xs bg-paper-2 px-1.5 py-0.5">php artisan config:clear</code> → 刷新本页</li>
            </ol>
            <p class="mt-3 text-xs text-ink-3 font-mono">
                详细图文：<a href="https://lbs.amap.com/api/webservice/create-project-and-key" target="_blank" class="underline">lbs.amap.com/api/webservice/create-project-and-key</a>
            </p>
        </div>
    @endif

    {{-- 头版：编辑感标题 + 步骤编号 --}}
    <div class="mb-8 pb-6 border-b border-ink/15">
        <div class="flex items-baseline gap-3 mb-2 font-mono text-[10px] uppercase tracking-[0.2em] text-ink-3">
            <span>§ 01</span>
            <span class="w-px h-3 bg-ink/15"></span>
            <span>从高德地图导入 POI</span>
            <span class="w-px h-3 bg-ink/15"></span>
            <span>2026 · 高德公开 API</span>
        </div>
        <h1 class="font-display text-3xl sm:text-4xl text-ink leading-tight">
            把高德收藏夹的地点，<br>
            <span class="serif-italic text-warm">搬进你的 Marker</span>
        </h1>
        <p class="mt-3 text-sm text-ink-2 max-w-2xl leading-relaxed">
            两种方式：粘贴高德分享链接自动识别 ugcId，或直接用关键字 + 城市搜 POI 列表。搜出来的地点，勾选后批量加入你的收藏。
        </p>
    </div>

    {{-- 方式 A：分享链接 --}}
    <div class="mb-8 border border-ink/20 bg-paper">
        <div class="flex items-center justify-between px-5 py-3 border-b border-ink/15 bg-paper-2">
            <div class="flex items-baseline gap-3">
                <span class="font-mono text-[10px] uppercase tracking-[0.2em] text-ink-3">A</span>
                <span class="font-display text-base text-ink">分享链接解析</span>
            </div>
            <span class="font-mono text-[9px] uppercase tracking-[0.15em] text-ink-3">识别 ugcId</span>
        </div>
        <div class="p-5">
            <p class="text-xs text-ink-2 leading-relaxed mb-4">
                高德收藏夹 web 端需要登录态，链接直拉受限。建议复制一条收藏夹分享链接 → 解析 ugcId → 用下方方式 B 关键字搜 POI。
            </p>
            <div class="flex gap-2">
                <input
                    type="text"
                    wire:model="shareUrl"
                    placeholder="https://guinness.autonavi.com/activity/... 或 amapuri://..."
                    class="flex-1 bg-transparent border-0 border-b border-ink/30 focus:border-ink focus:ring-0 outline-none px-0 py-2 text-sm text-ink placeholder:text-ink-3 font-mono"
                >
                <button
                    wire:click="parseShareUrl"
                    wire:loading.attr="disabled"
                    class="font-mono text-[10px] uppercase tracking-[0.18em] px-5 py-2 bg-ink text-paper border border-ink hover:bg-ink-2 transition-colors disabled:opacity-50"
                >
                    <span wire:loading.remove wire:target="parseShareUrl">解析</span>
                    <span wire:loading wire:target="parseShareUrl">解析中…</span>
                </button>
            </div>
        </div>
    </div>

    {{-- 方式 B：关键字搜索 --}}
    <div class="mb-8 border border-ink/20 bg-paper">
        <div class="flex items-center justify-between px-5 py-3 border-b border-ink/15 bg-paper-2">
            <div class="flex items-baseline gap-3">
                <span class="font-mono text-[10px] uppercase tracking-[0.2em] text-ink-3">B</span>
                <span class="font-display text-base text-ink">关键字搜索</span>
            </div>
            <span class="font-mono text-[9px] uppercase tracking-[0.15em] text-ink-3">推荐</span>
        </div>
        <div class="p-5">
            <p class="text-xs text-ink-2 leading-relaxed mb-4">
                输入地点名（收藏夹里最典型的 1-2 个）+ 城市。从高德搜完整 POI 列表，按地点名 + 距离匹配。
            </p>
            <div class="grid grid-cols-1 md:grid-cols-[1fr_180px] gap-3 mb-4">
                <input
                    type="text"
                    wire:model="keywords"
                    placeholder="如：莫干山、兰州拉面、千岛湖"
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
                    class="font-mono text-[10px] uppercase tracking-[0.18em] px-5 py-2 bg-ink text-paper border border-ink hover:bg-ink-2 transition-colors disabled:opacity-50"
                >
                    <span wire:loading.remove wire:target="search">搜索 POI</span>
                    <span wire:loading wire:target="search">搜索中…</span>
                </button>

                @if($searched && count($results) > 0)
                    <span class="font-mono text-[10px] uppercase tracking-[0.15em] text-ink-3">
                        共 {{ count($results) }} 个结果
                    </span>
                @endif
            </div>
        </div>
    </div>

    {{-- 错误 / ugcId 识别结果 --}}
    @if($error)
        <div class="mb-6 border border-ink/30 bg-paper-2 p-4 font-mono text-xs text-ink-2 whitespace-pre-line leading-relaxed">{{ $error }}</div>
    @endif

    {{-- 搜索结果：批量操作 + 列表 --}}
    @if($searched && count($results) > 0)
        <div class="mb-6 flex items-center gap-4 px-5 py-3 border border-ink/20 bg-paper-2">
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
                wire:click="importSelected"
                wire:loading.attr="disabled"
                class="ml-auto font-mono text-[10px] uppercase tracking-[0.18em] px-5 py-2 bg-warm text-paper border border-warm hover:bg-warm/90 transition-colors disabled:opacity-50"
            >
                导入所选
            </button>
        </div>

        <div class="border border-ink/20 divide-y divide-ink/10 max-h-[600px] overflow-y-auto">
            @foreach($results as $idx => $poi)
                <label class="flex items-start gap-4 px-5 py-3 {{ in_array($idx, $selected) ? 'bg-ink text-paper' : 'hover:bg-paper-2' }} cursor-pointer transition-colors">
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
        <div class="border border-ink/20 bg-paper p-12 text-center">
            <div class="font-display text-2xl text-ink-2 mb-2">没有结果</div>
            <p class="text-sm text-ink-3">换个关键字或城市试试</p>
        </div>
    @endif
</x-filament-panels::page>
