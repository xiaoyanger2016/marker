<?php

namespace App\Filament\Resources\PlaceResource\Pages;

use App\Filament\Resources\PlaceResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;
use Filament\Resources\Pages\ListRecords\Tab;

class ListPlaces extends ListRecords
{
    protected static string $resource = PlaceResource::class;

    public function getTabs(): array
    {
        // Linear 商务风：紧凑 tab，去 N° 编号前缀
        return [
            'all' => Tab::make('全部')
                ->modifyQueryUsing(fn ($query) => $query),

            'camping' => Tab::make('露营')
                ->modifyQueryUsing(fn ($query) => $query->where('place_type', 'camping')),

            'play_water' => Tab::make('玩水')
                ->modifyQueryUsing(fn ($query) => $query->where('place_type', 'play_water')),

            'cafe' => Tab::make('咖啡')
                ->modifyQueryUsing(fn ($query) => $query->where('place_type', 'cafe')),

            'restaurant' => Tab::make('美食')
                ->modifyQueryUsing(fn ($query) => $query->where('place_type', 'restaurant')),

            'viewpoint' => Tab::make('拍照')
                ->modifyQueryUsing(fn ($query) => $query->where('place_type', 'viewpoint')),

            'mountain' => Tab::make('山峰')
                ->modifyQueryUsing(fn ($query) => $query->where('place_type', 'mountain')),

            'wishlist' => Tab::make('种草中')
                ->modifyQueryUsing(fn ($query) => $query->where('is_wishlist', true))
                ->badge(fn () => \App\Models\Place::where('is_wishlist', true)->count()),

            'visited' => Tab::make('已去过')
                ->modifyQueryUsing(fn ($query) => $query->where('is_visited', true))
                ->badge(fn () => \App\Models\Place::where('is_visited', true)->count()),

            'unpublished' => Tab::make('下架')
                ->modifyQueryUsing(fn ($query) => $query->where('is_public', false))
                ->badge(fn () => \App\Models\Place::where('is_public', false)->count())
                ->badgeColor('warning'),
        ];
    }

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make()->label('添加地点'),
        ];
    }
}
