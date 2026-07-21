@extends('frontend.layout')

@section('title', '我的线路 · Marker')

@section('content')
<section class="px-4 py-3 max-w-2xl mx-auto">
    <h1 class="text-xl font-bold text-gray-900">🛣️ 我的线路</h1>
    <p class="text-xs text-gray-500 mt-1">共 {{ $routes->total() }} 条</p>
</section>

<div class="max-w-2xl mx-auto space-y-2 px-4">
    @forelse($routes as $r)
        <a href="{{ url('/route/' . $r->id) }}" class="block p-3 bg-white rounded-xl shadow-sm hover:shadow-md">
            <div class="flex items-center gap-3">
                <div class="w-12 h-12 rounded-lg flex items-center justify-center text-2xl text-white" style="background: {{ $r->typeMeta()['color'] ?? '#10b981' }}">
                    {{ $r->typeMeta()['icon'] ?? '🚗' }}
                </div>
                <div class="flex-1 min-w-0">
                    <div class="font-medium text-sm truncate">{{ $r->name }}</div>
                    <div class="text-xs text-gray-500 mt-0.5">
                        {{ $r->typeMeta()['label'] ?? '' }}
                        · {{ $r->places_count }} 个点
                        @if($r->distance_km) · {{ $r->distance_km }}km @endif
                    </div>
                </div>
                <div class="flex flex-col items-end gap-1">
                    @if($r->is_public)
                        <span class="text-[10px] text-emerald-600">已上架</span>
                    @else
                        <span class="text-[10px] text-gray-400">下架</span>
                    @endif
                    @if($r->rating_label)
                        @php $rl = \App\Models\Route::RATING_LABELS[$r->rating_label] ?? null; @endphp
                        @if($rl)
                            <span class="px-1.5 py-0.5 text-[10px] font-bold text-white rounded" style="background:{{ $rl['color'] }}">{{ $rl['label'] }}</span>
                        @endif
                    @endif
                </div>
            </div>
        </a>
    @empty
        <div class="py-20 text-center text-gray-400">
            <div class="text-4xl mb-2">🛣️</div>
            <p class="text-sm">还没有线路</p>
            <a href="/admin/routes/create" class="text-emerald-600 text-sm">+ 创建一条</a>
        </div>
    @endforelse
</div>

@if($routes->hasPages())
    <div class="px-4 py-4 max-w-2xl mx-auto">{{ $routes->links() }}</div>
@endif
@endsection
