@extends('frontend.layout')

@section('title', '发起约伴 · Marker')

@section('content')

<section class="border-b border-line-2">
    <div class="max-w-6xl mx-auto px-5 sm:px-8 pt-4 pb-2">
        <div class="flex items-center gap-3 font-mono text-[10px] uppercase tracking-[0.2em] text-ink-3">
            <a href="/activities" class="hover:text-ink transition-colors">← BACK</a>
            <span class="w-px h-3 bg-line-2"></span>
            <span>N°01 · NEW EVENT</span>
        </div>
    </div>
</section>

<section class="border-b border-line">
    <div class="max-w-6xl mx-auto px-5 sm:px-8 py-6 sm:py-14">
        <div class="grid grid-cols-12 gap-6 sm:gap-12">
            <div class="col-span-12 sm:col-span-7">
                <h1 class="font-display font-medium text-3xl sm:text-6xl leading-[1.0] text-ink">
                    发起<br>
                    <span class="serif-italic text-warm">约伴</span>
                </h1>
                <p class="font-display italic text-base sm:text-xl text-ink-2 mt-4">填好时间地点，剩下的交给感兴趣的人。</p>

                @if($place || $route)
                    <div class="mt-6 p-4 border-l-2 border-warm bg-paper-2 max-w-md">
                        <div class="eyebrow mb-1">LINKED</div>
                        @if($place)
                            <div class="font-display text-base text-ink">PLACE: {{ $place->name }}</div>
                        @endif
                        @if($route)
                            <div class="font-display text-base text-ink">ROUTE: {{ $route->name }}</div>
                        @endif
                    </div>
                @endif
            </div>

            <div class="col-span-12 sm:col-span-5 sm:pt-12">
                <p class="text-sm text-ink-2 border-l border-line-2 pl-4 leading-relaxed">
                    填好标题、时间、地点，剩下的——人数、费用、装备——<br>
                    <span class="font-display italic">能简则简</span>。能说清楚的都写进「详情」里。
                </p>
            </div>
        </div>
    </div>
</section>

<form method="POST" action="/activities" class="max-w-3xl mx-auto px-5 sm:px-8 py-8 sm:py-12 space-y-10">
    @csrf
    @if($place)<input type="hidden" name="place_id" value="{{ $place->id }}">@endif
    @if($route)<input type="hidden" name="route_id" value="{{ $route->id }}">@endif

    @if($errors->any())
        <div class="p-4 border-l-2 border-blood bg-paper-2">
            <div class="eyebrow text-blood mb-1">ERROR</div>
            <p class="text-sm text-ink">{{ $errors->first() }}</p>
        </div>
    @endif

    {{-- § 01 标题 --}}
    <section>
        <div class="eyebrow mb-4">§ 01 · 标题</div>
        <input name="title" type="text" required maxlength="200" value="{{ old('title') }}"
               placeholder="例如：国庆千岛湖骑行"
               class="input font-display text-2xl sm:text-3xl">
    </section>

    {{-- § 02 时间 --}}
    <section>
        <div class="eyebrow mb-4">§ 02 · 时间</div>
        <div class="grid grid-cols-1 sm:grid-cols-2 gap-6">
            <div>
                <label class="label">出发 / START</label>
                <input name="start_at" type="datetime-local" required
                       min="{{ now()->format('Y-m-d\TH:i') }}"
                       value="{{ old('start_at') }}"
                       class="input">
            </div>
            <div>
                <label class="label">截止报名 / DEADLINE</label>
                <input name="signup_deadline" type="datetime-local" value="{{ old('signup_deadline') }}"
                       class="input">
            </div>
        </div>
    </section>

    {{-- § 03 地点 --}}
    <section>
        <div class="eyebrow mb-4">§ 03 · 地点</div>
        <div class="space-y-6">
            <div>
                <label class="label">集合地点 / GATHERING</label>
                <input name="meeting_point" type="text" maxlength="200" value="{{ old('meeting_point') }}"
                       placeholder="如：杭州西广场星巴克门口"
                       class="input">
            </div>
            <div>
                <label class="label">所属城市 / CITY</label>
                <input name="region_name" type="text" maxlength="50" value="{{ old('region_name') }}"
                       placeholder="如：杭州"
                       class="input">
                <input name="region_code" type="hidden" value="{{ old('region_code') }}">
            </div>
        </div>
    </section>

    {{-- § 04 出行 --}}
    <section>
        <div class="eyebrow mb-4">§ 04 · 出行</div>
        <div class="grid grid-cols-1 sm:grid-cols-2 gap-6">
            <div>
                <label class="label">方式 / TRANSIT</label>
                <select name="transport" class="input">
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
                <label class="label">人数上限 / MAX PAX (0 = 不限)</label>
                <input name="max_participants" type="number" min="0" max="100" value="{{ old('max_participants', 6) }}" class="input">
            </div>
        </div>
    </section>

    {{-- § 05 费用 --}}
    <section>
        <div class="eyebrow mb-4">§ 05 · 费用</div>
        <div class="space-y-6">
            <div>
                <label class="label">人均费用 (¥) / FEE</label>
                <input name="fee" type="number" min="0" step="1" value="{{ old('fee', 0) }}" class="input">
            </div>
            <div>
                <label class="label">费用包含 / INCLUDES</label>
                <input name="fee_includes" type="text" maxlength="500" value="{{ old('fee_includes') }}"
                       placeholder="如：住宿 2 晚 + 早餐"
                       class="input">
            </div>
            <div>
                <label class="label">费用不含 / EXCLUDES</label>
                <input name="fee_excludes" type="text" maxlength="500" value="{{ old('fee_excludes') }}"
                       placeholder="如：正餐、油费、过路费"
                       class="input">
            </div>
        </div>
    </section>

    {{-- § 06 详情 --}}
    <section>
        <div class="eyebrow mb-4">§ 06 · 详情</div>
        <textarea name="description" rows="6" maxlength="5000"
                  placeholder="行程安排、装备建议、注意事项、紧急联系人……想写什么写什么。"
                  class="input resize-none leading-relaxed">{{ old('description') }}</textarea>
    </section>

    <div class="pt-6 border-t border-line-2 flex items-center justify-between">
        <a href="/activities" class="btn btn-ghost">取消</a>
        <button type="submit" class="btn btn-primary">
            发布活动
            <span class="font-mono text-[10px] opacity-70">→</span>
        </button>
    </div>
</form>
@endsection
