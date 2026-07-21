@extends('frontend.layout')

@section('title', '注册 · Marker')

@section('content')
<section class="border-b border-line-2 bg-paper-2">
    <div class="max-w-6xl mx-auto px-5 sm:px-8 py-4">
        <div class="flex items-center gap-3 font-mono text-[10px] uppercase tracking-[0.2em] text-ink-3">
            <a href="/" class="hover:text-ink transition-colors">← BACK</a>
            <span class="w-px h-3 bg-line-2"></span>
            <span>ACCOUNT</span>
        </div>
    </div>
</section>

<section class="border-b border-line">
    <div class="max-w-6xl mx-auto px-5 sm:px-8 py-8 sm:py-20">
        <div class="grid grid-cols-12 gap-6 sm:gap-12">
            <div class="col-span-12 sm:col-span-7">
                <span class="eyebrow">N°02 · SIGN UP</span>
                <h1 class="font-display font-medium text-4xl sm:text-7xl leading-[1.0] text-ink mt-3">
                    加入<br>读者群
                </h1>
                <p class="font-display italic text-lg sm:text-xl text-ink-2 mt-4">成为这本公路杂志的共同编辑者。</p>

                @if($errors->any())
                    <div class="mt-8 p-4 border-l-2 border-blood bg-paper-2 max-w-md">
                        <div class="eyebrow text-blood mb-1">ERROR</div>
                        <p class="text-sm text-ink">{{ $errors->first() }}</p>
                    </div>
                @endif
            </div>

            <div class="col-span-12 sm:col-span-5 sm:pt-12">
                <form method="POST" action="/register" class="space-y-6">
                    @csrf
                    <div>
                        <label for="name" class="label">昵称 · NAME</label>
                        <input id="name" name="name" type="text" required value="{{ old('name') }}"
                               class="input" placeholder="怎么称呼你">
                    </div>
                    <div>
                        <label for="email" class="label">邮箱 · EMAIL</label>
                        <input id="email" name="email" type="email" required value="{{ old('email') }}"
                               class="input" placeholder="you@example.com">
                    </div>
                    <div>
                        <label for="password" class="label">密码 · PASSWORD（6 位以上）</label>
                        <input id="password" name="password" type="password" required minlength="6"
                               class="input" placeholder="••••••••">
                    </div>
                    <div>
                        <label for="password_confirmation" class="label">确认密码 · CONFIRM</label>
                        <input id="password_confirmation" name="password_confirmation" type="password" required minlength="6"
                               class="input" placeholder="再输入一次">
                    </div>

                    <div class="pt-2">
                        <button type="submit" class="btn btn-primary w-full">
                            注册并登录
                            <span class="font-mono text-[10px] opacity-70">→</span>
                        </button>
                    </div>
                </form>

                <div class="mt-6 pt-6 border-t border-line font-mono text-[11px] text-ink-2">
                    已有账号？
                    <a href="/login" class="link">直接登录</a>
                </div>
            </div>
        </div>
    </div>
</section>
@endsection
