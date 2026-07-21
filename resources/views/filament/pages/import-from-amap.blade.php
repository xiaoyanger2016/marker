<x-filament-panels::page>
    <x-filament::section>
        <x-slot name="heading">📥 从高德地图导入 POI</x-slot>
        <x-slot name="description">
            输入关键字 + 城市，从高德搜 POI 列表，勾选要导入的批量加入你的收藏。
        </x-slot>

        {{-- 方式 1: 粘贴高德收藏夹分享链接 --}}
        <div class="mb-6 p-4 bg-amber-50 border border-amber-200 rounded-lg">
            <h3 class="font-semibold text-amber-900 mb-2">📌 方式 A：粘贴高德分享链接</h3>
            <p class="text-xs text-amber-700 mb-3">
                高德收藏夹 web 端需要登录态直接拿不到，<strong>请改用下方关键字搜索</strong>。链接可作为参考（识别 ugcId）：
            </p>
            <div class="flex gap-2">
                <input
                    type="text"
                    wire:model="shareUrl"
                    placeholder="https://guinness.autonavi.com/activity/... 或 amapuri://ajx_favorites/folder?data=..."
                    class="flex-1 px-3 py-2 text-sm border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-amber-400"
                >
                <button
                    wire:click="parseShareUrl"
                    wire:loading.attr="disabled"
                    class="px-4 py-2 text-sm bg-amber-500 hover:bg-amber-600 text-white rounded-md whitespace-nowrap"
                >
                    解析
                </button>
            </div>
        </div>

        {{-- 方式 2: 关键字搜索 --}}
        <div class="mb-6 p-4 bg-emerald-50 border border-emerald-200 rounded-lg">
            <h3 class="font-semibold text-emerald-900 mb-2">🔍 方式 B：关键字搜索（推荐）</h3>
            <p class="text-xs text-emerald-700 mb-3">输入地点名（收藏夹里典型地点）+ 城市，搜出完整 POI 列表</p>
            <div class="grid grid-cols-1 md:grid-cols-3 gap-2 mb-3">
                <input
                    type="text"
                    wire:model="keywords"
                    placeholder="如：莫干山、兰州拉面、千岛湖"
                    class="px-3 py-2 text-sm border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-emerald-400 md:col-span-2"
                >
                <input
                    type="text"
                    wire:model="city"
                    placeholder="城市（可选）如：杭州"
                    class="px-3 py-2 text-sm border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-emerald-400"
                >
            </div>
            <div class="flex items-center gap-3">
                <button
                    wire:click="search"
                    wire:loading.attr="disabled"
                    class="px-4 py-2 text-sm bg-emerald-500 hover:bg-emerald-600 text-white rounded-md flex items-center gap-1"
                >
                    <span wire:loading.remove wire:target="search">🔍 搜索</span>
                    <span wire:loading wire:target="search">搜索中...</span>
                </button>

                @if($searched && count($results) > 0)
                    <span class="text-xs text-gray-500">共 {{ count($results) }} 个结果</span>
                @endif
            </div>
        </div>

        {{-- 错误信息 --}}
        @if($error)
            <div class="mb-4 p-3 bg-red-50 border border-red-200 rounded-md text-sm text-red-700 whitespace-pre-line">{{ $error }}</div>
        @endif

        {{-- 搜索结果 --}}
        @if($searched && count($results) > 0)
            <div class="mb-4 p-3 bg-gray-50 rounded-md flex items-center gap-3">
                <label class="text-sm font-medium text-gray-700">批量操作：</label>
                <select
                    wire:model.live="placeType"
                    class="text-sm border border-gray-300 rounded px-2 py-1"
                >
                    <option value="">— 导入后类型待定 —</option>
                    @foreach($placeTypes as $key => $meta)
                        <option value="{{ $key }}">{{ $meta['icon'] }} {{ $meta['label'] }}</option>
                    @endforeach
                </select>
                <span class="text-xs text-gray-500">已选 {{ count($selected) }} / {{ count($results) }}</span>
                <button
                    wire:click="importSelected"
                    wire:loading.attr="disabled"
                    class="ml-auto px-4 py-1.5 text-sm bg-blue-500 hover:bg-blue-600 text-white rounded-md"
                >
                    导入所选
                </button>
            </div>

            <div class="space-y-1.5 max-h-[500px] overflow-y-auto">
                @foreach($results as $idx => $poi)
                    <label class="flex items-start gap-3 p-2.5 border {{ in_array($idx, $selected) ? 'border-emerald-400 bg-emerald-50' : 'border-gray-200 hover:border-gray-300' }} rounded-md cursor-pointer transition-colors">
                        <input
                            type="checkbox"
                            wire:model.live="selected"
                            value="{{ $idx }}"
                            class="mt-1 w-4 h-4"
                        >
                        <div class="flex-1 min-w-0">
                            <div class="font-medium text-sm text-gray-900">{{ $poi['name'] }}</div>
                            <div class="text-xs text-gray-500 mt-0.5">
                                📍 {{ $poi['pname'] }} {{ $poi['cityname'] }} {{ $poi['adname'] }} · {{ $poi['address'] }}
                            </div>
                            <div class="text-[10px] text-gray-400 mt-0.5">
                                🏷️ {{ $poi['type'] ?: '未分类' }}
                                @if($poi['tel'])
                                    · 📞 {{ $poi['tel'] }}
                                @endif
                                · 🆔 {{ $poi['id'] }}
                            </div>
                        </div>
                        <div class="text-[10px] text-gray-400 font-mono whitespace-nowrap">
                            {{ round($poi['longitude'], 5) }},<br>{{ round($poi['latitude'], 5) }}
                        </div>
                    </label>
                @endforeach
            </div>
        @elseif($searched)
            <div class="p-8 text-center text-gray-400">
                <div class="text-3xl mb-2">😶</div>
                <p class="text-sm">没有结果，换个关键字试试</p>
            </div>
        @endif
    </x-filament::section>
</x-filament-panels::page>
