@extends('frontend.layout')

@section('title', '我的收藏集 · Marker')

@section('content')
<section class="px-4 py-3 max-w-2xl mx-auto flex items-baseline justify-between">
    <div>
        <h1 class="text-xl font-bold text-gray-900">📂 收藏集</h1>
        <p class="text-xs text-gray-500 mt-1">共 {{ $collections->count() }} 个</p>
    </div>
    <a href="/admin/collections/create" class="text-sm text-emerald-600">+ 新建</a>
</section>

<div class="max-w-2xl mx-auto space-y-2 px-4">
    @forelse($collections as $c)
        <a href="{{ url('/me/collections/' . $c->id) }}" class="block p-3 bg-white rounded-xl shadow-sm">
            <div class="flex items-center gap-3">
                <div class="w-12 h-12 rounded-lg flex items-center justify-center text-2xl bg-pink-50">
                    📂
                </div>
                <div class="flex-1 min-w-0">
                    <div class="font-medium text-sm">{{ $c->name }}</div>
                    <div class="text-xs text-gray-500 mt-0.5">
                        {{ $c->places_count }} 个地点
                        @if($c->is_public)
                            · <span class="text-emerald-600">🔗 公开</span>
                        @else
                            · <span class="text-gray-400">🔒 私有</span>
                        @endif
                    </div>
                    @if($c->description)
                        <p class="text-xs text-gray-400 mt-1 line-clamp-1">{{ $c->description }}</p>
                    @endif
                </div>
            </div>
        </a>
    @empty
        <div class="py-20 text-center text-gray-400">
            <div class="text-4xl mb-2">📂</div>
            <p class="text-sm">还没有收藏集</p>
        </div>
    @endforelse
</div>
@endsection
