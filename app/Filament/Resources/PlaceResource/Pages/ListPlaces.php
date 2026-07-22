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
        // 编辑感 tab：N°编号 + 中文，去掉所有 emoji
        return [
            'all' => Tab::make('N°00 · 全部')
                ->modifyQueryUsing(fn ($query) => $query),

            'camping' => Tab::make('N°01 · 露营')
                ->modifyQueryUsing(fn ($query) => $query->where('place_type', 'camping')),

            'play_water' => Tab::make('N°02 · 玩水')
                ->modifyQueryUsing(fn ($query) => $query->where('place_type', 'play_water')),

            'cafe' => Tab::make('N°03 · 咖啡')
                ->modifyQueryUsing(fn ($query) => $query->where('place_type', 'cafe')),

            'restaurant' => Tab::make('N°04 · 美食')
                ->modifyQueryUsing(fn ($query) => $query->where('place_type', 'restaurant')),

            'viewpoint' => Tab::make('N°05 · 拍照')
                ->modifyQueryUsing(fn ($query) => $query->where('place_type', 'viewpoint')),

            'mountain' => Tab::make('N°06 · 山峰')
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
            Actions\CreateAction::make()->label('+ 添加地点'),
        ];
    }
}
