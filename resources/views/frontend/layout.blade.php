<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', $locale ?? 'zh-CN') }}" data-theme="{{ $theme ?? 'paper' }}">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no, viewport-fit=cover">
    {{-- 强制每次重新请求，避免开发期改完用户看到的还是旧 CSS/HTML --}}
    <meta http-equiv="Cache-Control" content="no-cache, no-store, must-revalidate">
    <meta http-equiv="Pragma" content="no-cache">
    <meta http-equiv="Expires" content="0">
    <meta name="theme-color" content="#F2EDE2" media="(prefers-color-scheme: light)">
    <meta name="theme-color" content="#0E0D0B" media="(prefers-color-scheme: dark)">
    <meta name="format-detection" content="telephone=no">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', 'Marker · 我的收藏地图')</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    <script>
        // SSR 阶段设主题，渲染前避免主题闪烁
        (function() {
            try {
                var t = localStorage.getItem('marker.theme') || '{{ $theme ?? "paper" }}';
                // 5 主题：paper / sand / ink / mono / auto
                if (['paper', 'sand', 'ink', 'mono', 'auto'].indexOf(t) === -1) t = 'paper';
                document.documentElement.setAttribute('data-theme', t);
            } catch (e) {}
        })();
    </script>
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
                    <span class="eyebrow">{{ __('ui.nav_navigation') }}</span>
                    <div class="mt-3 space-y-2">
                        <a href="/" class="block font-display text-lg {{ request()->path() === '/' ? 'text-ink' : 'text-ink-2 hover:text-ink' }} transition-colors">
                            <span class="font-mono text-[10px] text-ink-3 mr-2">N°01</span>{{ __('ui.nav_home') }}
                        </a>
                        <a href="/activities" class="block font-display text-lg {{ request()->is('activities*') ? 'text-ink' : 'text-ink-2 hover:text-ink' }} transition-colors">
                            <span class="font-mono text-[10px] text-ink-3 mr-2">N°02</span>{{ __('ui.nav_activity') }}
                        </a>
                        <a href="/radar" class="block font-display text-lg {{ request()->is('radar') ? 'text-ink' : 'text-ink-2 hover:text-ink' }} transition-colors">
                            <span class="font-mono text-[10px] text-ink-3 mr-2">N°03</span>{{ __('ui.nav_radar') }}
                        </a>
                        <a href="/me" class="block font-display text-lg {{ request()->is('me*') ? 'text-ink' : 'text-ink-2 hover:text-ink' }} transition-colors">
                            <span class="font-mono text-[10px] text-ink-3 mr-2">N°04</span>{{ __('ui.nav_me') }}
                        </a>
                    </div>
                </div>

                <div class="border-t border-line pt-6">
                    <span class="eyebrow">{{ __('ui.nav_types') }}</span>
                    <div class="mt-3 space-y-2">
                        @foreach(\App\Http\Controllers\Frontend\HomeController::TYPES as $i => $t)
                            <a href="/type/{{ $t['key'] }}" class="flex items-baseline gap-3 group">
                                <span class="font-mono text-[10px] text-ink-3 group-hover:text-warm transition-colors">{{ str_pad($i + 1, 2, '0', STR_PAD_LEFT) }}</span>
                                <span class="font-display text-sm text-ink-2 group-hover:text-ink transition-colors">{{ __($t['label_key']) }}</span>
                                <span class="flex-1 border-b border-dotted border-line-2 self-end mb-0.5"></span>
                                <span class="font-mono text-[9px] text-ink-3">→</span>
                            </a>
                        @endforeach
                    </div>
                </div>

                {{-- 语言切换 --}}
                <div class="border-t border-line pt-6">
                    <span class="eyebrow">{{ __('ui.nav_language') }}</span>
                    <div class="mt-3 grid grid-cols-5 gap-1">
                        @php
                            $langs = ['zh-CN' => '简', 'zh-TW' => '繁', 'en' => 'EN', 'ja' => '日', 'ko' => '한'];
                        @endphp
                        @foreach($langs as $code => $short)
                            <a href="{{ url('/lang/' . $code) }}"
                               class="text-center font-mono text-[11px] py-1 border {{ ($locale ?? 'zh-CN') === $code ? 'border-ink bg-ink text-paper' : 'border-line text-ink-2 hover:border-ink hover:text-ink' }}">
                                {{ $short }}
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
    <main class="@yield('main_class', 'pb-36 sm:pb-28')">
        @yield('content')
    </main>
    @include('frontend.partials.tabbar')
    @endif

    @stack('scripts')
</body>
</html>
