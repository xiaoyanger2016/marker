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

    <main class="@yield('main_class', 'pb-20')">
        @yield('content')
    </main>

    @include('frontend.partials.tabbar')

    @stack('scripts')
</body>
</html>
