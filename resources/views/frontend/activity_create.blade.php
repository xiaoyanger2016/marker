@extends('frontend.layout')

@section('title', '发起约伴 · Marker')

@section('content')
<section class="px-4 py-4 bg-gradient-to-br from-rose-500 via-pink-500 to-orange-500 text-white">
    <div class="max-w-2xl mx-auto">
        <a href="/activities" class="text-xs text-white/80">← 返回活动</a>
        <h1 class="text-xl font-bold mt-2">🎒 发起约伴</h1>
        <p class="text-sm text-white/80 mt-1">填好时间地点，发起人来定，感兴趣的人一键跟随</p>
    </div>
</section>

{{-- 关联内容 --}}
@if($place || $route)
<div class="max-w-2xl mx-auto px-4 mt-3">
    <div class="bg-white rounded-xl border border-rose-200 p-3 text-sm">
        <div class="text-xs text-rose-500 mb-1">🔗 关联内容（自动填入）</div>
        @if($place)
            <div class="font-medium text-gray-900">📍 {{ $place->name }}</div>
            <input type="hidden" name="place_id" value="{{ $place->id }}">
        @endif
        @if($route)
            <div class="font-medium text-gray-900">🛣️ {{ $route->name }}</div>
            <input type="hidden" name="route_id" value="{{ $route->id }}">
        @endif
    </div>
</div>
@endif

<form method="POST" action="/activities" class="max-w-2xl mx-auto px-4 py-4 space-y-3">
    @csrf
    @if($place)<input type="hidden" name="place_id" value="{{ $place->id }}">@endif
    @if($route)<input type="hidden" name="route_id" value="{{ $route->id }}">@endif

    @if($errors->any())
        <div class="p-3 bg-rose-50 border border-rose-200 text-rose-700 text-sm rounded-lg">{{ $errors->first() }}</div>
    @endif

    <div class="bg-white rounded-2xl shadow-sm p-4 space-y-3">
        <div>
            <label class="text-xs text-gray-600 mb-1 block">活动标题 <span class="text-rose-500">*</span></label>
            <input name="title" type="text" required maxlength="200" value="{{ old('title') }}"
                   placeholder="例如：国庆去千岛湖骑行"
                   class="w-full px-3 py-2.5 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-rose-500 focus:border-rose-500 outline-none">
        </div>

        <div>
            <label class="text-xs text-gray-600 mb-1 block">出发时间 <span class="text-rose-500">*</span></label>
            <input name="start_at" type="datetime-local" required value="{{ old('start_at') }}"
                   class="w-full px-3 py-2.5 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-rose-500 focus:border-rose-500 outline-none">
        </div>

        <div class="grid grid-cols-2 gap-2">
            <div>
                <label class="text-xs text-gray-600 mb-1 block">结束时间</label>
                <input name="end_at" type="datetime-local" value="{{ old('end_at') }}"
                       class="w-full px-3 py-2.5 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-rose-500 focus:border-rose-500 outline-none">
            </div>
            <div>
                <label class="text-xs text-gray-600 mb-1 block">报名截止</label>
                <input name="signup_deadline" type="datetime-local" value="{{ old('signup_deadline') }}"
                       class="w-full px-3 py-2.5 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-rose-500 focus:border-rose-500 outline-none">
            </div>
        </div>

        <div>
            <label class="text-xs text-gray-600 mb-1 block">集合地点</label>
            <input name="meeting_point" type="text" maxlength="200" value="{{ old('meeting_point') }}"
                   placeholder="如：杭州西广场"
                   class="w-full px-3 py-2.5 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-rose-500 focus:border-rose-500 outline-none">
        </div>
    </div>

    <div class="bg-white rounded-2xl shadow-sm p-4 space-y-3">
        <h3 class="text-sm font-bold text-gray-900">🚗 出行方式 & 人数</h3>

        <div class="grid grid-cols-2 gap-2">
            <div>
                <label class="text-xs text-gray-600 mb-1 block">出行方式</label>
                <select name="transport" class="w-full px-3 py-2.5 border border-gray-300 rounded-lg text-sm bg-white">
                    <option value="">不限</option>
                    <option value="自驾" {{ old('transport') == '自驾' ? 'selected' : '' }}>自驾</option>
                    <option value="拼车" {{ old('transport') == '拼车' ? 'selected' : '' }}>拼车</option>
                    <option value="包车" {{ old('transport') == '包车' ? 'selected' : '' }}>包车</option>
                    <option value="徒步" {{ old('transport') == '徒步' ? 'selected' : '' }}>徒步</option>
                    <option value="骑行" {{ old('transport') == '骑行' ? 'selected' : '' }}>骑行</option>
                    <option value="公共交通" {{ old('transport') == '公共交通' ? 'selected' : '' }}>公共交通</option>
                </select>
            </div>
            <div>
                <label class="text-xs text-gray-600 mb-1 block">人数上限（0=不限）</label>
                <input name="max_participants" type="number" min="0" max="100" value="{{ old('max_participants', 6) }}"
                       class="w-full px-3 py-2.5 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-rose-500 focus:border-rose-500 outline-none">
            </div>
        </div>
    </div>

    <div class="bg-white rounded-2xl shadow-sm p-4 space-y-3">
        <h3 class="text-sm font-bold text-gray-900">💰 费用</h3>

        <div>
            <label class="text-xs text-gray-600 mb-1 block">人均费用（¥）</label>
            <input name="fee" type="number" min="0" step="1" value="{{ old('fee', 0) }}"
                   class="w-full px-3 py-2.5 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-rose-500 focus:border-rose-500 outline-none">
        </div>

        <div>
            <label class="text-xs text-gray-600 mb-1 block">费用包含</label>
            <input name="fee_includes" type="text" maxlength="500" value="{{ old('fee_includes') }}"
                   placeholder="如：住宿 2 晚 + 早餐"
                   class="w-full px-3 py-2.5 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-rose-500 focus:border-rose-500 outline-none">
        </div>

        <div>
            <label class="text-xs text-gray-600 mb-1 block">费用不含</label>
            <input name="fee_excludes" type="text" maxlength="500" value="{{ old('fee_excludes') }}"
                   placeholder="如：正餐、油费、过路费"
                   class="w-full px-3 py-2.5 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-rose-500 focus:border-rose-500 outline-none">
        </div>
    </div>

    <div class="bg-white rounded-2xl shadow-sm p-4 space-y-3">
        <h3 class="text-sm font-bold text-gray-900">📝 活动详情</h3>
        <textarea name="description" rows="4" maxlength="5000" placeholder="行程安排、装备建议、注意事项..."
                  class="w-full px-3 py-2.5 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-rose-500 focus:border-rose-500 outline-none resize-none">{{ old('description') }}</textarea>
    </div>

    <button type="submit" class="w-full py-3 bg-rose-500 hover:bg-rose-600 text-white font-bold rounded-2xl text-sm">
        🎒 发布活动
    </button>
</form>
@endsection
