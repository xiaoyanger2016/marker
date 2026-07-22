@extends('frontend.layout', ['bodyClass' => 'page-not-found'])

@section('title', '未找到 · 404 · Marker')
@section('head_extra')
<meta name="robots" content="noindex">
@endsection

@section('content')
<section class="min-h-[60vh] flex items-center justify-center px-5 sm:px-8 py-16 sm:py-24">
    <div class="max-w-xl w-full text-center">
        <div class="font-mono text-[10px] uppercase tracking-[0.3em] text-ink-3 mb-4">
            ERROR · 404
        </div>

        <h1 class="font-display text-5xl sm:text-7xl text-ink leading-none mb-4">
            没找到这页
        </h1>

        <p class="text-sm text-ink-2 mb-2">
            @if(isset($exception) && $exception?->getMessage() && $exception->getMessage() !== '')
                {{ $exception->getMessage() }}
            @else
                这个链接已经不存在，或者对应的内容 / 地点 / 笔记被删除 / 改为了私有。
            @endif
        </p>

        @if(isset($exception) && $exception?->getPrevious()?->getMessage())
            <p class="text-xs text-ink-3 mb-2 font-mono">{{ $exception->getPrevious()->getMessage() }}</p>
        @endif

        <p class="text-xs text-ink-3 mb-10 font-mono">
            请求路径：<span class="text-ink-2">{{ request()->path() }}</span>
        </p>

        <div class="flex flex-col sm:flex-row items-center justify-center gap-3 sm:gap-4 mb-10">
            <a href="{{ url('/') }}" class="font-mono text-xs uppercase tracking-[0.15em] px-5 py-2.5 bg-ink text-paper hover:bg-warm transition-colors">
                回首页
            </a>
            <a href="{{ url('/me') }}" class="font-mono text-xs uppercase tracking-[0.15em] px-5 py-2.5 border border-line hover:border-ink transition-colors">
                我的内容
            </a>
            <a href="{{ url('/map') }}" class="font-mono text-xs uppercase tracking-[0.15em] px-5 py-2.5 border border-line hover:border-ink transition-colors">
                打开地图
            </a>
        </div>

        @php
            $path = request()->path();
            // 自动推断目标类型
            $suggestion = null;
            if (preg_match('#^content/(\d+)#', $path, $m)) {
                $suggestion = ['label' => '查看内容 #' . $m[1], 'url' => url('/content/' . $m[1])];
            } elseif (preg_match('#^place/(\d+)#', $path, $m)) {
                $suggestion = ['label' => '查看地点 #' . $m[1], 'url' => url('/place/' . $m[1])];
            }
        @endphp

        @if($suggestion)
            <p class="text-xs text-ink-3 mb-2">{{ $suggestion['label'] }}</p>
            <a href="{{ $suggestion['url'] }}" class="text-xs text-warm underline break-all">{{ $suggestion['url'] }}</a>
        @endif
    </div>
</section>
@endsection
