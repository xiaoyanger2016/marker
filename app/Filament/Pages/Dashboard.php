<?php

namespace App\Filament\Pages;

use App\Models\Activity;
use App\Models\Content;
use App\Models\Place;
use App\Models\Region;
use App\Models\User;
use Filament\Pages\Page;

class Dashboard extends Page
{
    protected static ?string $navigationIcon = 'heroicon-o-home';

    protected static ?string $navigationLabel = 'Dashboard';

    protected static string $view = 'filament.pages.dashboard';

    // 隐藏 Filament 默认大标题 — 我们用 view 里的 Inbox 风格自定义 header
    public function getHeading(): string
    {
        return '';
    }

    public function getSubheading(): ?string
    {
        return null;
    }

    // 隐藏默认 page header 的 eyebrow / breadcrumb
    protected function getHeaderActions(): array
    {
        return [];
    }

    public function getViewData(): array
    {
        // 8 大类分别统计
        $byType = [];
        foreach (Content::TYPES as $key => $meta) {
            $byType[$key] = Content::where('type', $key)->count();
        }

        return [
            'stats' => [
                // 主 metric cards
                'contents'           => Content::count(),
                'contents_today'     => Content::where('created_at', '>=', now()->subDay())->count(),
                'contents_public'    => Content::where('is_public', true)->count(),
                'contents_visited'   => Content::where('is_visited', true)->count(),
                'contents_wishlist'  => Content::where('is_wishlist', true)->count(),
                'places'             => Place::count(),
                'activities'         => Activity::count(),
                'activities_open'    => Activity::where('status', 'open')->count(),
                'activities_full'    => Activity::where('status', 'full')->count(),
                'users'              => User::count(),
                'users_active_7d'    => User::where('created_at', '>=', now()->subDays(7))->count(),

                // sidebar 快捷入口计数
                'regions'            => Region::count(),

                // 8 大类分布 (按 N°01-08 顺序)
                'by_type'            => $byType,
            ],
            'recent_contents' => Content::orderBy('created_at', 'desc')->limit(8)->get(['id', 'title', 'type', 'created_at', 'is_public']),
        ];
    }
}
