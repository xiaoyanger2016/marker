@extends('frontend.layout')

@section('title', $type['label'] . ' · Marker')

@section('content')
<section class="px-4 py-4 text-white" style="background: linear-gradient(135deg, {{ $type['color'] }}, {{ $type['color'] }}aa);">
    <div class="max-w-2xl mx-auto flex items-center gap-3">
        <a href="{{ url('/') }}" class="text-white/80">
            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"></path></svg>
        </a>
        <div class="flex-1">
            <h1 class="text-xl font-bold flex items-center gap-2">
                <span class="text-2xl">{{ $type['icon'] }}</span>
                {{ $type['label'] }}
            </h1>
            <p class="text-sm text-white/80 mt-0.5">{{ $type['desc'] }}</p>
        </div>
    </div>
</section>

<section class="px-4 py-3">
    <div class="text-xs text-gray-500 max-w-2xl mx-auto mb-2">共 {{ $items->count() }} 个</div>
    <div class="masonry max-w-2xl mx-auto">
        @forelse($items as $item)
            @include('frontend.partials.card', ['item' => $item])
        @empty
            <div class="col-span-full py-20 text-center text-gray-400">
                <div class="text-5xl mb-2">{{ $type['icon'] }}</div>
                <p class="text-sm">还没有{{ $type['label'] }}内容</p>
                <a href="{{ url('/admin/places/create') }}" class="text-emerald-600 text-sm">+ 添加一个</a>
            </div>
        @endforelse
    </div>
</section>
@endsection
