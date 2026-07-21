<!DOCTYPE html>
<html lang="zh-CN">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no, viewport-fit=cover">
    <meta name="theme-color" content="#10b981">
    <meta name="format-detection" content="telephone=no">
    <title>@yield('title', 'Marker · 我的收藏地图')</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    @stack('head')
</head>
<body class="min-h-screen bg-gray-50 text-gray-900 antialiased" style="font-family: -apple-system, BlinkMacSystemFont, 'PingFang SC', 'Microsoft YaHei', sans-serif;">
    @include('frontend.partials.nav')

    {{-- PC 端：左侧 sidebar 导航（编辑感排版：编号 + 衬线标签） --}}
    @if(!($isMobile ?? true) && ! in_array(request()->path(), ['login', 'register', 'profile']))
    <div class="flex max-w-7xl mx-auto">
        <aside class="hidden md:block w-64 flex-shrink-0 border-r border-line min-h-screen bg-paper">
            <nav class="sticky top-14 p-6 space-y-6">
                <div>
                    <span class="eyebrow">导航 · Navigation</span>
                    <div class="mt-3 space-y-2">
                        <a href="/" class="block font-display text-lg {{ request()->path() === '/' ? 'text-ink' : 'text-ink-2 hover:text-ink' }} transition-colors">
                            <span class="font-mono text-[10px] text-ink-3 mr-2">N°01</span>首页
                        </a>
                        <a href="/activities" class="block font-display text-lg {{ request()->is('activities*') ? 'text-ink' : 'text-ink-2 hover:text-ink' }} transition-colors">
                            <span class="font-mono text-[10px] text-ink-3 mr-2">N°02</span>活动
                        </a>
                        <a href="/radar" class="block font-display text-lg {{ request()->is('radar') ? 'text-ink' : 'text-ink-2 hover:text-ink' }} transition-colors">
                            <span class="font-mono text-[10px] text-ink-3 mr-2">N°03</span>雷达
                        </a>
                        <a href="/me" class="block font-display text-lg {{ request()->is('me*') ? 'text-ink' : 'text-ink-2 hover:text-ink' }} transition-colors">
                            <span class="font-mono text-[10px] text-ink-3 mr-2">N°04</span>我的
                        </a>
                    </div>
                </div>

                <div class="border-t border-line pt-6">
                    <span class="eyebrow">类型 · Types</span>
                    <div class="mt-3 space-y-2">
                        @foreach(\App\Http\Controllers\Frontend\HomeController::TYPES as $i => $t)
                            <a href="/type/{{ $t['key'] }}" class="flex items-baseline gap-3 group">
                                <span class="font-mono text-[10px] text-ink-3 group-hover:text-warm transition-colors">{{ str_pad($i + 1, 2, '0', STR_PAD_LEFT) }}</span>
                                <span class="font-display text-sm text-ink-2 group-hover:text-ink transition-colors">{{ $t['label'] }}</span>
                                <span class="flex-1 border-b border-dotted border-line-2 self-end mb-0.5"></span>
                                <span class="font-mono text-[9px] text-ink-3">→</span>
                            </a>
                        @endforeach
                    </div>
                </div>

                <div class="border-t border-line pt-6">
                    <p class="font-mono text-[10px] uppercase tracking-[0.15em] text-ink-3 leading-relaxed">
                        Made with hands ·<br>No algorithm
                    </p>
                </div>
            </nav>
        </aside>
        <main class="flex-1 @yield('main_class', 'pb-12')">
            @yield('content')
        </main>
    </div>
    @include('frontend.partials.tabbar_hidden')
    @else
    <main class="@yield('main_class', 'pb-20')">
        @yield('content')
    </main>
    @include('frontend.partials.tabbar')
    @endif

    @stack('scripts')
</body>
</html>
