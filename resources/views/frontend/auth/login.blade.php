@extends('frontend.layout')

@section('title', '登录 · Marker')

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
            {{-- 左侧：编辑感标题 --}}
            <div class="col-span-12 sm:col-span-7">
                <span class="eyebrow">N°01 · LOG IN</span>
                <h1 class="font-display font-medium text-4xl sm:text-7xl leading-[1.0] text-ink mt-3">
                    欢迎<br>回来
                </h1>
                <p class="font-display italic text-lg sm:text-xl text-ink-2 mt-4">继续你的公路地图志。</p>

                @if($errors->any())
                    <div class="mt-8 p-4 border-l-2 border-blood bg-paper-2 max-w-md">
                        <div class="eyebrow text-blood mb-1">ERROR</div>
                        <p class="text-sm text-ink">{{ $errors->first() }}</p>
                    </div>
                @endif
            </div>

            {{-- 右侧：表单 --}}
            <div class="col-span-12 sm:col-span-5 sm:pt-12">
                <form method="POST" action="/login" class="space-y-6">
                    @csrf
                    <div>
                        <label for="email" class="label">邮箱 · EMAIL</label>
                        <input id="email" name="email" type="email" required value="{{ old('email') }}"
                               class="input" placeholder="you@example.com">
                    </div>
                    <div>
                        <label for="password" class="label">密码 · PASSWORD</label>
                        <input id="password" name="password" type="password" required
                               class="input" placeholder="••••••••">
                    </div>

                    <div class="pt-2">
                        <button type="submit" class="btn btn-primary w-full">
                            登录
                            <span class="font-mono text-[10px] opacity-70">→</span>
                        </button>
                    </div>
                </form>

                <div class="mt-6 pt-6 border-t border-line font-mono text-[11px] text-ink-2">
                    还没有账号？
                    <a href="/register" class="link">立即注册</a>
                </div>

                <div class="mt-8 p-4 border border-line">
                    <div class="eyebrow mb-2">DEMO</div>
                    <div class="font-mono text-[11px] text-ink-2 space-y-1">
                        <div>email：<span class="text-ink">eric@marker.local</span></div>
                        <div>pass：<span class="text-ink">password</span></div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>
@endsection
