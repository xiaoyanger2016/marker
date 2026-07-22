<?php

namespace App\Filament\Pages;

use App\Models\Activity;
use App\Models\Place;
use App\Models\Route;
use Filament\Pages\Page;
use Illuminate\Support\Facades\DB;

class Dashboard extends Page
{
    protected static ?string $navigationIcon = 'heroicon-o-home';

    protected static ?string $navigationLabel = 'Dashboard';

    protected static string $view = 'filament.pages.dashboard';

    public function getViewData(): array
    {
        return [
            'stats' => [
                'places' => Place::count(),
                'places_today' => Place::where('created_at', '>=', now()->subDay())->count(),
                'routes' => Route::count(),
                'routes_today' => Route::where('created_at', '>=', now()->subDay())->count(),
                'activities' => Activity::count(),
                'activities_open' => Activity::where('status', 'open')->count(),
                'users' => DB::table('users')->count(),
            ],
            'recent_places' => Place::orderBy('created_at', 'desc')->limit(6)->get(['id', 'name', 'city', 'place_type', 'created_at']),
        ];
    }
}
