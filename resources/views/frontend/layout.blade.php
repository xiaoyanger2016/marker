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

    {{-- PC 端：左侧 sidebar 导航（登录/注册页除外，页面简洁优先） --}}
    @if(!($isMobile ?? true) && ! in_array(request()->path(), ['login', 'register', 'profile']))
    <div class="flex max-w-7xl mx-auto">
        <aside class="hidden md:block w-56 flex-shrink-0 border-r border-gray-100 min-h-screen bg-white">
            <nav class="sticky top-16 p-3 space-y-1 text-sm">
                <a href="/" class="block px-3 py-2 rounded {{ request()->path() === '/' ? 'bg-emerald-50 text-emerald-600 font-medium' : 'text-gray-700 hover:bg-gray-50' }}">🏠 首页</a>
                <a href="/activities" class="block px-3 py-2 rounded {{ request()->path() === 'activities' ? 'bg-emerald-50 text-emerald-600 font-medium' : 'text-gray-700 hover:bg-gray-50' }}">🎒 活动</a>
                <a href="/radar" class="block px-3 py-2 rounded {{ request()->path() === 'radar' ? 'bg-emerald-50 text-emerald-600 font-medium' : 'text-gray-700 hover:bg-gray-50' }}">📡 雷达</a>
                <a href="/me" class="block px-3 py-2 rounded {{ request()->is('me*') ? 'bg-emerald-50 text-emerald-600 font-medium' : 'text-gray-700 hover:bg-gray-50' }}">👤 我的</a>
                <div class="border-t border-gray-100 my-2"></div>
                @foreach(\App\Http\Controllers\Frontend\HomeController::TYPES as $t)
                    <a href="/type/{{ $t['key'] }}" class="flex items-center gap-2 px-3 py-1.5 rounded text-gray-600 hover:bg-gray-50">
                        <span>{{ $t['icon'] }}</span><span>{{ $t['label'] }}</span>
                    </a>
                @endforeach
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
