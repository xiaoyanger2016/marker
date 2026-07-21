@extends('frontend.layout')

@section('title', '登录 · Marker')

@section('content')
<section class="px-4 py-10">
    <div class="max-w-sm mx-auto">
        <div class="text-center mb-6">
            <div class="text-5xl mb-2">📍</div>
            <h1 class="text-2xl font-bold text-gray-900">登录 Marker</h1>
            <p class="text-sm text-gray-500 mt-1">收藏你的自驾宝藏</p>
        </div>

        @if($errors->any())
            <div class="mb-4 p-3 bg-red-50 border border-red-200 text-red-700 text-sm rounded-lg">
                {{ $errors->first() }}
            </div>
        @endif

        <form method="POST" action="/login" class="space-y-3">
            @csrf
            <div>
                <label class="block text-xs text-gray-600 mb-1">邮箱</label>
                <input name="email" type="email" required value="{{ old('email') }}"
                       class="w-full px-3 py-2.5 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-emerald-500 focus:border-emerald-500 outline-none"
                       placeholder="you@example.com">
            </div>
            <div>
                <label class="block text-xs text-gray-600 mb-1">密码</label>
                <input name="password" type="password" required
                       class="w-full px-3 py-2.5 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-emerald-500 focus:border-emerald-500 outline-none"
                       placeholder="••••••••">
            </div>
            <button type="submit"
                    class="w-full py-2.5 bg-emerald-500 hover:bg-emerald-600 text-white font-medium rounded-lg text-sm">
                登录
            </button>
        </form>

        <div class="mt-5 text-center text-sm text-gray-600">
            还没有账号？<a href="/register" class="text-emerald-600 font-medium">立即注册</a>
        </div>

        <div class="mt-4 p-3 bg-gray-50 rounded-lg text-xs text-gray-500 leading-relaxed">
            <div class="font-medium text-gray-700 mb-1">💡 测试账号</div>
            <div>邮箱：<code class="text-emerald-600">eric@marker.local</code></div>
            <div>密码：<code class="text-emerald-600">password</code></div>
        </div>
    </div>
</section>
@endsection
