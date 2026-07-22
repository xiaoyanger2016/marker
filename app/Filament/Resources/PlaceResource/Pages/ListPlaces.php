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
        // 8 大类 tabs + 状态 tabs
        return [
            'all' => Tab::make('全部')
                ->modifyQueryUsing(fn ($query) => $query),

            'self_drive' => Tab::make('自驾')
                ->modifyQueryUsing(fn ($query) => $query->where('place_type', 'self_drive')),

            'play_water' => Tab::make('玩水')
                ->modifyQueryUsing(fn ($query) => $query->where('place_type', 'play_water')),

            'hiking' => Tab::make('徒步')
                ->modifyQueryUsing(fn ($query) => $query->where('place_type', 'hiking')),

            'paddle' => Tab::make('桨板')
                ->modifyQueryUsing(fn ($query) => $query->where('place_type', 'paddle')),

            'photo' => Tab::make('拍照')
                ->modifyQueryUsing(fn ($query) => $query->where('place_type', 'photo')),

            'food' => Tab::make('美食')
                ->modifyQueryUsing(fn ($query) => $query->where('place_type', 'food')),

            'camping' => Tab::make('露营')
                ->modifyQueryUsing(fn ($query) => $query->where('place_type', 'camping')),

            'sunrise_sunset' => Tab::make('日出日落')
                ->modifyQueryUsing(fn ($query) => $query->where('place_type', 'sunrise_sunset')),

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
