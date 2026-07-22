<?php

namespace App\Filament\Pages;

use App\Models\Activity;
use App\Models\Category;
use App\Models\Place;
use App\Models\Region;
use App\Models\Route;
use App\Models\User;
use Filament\Pages\Page;
use Illuminate\Support\Facades\DB;

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
        return [
            'stats' => [
                // 主 metric cards
                'places'             => Place::count(),
                'places_today'       => Place::where('created_at', '>=', now()->subDay())->count(),
                'places_visited'     => Place::where('is_visited', true)->count(),
                'places_wishlist'    => Place::where('is_wishlist', true)->count(),
                'routes'             => Route::count(),
                'routes_self_drive'  => Route::where('type', 'self_drive')->count(),
                'routes_hiking'      => Route::where('type', 'hiking')->count(),
                'activities'         => Activity::count(),
                'activities_open'    => Activity::where('status', 'open')->count(),
                'activities_full'    => Activity::where('status', 'full')->count(),
                'users'              => User::count(),
                'users_active_7d'    => User::where('created_at', '>=', now()->subDays(7))->count(),

                // sidebar 快捷入口计数
                'categories'         => Category::count(),
                'regions'            => Region::count(),
            ],
            'recent_places' => Place::orderBy('created_at', 'desc')->limit(8)->get(['id', 'name', 'city', 'place_type', 'created_at']),
        ];
    }
}

