@extends('frontend.layout')

@section('title', $content->title . ' · Marker')

@section('head')
<link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" crossorigin=""/>
{{-- Phase 18.7: Open Graph / 微信分享卡 --}}
<meta property="og:title" content="{{ $content->title }}">
<meta property="og:description" content="{{ \Illuminate\Support\Str::limit(strip_tags($content->summary ?? $content->subtitle ?? ''), 100) }}">
<meta property="og:image" content="{{ url('/content/' . $content->id . '/share-card.png') }}">
<meta property="og:url" content="{{ url('/content/' . $content->id) }}">
<meta property="og:type" content="article">
<meta name="wechat:card_title" content="{{ $content->title }}">
<meta name="wechat:card_description" content="{{ \Illuminate\Support\Str::limit(strip_tags($content->summary ?? ''), 80) }}">
<style>
    .gallery-image {
        width: 100%;
        height: 240px;
        object-fit: cover;
        display: block;
    }
    .video-wrap { position: relative; padding-bottom: 56.25%; height: 0; }
    .video-wrap video { position: absolute; top: 0; left: 0; width: 100%; height: 100%; }
    .gallery-image { width: 100%; height: 240px; object-fit: cover; display: block; }
</style>
@endsection

@section('content')

@php
    $typeMeta = $content->typeMeta();
    $typeIcon = $typeMeta['icon'] ?? 'N°00';
    $typeLabel = $typeMeta['label'] ?? $content->type;
    $typeColor = $typeMeta['color'] ?? '#1A1814';
    $sub = $content->subTable();
@endphp

{{-- HEADER: N° 编号 + 类型 + 标题 --}}
<section class="border-b border-line-2">
    <div class="max-w-6xl mx-auto px-5 sm:px-8 pt-4 pb-2">
        <div class="flex items-center gap-3 font-mono text-[10px] uppercase tracking-[0.2em] text-ink-3">
            <a href="/" class="hover:text-ink transition-colors">← BACK</a>
            <span class="w-px h-3 bg-line-2"></span>
            <span>N°{{ str_pad($content->id, 3, '0', STR_PAD_LEFT) }}</span>
            <span class="w-px h-3 bg-line-2"></span>
            <span style="color: {{ $typeColor }}">{{ $typeIcon }} · {{ $typeLabel }}</span>
        </div>
    </div>
</section>

{{-- HERO: cover + title + meta --}}
@if($cover)
<section class="border-b border-line">
    <div class="max-w-6xl mx-auto px-0 sm:px-8">
        <div class="aspect-[16/9] sm:aspect-[21/9] overflow-hidden bg-ink-3">
            <img src="{{ $cover->url }}" alt="{{ $content->title }}" class="w-full h-full object-cover">
        </div>
    </div>
</section>
@else
{{-- 无封面 fallback：类型色块 + N° 编号 + 类型标签 --}}
<section class="border-b border-line">
    <div class="max-w-6xl mx-auto px-0 sm:px-8">
        <div class="aspect-[16/9] sm:aspect-[21/9] overflow-hidden flex items-center justify-center" style="background: linear-gradient(135deg, {{ $typeColor }} 0%, {{ $typeColor }}cc 100%);">
            <div class="text-center text-paper px-6">
                <div class="font-mono text-xs sm:text-sm uppercase tracking-[0.4em] mb-3 opacity-80">MARKER · {{ $typeIcon }}</div>
                <div class="font-display text-5xl sm:text-8xl font-medium leading-none mb-3">
                    N°{{ str_pad($content->id, 3, '0', STR_PAD_LEFT) }}
                </div>
                <div class="font-mono text-xs sm:text-sm uppercase tracking-[0.3em] opacity-90">{{ $typeLabel }}</div>
            </div>
        </div>
    </div>
</section>
@endif

<section class="border-b border-line">
    <div class="max-w-6xl mx-auto px-5 sm:px-8 py-8 sm:py-12">
        <div class="grid grid-cols-12 gap-6 sm:gap-12">
            <div class="col-span-12 sm:col-span-8">
                @if($content->subtitle)
                    <p class="font-display italic text-xl text-ink-2 mb-3">{{ $content->subtitle }}</p>
                @endif
                <h1 class="font-display font-medium text-3xl sm:text-5xl leading-[1.05] text-ink">
                    {{ $content->title }}
                </h1>
                @if($content->summary)
                    <p class="font-sans text-base text-ink-2 mt-5 leading-relaxed max-w-2xl">{{ $content->summary }}</p>
                @endif
            </div>

            <div class="col-span-12 sm:col-span-4 sm:pt-4 space-y-4">
                @if($content->rating_label)
                    @php $rl = $rating ?? null; @endphp
                    <div>
                        <div class="eyebrow">RATING</div>
                        <div class="font-display text-3xl mt-1" style="color: {{ $rl['color'] ?? '#1A1814' }}">{{ $rl['label'] ?? '' }}</div>
                        @if($vote_count > 0)
                            <div class="font-mono text-[10px] text-ink-3 mt-1">
                                {{ $vote_count }} {{ __('ui.content_vote_count') }} · avg {{ $vote_avg }}
                            </div>
                        @endif
                    </div>
                @endif

                {{-- Phase 17：投票 UI --}}
                <div x-data="{
                    loading: false,
                    label: '{{ $content->rating_label }}',
                    count: {{ $vote_count }},
                    dist: @json(array_values($vote_distribution)),
                    myVote: {{ $user_vote ? (int) $user_vote->rating_value : 'null' }},
                    async vote(value) {
                        if (this.loading) return;
                        this.loading = true;
                        try {
                            const r = await fetch('{{ url('/content/' . $content->id . '/vote') }}', {
                                method: 'POST',
                                headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': document.querySelector('meta[name=csrf-token]').content, 'Accept': 'application/json' },
                                body: JSON.stringify({ value })
                            });
                            if (r.status === 401) { window.location = '/login'; return; }
                            const j = await r.json();
                            if (j.ok) {
                                this.label = j.rating_label;
                                this.count = j.vote_count;
                                this.dist = Object.values(j.vote_distribution);
                                this.myVote = value;
                            }
                        } finally { this.loading = false; }
                    }
                }">
                    <div class="eyebrow mb-2">{{ __('ui.content_section_vote') }}</div>
                    <div class="grid grid-cols-5 gap-1.5">
                        @php
                            $tiers = [
                                1 => ['label_key' => 'ui.rating_terrible', 'color' => '#7f1d1d'],
                                2 => ['label_key' => 'ui.rating_npc',      'color' => '#6b7280'],
                                3 => ['label_key' => 'ui.rating_nice',     'color' => '#0ea5e9'],
                                4 => ['label_key' => 'ui.rating_great',    'color' => '#10b981'],
                                5 => ['label_key' => 'ui.rating_amazing',  'color' => '#dc2626'],
                            ];
                        @endphp
                        @foreach($tiers as $value => $t)
                            <button type="button"
                                    @click="vote({{ $value }})"
                                    :class="myVote === {{ $value }} ? 'border-ink' : 'border-line hover:border-ink-2'"
                                    class="text-center py-1.5 border-2 transition-colors">
                                <div class="font-display text-[11px] sm:text-sm leading-none" style="color: {{ $t['color'] }}">{{ __($t['label_key']) }}</div>
                                <div class="font-mono text-[8px] mt-0.5 text-ink-3" x-text="dist[{{ $value - 1 }}] || 0"></div>
                            </button>
                        @endforeach
                    </div>
                </div>

                @if($content->user)
                    <div>
                        <div class="eyebrow">CURATED BY</div>
                        <div class="font-display text-lg text-ink mt-1">{{ $content->user->name }}</div>
                    </div>
                @endif
                <div>
                    <div class="eyebrow">VIEWS</div>
                    <div class="font-display text-2xl text-ink mt-1">{{ $content->view_count }}</div>
                </div>

                {{-- Phase 18.7: 分享 --}}
                <div x-data="{ copied: false }">
                    <div class="eyebrow mb-1.5">SHARE</div>
                    <div class="flex items-center gap-1.5">
                        <button @click="navigator.clipboard.writeText('{{ url("/content/" . $content->id) }}'); copied = true; setTimeout(() => copied = false, 1500)"
                                class="flex-1 font-mono text-[10px] uppercase tracking-[0.15em] border border-ink px-2 py-1.5 hover:bg-ink hover:text-paper transition-colors flex items-center justify-center gap-1"
                                :class="copied ? 'bg-ink text-paper' : ''">
                            <svg class="w-3 h-3" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M10 13a5 5 0 0 0 7.54.54l3-3a5 5 0 0 0-7.07-7.07l-1.72 1.71M14 11a5 5 0 0 0-7.54-.54l-3 3a5 5 0 0 0 7.07 7.07l1.71-1.71"/></svg>
                            <span x-text="copied ? '已复制' : '链接'"></span>
                        </button>
                        <a href="{{ url('/content/' . $content->id . '/share-card.png') }}" download="marker-{{ $content->id }}.png"
                           class="flex-1 font-mono text-[10px] uppercase tracking-[0.15em] border border-ink px-2 py-1.5 hover:bg-ink hover:text-paper transition-colors text-center flex items-center justify-center gap-1">
                            <svg class="w-3 h-3" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="3" width="18" height="18" rx="0"/><path d="M3 9h18M9 3v18"/></svg>
                            <span>卡</span>
                        </a>
                    </div>
                </div>
                <div>
                    <div class="eyebrow">STATUS</div>
                    <div class="font-mono text-xs text-ink-2 mt-1">
                        @if($content->is_visited) <span class="text-info">已去过</span> @endif
                        @if($content->is_wishlist) <span class="text-warm">种草中</span> @endif
                        @if($content->is_public) <span class="text-success">公开</span> @else <span class="text-ink-3">草稿</span> @endif
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>

{{-- 关联地点 (单/多地点) --}}
@if($content->places->count() > 0)
<section class="border-b border-line">
    <div class="max-w-6xl mx-auto px-5 sm:px-8 py-8 sm:py-12">
        <div class="eyebrow mb-5">§ 02 — {{ $content->isMultiplePlaces() ? '沿途地点' : '地点' }} · {{ $content->places->count() }} STOPS</div>
        <ol class="space-y-4">
            @foreach($content->places as $i => $place)
                <li class="border-b border-line-2 pb-4 flex gap-4">
                    <span class="font-mono text-xs text-ink-3 w-8 flex-shrink-0 pt-1">{{ $content->isMultiplePlaces() ? str_pad($i + 1, 2, '0', STR_PAD_LEFT) : 'N°' . str_pad($place->id, 2, '0', STR_PAD_LEFT) }}</span>
                    <div class="flex-1 min-w-0">
                        <a href="{{ url('/place/' . $place->id) }}" class="font-display text-lg text-ink hover:text-warm transition-colors">
                            {{ $place->name }}
                        </a>
                        @if($place->city || $place->address)
                            <div class="font-mono text-[10px] uppercase tracking-[0.15em] text-ink-3 mt-1">
                                {{ trim($place->city . ($place->address ? ' · ' . $place->address : '')) }}
                            </div>
                        @endif
                        @if($place->pivot->notes)
                            <p class="text-sm text-ink-2 mt-2 italic">{{ $place->pivot->notes }}</p>
                        @endif
                    </div>
                </li>
            @endforeach
        </ol>
    </div>
</section>
@endif

{{-- 类型专属 (sub table) --}}
@if($sub)
<section class="border-b border-line">
    <div class="max-w-6xl mx-auto px-5 sm:px-8 py-8 sm:py-12">
        <div class="eyebrow mb-5">§ 03 — {{ $typeLabel }} · 详细信息</div>
        <div class="grid grid-cols-1 sm:grid-cols-3 gap-x-6 gap-y-4">
            @php
                $fields = match($content->type) {
                    'self_drive' => [
                        ['label' => '距离', 'value' => $sub->distance_km, 'unit' => 'km'],
                        ['label' => '预计时长', 'value' => $sub->duration_minutes, 'unit' => '分钟'],
                        ['label' => '最高海拔', 'value' => $sub->altitude_meters, 'unit' => 'm'],
                        ['label' => '难度', 'value' => ['easy' => '轻松', 'moderate' => '中等', 'hard' => '困难'][$sub->difficulty ?? ''] ?? $sub->difficulty],
                        ['label' => '路况', 'value' => ['paved' => '全程铺装', 'mostly_paved' => '大部分铺装', 'mixed' => '混合', 'offroad' => '越野'][$sub->road_condition ?? ''] ?? $sub->road_condition],
                        ['label' => '最佳季节', 'value' => is_array($sub->best_season) ? implode(' / ', array_map(fn($s) => ['spring' => '春', 'summer' => '夏', 'autumn' => '秋', 'winter' => '冬'][$s] ?? $s, $sub->best_season)) : null],
                        ['label' => '加油站', 'value' => is_array($sub->gas_stations) ? count($sub->gas_stations) . ' 处' : null],
                        ['label' => '两步路', 'value' => $sub->two_foot_route_id, 'mono' => true],
                    ],
                    'hiking' => [
                        ['label' => '距离', 'value' => $sub->distance_km, 'unit' => 'km'],
                        ['label' => '预计时长', 'value' => $sub->duration_minutes, 'unit' => '分钟'],
                        ['label' => '最高海拔', 'value' => $sub->altitude_meters, 'unit' => 'm'],
                        ['label' => '累计爬升', 'value' => $sub->elevation_gain, 'unit' => 'm'],
                        ['label' => '难度', 'value' => ['easy' => '轻松', 'moderate' => '中等', 'hard' => '困难', 'expert' => '专业'][$sub->difficulty ?? ''] ?? $sub->difficulty],
                        ['label' => '线路类型', 'value' => ['loop' => '环形', 'out_back' => '往返', 'one_way' => '单程'][$sub->route_type ?? ''] ?? $sub->route_type],
                        ['label' => '最佳季节', 'value' => is_array($sub->best_season) ? implode(' / ', array_map(fn($s) => ['spring' => '春', 'summer' => '夏', 'autumn' => '秋', 'winter' => '冬'][$s] ?? $s, $sub->best_season)) : null],
                        ['label' => '两步路', 'value' => $sub->two_foot_route_id, 'mono' => true],
                    ],
                    'play_water' => [
                        ['label' => '水域', 'value' => ['lake' => '湖', 'river' => '河', 'sea' => '海', 'pool' => '潭', 'reservoir' => '水库'][$sub->water_type ?? ''] ?? $sub->water_type],
                        ['label' => '水深', 'value' => $sub->water_depth],
                        ['label' => '可游泳', 'value' => $sub->is_swimmable ? '✓' : '—'],
                        ['label' => '免费', 'value' => $sub->is_free ? '✓' : '—'],
                        ['label' => '门票', 'value' => $sub->ticket],
                        ['label' => '有救生员', 'value' => $sub->has_lifeguard ? '✓' : '—'],
                        ['label' => '停车', 'value' => ['free' => '免费', 'paid' => '收费', 'limited' => '有限', 'no' => '无'][$sub->parking ?? ''] ?? $sub->parking],
                    ],
                    'paddle' => [
                        ['label' => '水深', 'value' => $sub->water_depth],
                        ['label' => '水流情况', 'value' => ['calm' => '平静', 'mild' => '缓流', 'moderate' => '中流', 'strong' => '急流'][$sub->water_current ?? ''] ?? $sub->water_current],
                        ['label' => '难度', 'value' => ['easy' => '轻松', 'moderate' => '中等', 'hard' => '困难'][$sub->difficulty ?? ''] ?? $sub->difficulty],
                        ['label' => '装备租赁', 'value' => $sub->rental_available ? '✓' : '—'],
                        ['label' => '最佳时间', 'value' => $sub->best_time],
                    ],
                    'photo' => [
                        ['label' => '最佳时间', 'value' => $sub->best_time],
                        ['label' => '最佳光影', 'value' => $sub->best_light],
                        ['label' => '机位数量', 'value' => $sub->viewpoint_count],
                        ['label' => '可飞无人机', 'value' => $sub->is_drone_allowed ? '✓' : '—'],
                        ['label' => '需要许可', 'value' => $sub->permit_required ? '✓' : '—'],
                        ['label' => '停车', 'value' => ['free' => '免费', 'paid' => '收费', 'limited' => '有限', 'no' => '无'][$sub->parking ?? ''] ?? $sub->parking],
                    ],
                    'food' => [
                        ['label' => '人均', 'value' => $sub->price_per_person, 'unit' => '元'],
                        ['label' => '菜系', 'value' => $sub->cuisine_type],
                        ['label' => '营业时间', 'value' => $sub->business_hours],
                        ['label' => '招牌菜', 'value' => is_array($sub->signature_dishes) ? implode(' / ', $sub->signature_dishes) : null],
                        ['label' => '预订方式', 'value' => $sub->reservation],
                        ['label' => '联系方式', 'value' => $sub->contact],
                    ],
                    'camping' => [
                        ['label' => '海拔', 'value' => $sub->altitude_meters, 'unit' => 'm'],
                        ['label' => '免费', 'value' => $sub->is_free ? '✓' : '—'],
                        ['label' => '有水源', 'value' => $sub->has_water ? '✓' : '—'],
                        ['label' => '有厕所', 'value' => $sub->has_toilet ? '✓' : '—'],
                        ['label' => '可明火', 'value' => $sub->fire_allowed ? '✓' : '—'],
                        ['label' => '有信号', 'value' => $sub->has_signal ? '✓' : '—'],
                        ['label' => '停车', 'value' => ['free' => '免费', 'paid' => '收费', 'limited' => '有限', 'no' => '无'][$sub->parking ?? ''] ?? $sub->parking],
                    ],
                    'sunrise_sunset' => [
                        ['label' => '方位', 'value' => ['east' => '东 (日出)', 'west' => '西 (日落)', 'both' => '都能看'][$sub->direction ?? ''] ?? $sub->direction],
                        ['label' => '最佳时间', 'value' => $sub->best_time],
                        ['label' => '机位数量', 'value' => $sub->viewpoint_count],
                        ['label' => '抵达难度', 'value' => ['easy' => '轻松', 'moderate' => '中等', 'hard' => '困难'][$sub->difficulty ?? ''] ?? $sub->difficulty],
                        ['label' => '可飞无人机', 'value' => $sub->is_drone_allowed ? '✓' : '—'],
                    ],
                    default => [],
                };
            @endphp
            @foreach($fields as $f)
                @if(!empty($f['value']) && $f['value'] !== '—')
                <div>
                    <div class="eyebrow">{{ $f['label'] }}</div>
                    <div class="font-display text-base text-ink mt-1 {{ !empty($f['mono']) ? 'font-mono' : '' }}">
                        {{ $f['value'] }}{{ !empty($f['unit']) ? ' <span class="text-ink-3 text-sm">' . $f['unit'] . '</span>' : '' }}
                    </div>
                </div>
                @endif
            @endforeach
        </div>

        @if($sub->description ?? null)
            <div class="prose prose-sm max-w-none mt-6 text-ink-2">{!! $sub->description !!}</div>
        @endif

        @if($content->type === 'self_drive' && is_array($sub->waypoints) && count($sub->waypoints) > 0)
            <div class="mt-6">
                <div class="eyebrow mb-3">途经点</div>
                <ol class="space-y-1">
                    @foreach($sub->waypoints as $wp)
                        <li class="text-sm text-ink-2 font-mono">{{ $wp }}</li>
                    @endforeach
                </ol>
            </div>
        @endif

        @if($content->type === 'hiking' && is_array($sub->waypoints) && count($sub->waypoints) > 0)
            <div class="mt-6">
                <div class="eyebrow mb-3">途经点</div>
                <ol class="space-y-1">
                    @foreach($sub->waypoints as $wp)
                        <li class="text-sm text-ink-2 font-mono">{{ $wp }}</li>
                    @endforeach
                </ol>
            </div>
        @endif

        @if(is_array($sub->gear_checklist) && count($sub->gear_checklist) > 0)
            <div class="mt-6">
                <div class="eyebrow mb-3">装备清单</div>
                <div class="flex flex-wrap gap-2">
                    @foreach($sub->gear_checklist as $g)
                        <span class="text-xs font-mono border border-line-2 px-2 py-1">{{ $g }}</span>
                    @endforeach
                </div>
            </div>
        @endif

        @if(is_array($sub->safety_notes) && count($sub->safety_notes) > 0)
            <div class="mt-6">
                <div class="eyebrow mb-3">安全提示</div>
                <ul class="space-y-1">
                    @foreach($sub->safety_notes as $s)
                        <li class="text-sm text-ink-2">· {{ $s }}</li>
                    @endforeach
                </ul>
            </div>
        @endif
    </div>
</section>
@endif

{{-- 详情描述 --}}
@if($content->description)
<section class="border-b border-line">
    <div class="max-w-6xl mx-auto px-5 sm:px-8 py-8 sm:py-12">
        <div class="eyebrow mb-5">§ 04 — 详细描述</div>
        <div class="prose prose-sm max-w-none text-ink-2">{!! $content->description !!}</div>
    </div>
</section>
@endif

{{-- 相册 + 视频 --}}
@if($gallery->count() > 0)
<section class="border-b border-line">
    <div class="max-w-6xl mx-auto px-5 sm:px-8 py-8 sm:py-12">
        <div class="eyebrow mb-5">§ 05 — 相册 · {{ $gallery->count() }} PHOTOS</div>
        <div class="grid grid-cols-2 sm:grid-cols-3 gap-3">
            @foreach($gallery as $img)
                <a href="{{ $img->url }}" target="_blank" class="block aspect-square overflow-hidden border border-line-2">
                    <img src="{{ $img->thumbnail_url ?? $img->url }}" alt="{{ $img->pivot->caption ?? '' }}" class="gallery-image">
                </a>
            @endforeach
        </div>
    </div>
</section>
@endif

@if($videos->count() > 0)
<section class="border-b border-line">
    <div class="max-w-6xl mx-auto px-5 sm:px-8 py-8 sm:py-12">
        <div class="eyebrow mb-5">§ 06 — 视频集 · {{ $videos->count() }} VIDEOS</div>
        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
            @foreach($videos as $v)
                <div class="border border-line-2">
                    @if(str_starts_with($v->path, 'http'))
                        <div class="video-wrap">
                            <video src="{{ $v->path }}" controls></video>
                        </div>
                    @else
                        <div class="aspect-video bg-ink-3 flex items-center justify-center font-mono text-xs text-paper-2">{{ $v->path }}</div>
                    @endif
                    @if($v->pivot->caption)
                        <div class="px-3 py-2 text-sm text-ink-2">{{ $v->pivot->caption }}</div>
                    @endif
                </div>
            @endforeach
        </div>
    </div>
</section>
@endif

{{-- 关联活动 --}}
@if($activities->count() > 0)
<section class="border-b border-line">
    <div class="max-w-6xl mx-auto px-5 sm:px-8 py-8 sm:py-12">
        <div class="eyebrow mb-5">§ 07 — 关联约伴 · {{ $activities->count() }}</div>
        <div class="space-y-3">
            @foreach($activities as $act)
                <a href="{{ url('/activities/' . $act->id) }}" class="block border border-line p-4 hover:border-ink transition-colors">
                    <div class="flex items-baseline justify-between">
                        <h3 class="font-display text-lg text-ink">{{ $act->title }}</h3>
                        <span class="font-mono text-[10px] text-ink-3">{{ $act->start_at?->format('Y-m-d H:i') }}</span>
                    </div>
                    <div class="font-mono text-[10px] text-ink-3 mt-1">
                        {{ $act->meeting_point ?? '—' }} · {{ $act->joined_count }}/{{ $act->max_participants ?: '∞' }} · {{ \App\Models\Activity::STATUSES[$act->status] ?? $act->status }}
                    </div>
                </a>
            @endforeach
        </div>
    </div>
</section>
@endif

{{-- 评论 --}}
<section class="border-b border-line">
    <div class="max-w-6xl mx-auto px-5 sm:px-8 py-8 sm:py-12">
        <div class="eyebrow mb-5">§ 08 — 评论 · {{ $content->publicComments->count() }}</div>

        @auth
            <form method="POST" action="{{ url('/content/' . $content->id . '/comments') }}" enctype="multipart/form-data" class="mb-8 border border-line p-4" x-data="{ imageCount: 0, videoCount: 0 }">
                @csrf
                <textarea name="body" required rows="3" placeholder="{{ __('ui.comment_placeholder') }}" class="w-full bg-transparent border-b border-line-2 focus:border-ink outline-none text-sm resize-none mb-3"></textarea>

                {{-- Phase 17：图片/视频上传 --}}
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-3 mb-3">
                    <div>
                        <label class="font-mono text-[10px] uppercase tracking-[0.15em] text-ink-3 block mb-1.5">
                            {{ __('ui.comment_add_image') }} · <span x-text="imageCount"></span>/9
                        </label>
                        <input type="file" name="images[]" multiple accept="image/*"
                               @change="imageCount = $event.target.files.length"
                               class="block w-full text-xs font-mono text-ink-2 file:mr-2 file:py-1 file:px-3 file:border-0 file:bg-ink file:text-paper file:font-mono file:text-[10px] file:uppercase file:tracking-[0.15em] hover:file:bg-warm">
                    </div>
                    <div>
                        <label class="font-mono text-[10px] uppercase tracking-[0.15em] text-ink-3 block mb-1.5">
                            {{ __('ui.comment_add_video') }} · <span x-text="videoCount"></span>/3
                        </label>
                        <input type="file" name="videos[]" multiple accept="video/*"
                               @change="videoCount = $event.target.files.length"
                               class="block w-full text-xs font-mono text-ink-2 file:mr-2 file:py-1 file:px-3 file:border-0 file:bg-ink file:text-paper file:font-mono file:text-[10px] file:uppercase file:tracking-[0.15em] hover:file:bg-warm">
                    </div>
                </div>

                <div class="flex items-center justify-between">
                    <select name="rating_label" class="text-xs border border-line-2 px-2 py-1 bg-transparent">
                        <option value="">{{ __('ui.optional') }} · 评分</option>
                        <option value="terrible">{{ __('ui.rating_terrible') }}</option>
                        <option value="npc">{{ __('ui.rating_npc') }}</option>
                        <option value="nice">{{ __('ui.rating_nice') }}</option>
                        <option value="great">{{ __('ui.rating_great') }}</option>
                        <option value="amazing">{{ __('ui.rating_amazing') }}</option>
                    </select>
                    <button type="submit" class="font-mono text-xs uppercase tracking-[0.15em] px-4 py-2 bg-ink text-paper hover:bg-warm transition-colors">{{ __('ui.comment_post') }}</button>
                </div>
            </form>
        @else
            <p class="text-sm text-ink-3 mb-8"><a href="{{ url('/login') }}" class="underline">{{ __('ui.login') }}</a> 后可以发表评论</p>
        @endauth

        <div class="space-y-5">
            @forelse($content->publicComments as $comment)
                <div class="border-b border-line-2 pb-4">
                    <div class="flex items-baseline gap-3 mb-2">
                        <span class="font-display text-sm text-ink">{{ $comment->user?->name ?? __('ui.comment_anonymous') }}</span>
                        <span class="font-mono text-[10px] text-ink-3">{{ $comment->created_at?->format('Y-m-d H:i') }}</span>
                        @if($comment->rating_label)
                            @php $crl = \App\Models\Content::RATING_LABELS[$comment->rating_label] ?? null; @endphp
                            @if($crl)
                                <span class="font-mono text-[10px] uppercase tracking-[0.15em]" style="color: {{ $crl['color'] }}">· {{ $crl['label'] }}</span>
                            @endif
                        @endif
                    </div>
                    <p class="text-sm text-ink-2 whitespace-pre-line">{{ $comment->body }}</p>

                    {{-- 评论图片 (3-col grid) --}}
                    @if($comment->images->isNotEmpty())
                        <div class="mt-3 grid grid-cols-3 gap-1.5 max-w-md">
                            @foreach($comment->images as $img)
                                <a href="{{ $img->url }}" target="_blank" class="block aspect-square overflow-hidden border border-line-2">
                                    <img src="{{ $img->url }}" alt="" loading="lazy" class="w-full h-full object-cover hover:scale-105 transition-transform">
                                </a>
                            @endforeach
                        </div>
                    @endif
                    {{-- 评论视频 --}}
                    @if($comment->videos->isNotEmpty())
                        <div class="mt-3 space-y-2 max-w-md">
                            @foreach($comment->videos as $vid)
                                <div class="video-wrap border border-line-2">
                                    <video src="{{ $vid->url }}" controls preload="metadata" class="w-full"></video>
                                </div>
                            @endforeach
                        </div>
                    @endif
                </div>
            @empty
                <p class="text-sm text-ink-3 italic">{{ __('ui.comment_empty') }}</p>
            @endforelse
        </div>
    </div>
</section>

{{-- 关联笔记 (Phase 19) --}}
@if($content->notes->isNotEmpty())
<section class="border-b border-line">
    <div class="max-w-6xl mx-auto px-5 sm:px-8 py-8 sm:py-12">
        <div class="eyebrow mb-5">§ 09 — 关联笔记 · {{ $content->notes->count() }} NOTES</div>
        <p class="text-sm text-ink-3 mb-6">从外部平台收录的参考笔记（小红书 / 大众点评 / 马蜂窝 等）。</p>

        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-4">
            @foreach($content->notes as $note)
                <article class="border border-line p-4 hover:border-ink transition-colors flex flex-col">
                    {{-- 封面 --}}
                    <div class="aspect-[4/3] overflow-hidden border border-line-2 mb-3 bg-ink-3/10">
                        @php
                            $coverSrc = $note->coverMedia?->url ?? $note->cover_url;
                        @endphp
                        @if($coverSrc)
                            <img src="{{ $coverSrc }}" alt="" loading="lazy" class="w-full h-full object-cover" referrerpolicy="no-referrer">
                        @else
                            <div class="w-full h-full flex items-center justify-center font-mono text-[10px] uppercase tracking-[0.15em] text-ink-3">
                                {{ $note->source === 'xiaohongshu' ? 'RED NOTE' : ($note->source === 'dianping' ? 'DIANPING' : ($note->source === 'mafengwo' ? 'MAFENGWO' : 'NOTE')) }}
                            </div>
                        @endif
                    </div>

                    {{-- 来源 + 角色 --}}
                    <div class="flex items-center justify-between mb-2">
                        <span class="font-mono text-[10px] uppercase tracking-[0.15em] text-ink-3">
                            @switch($note->source)
                                @case('xiaohongshu') 小红书 @break
                                @case('dianping') 大众点评 @break
                                @case('mafengwo') 马蜂窝 @break
                                @default 手动
                            @endswitch
                        </span>
                        @php
                            $roleMap = ['reference' => '参考', 'inspiration' => '灵感', 'detailed' => '详情'];
                            $role = $note->pivot->role ?? 'reference';
                        @endphp
                        <span class="font-mono text-[10px] uppercase tracking-[0.15em] text-warm">{{ $roleMap[$role] ?? '参考' }}</span>
                    </div>

                    {{-- 标题 --}}
                    <h3 class="font-display text-base text-ink leading-tight mb-2 line-clamp-2">
                        @if($note->source_url)
                            <a href="{{ $note->source_url }}" target="_blank" rel="noopener" class="hover:underline">{{ $note->title }}</a>
                        @else
                            {{ $note->title }}
                        @endif
                    </h3>

                    {{-- 作者 + 时间 --}}
                    <div class="flex items-baseline justify-between text-xs text-ink-3 mt-auto">
                        @if($note->author)<span class="font-mono">@ {{ $note->author }}</span>@else<span></span>@endif
                        @if($note->published_at)<span class="font-mono text-[10px]">{{ $note->published_at->format('Y-m-d') }}</span>@endif
                    </div>

                    {{-- 摘录 --}}
                    @if($note->content)
                        <p class="text-xs text-ink-2 mt-2 line-clamp-3 whitespace-pre-line">{{ \Illuminate\Support\Str::limit(strip_tags($note->content), 120) }}</p>
                    @endif
                </article>
            @endforeach
        </div>
    </div>
</section>
@endif

@endsection
