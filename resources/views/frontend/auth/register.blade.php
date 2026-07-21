@extends('frontend.layout')

@section('title', '注册 · Marker')

@section('content')
<section class="px-4 py-10">
    <div class="max-w-sm mx-auto">
        <div class="text-center mb-6">
            <div class="text-5xl mb-2">📍</div>
            <h1 class="text-2xl font-bold text-gray-900">加入 Marker</h1>
            <p class="text-sm text-gray-500 mt-1">一起收藏自驾宝藏</p>
        </div>

        @if($errors->any())
            <div class="mb-4 p-3 bg-red-50 border border-red-200 text-red-700 text-sm rounded-lg">
                {{ $errors->first() }}
            </div>
        @endif

        <form method="POST" action="/register" class="space-y-3">
            @csrf
            <div>
                <label class="block text-xs text-gray-600 mb-1">昵称</label>
                <input name="name" type="text" required value="{{ old('name') }}"
                       class="w-full px-3 py-2.5 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-emerald-500 focus:border-emerald-500 outline-none"
                       placeholder="怎么称呼你">
            </div>
            <div>
                <label class="block text-xs text-gray-600 mb-1">邮箱</label>
                <input name="email" type="email" required value="{{ old('email') }}"
                       class="w-full px-3 py-2.5 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-emerald-500 focus:border-emerald-500 outline-none"
                       placeholder="you@example.com">
            </div>
            <div>
                <label class="block text-xs text-gray-600 mb-1">密码（至少 6 位）</label>
                <input name="password" type="password" required
                       class="w-full px-3 py-2.5 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-emerald-500 focus:border-emerald-500 outline-none"
                       placeholder="••••••••">
            </div>
            <div>
                <label class="block text-xs text-gray-600 mb-1">确认密码</label>
                <input name="password_confirmation" type="password" required
                       class="w-full px-3 py-2.5 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-emerald-500 focus:border-emerald-500 outline-none"
                       placeholder="再输入一次">
            </div>
            <button type="submit"
                    class="w-full py-2.5 bg-emerald-500 hover:bg-emerald-600 text-white font-medium rounded-lg text-sm">
                注册并登录
            </button>
        </form>

        <div class="mt-5 text-center text-sm text-gray-600">
            已有账号？<a href="/login" class="text-emerald-600 font-medium">直接登录</a>
        </div>
    </div>
</section>
@endsection
