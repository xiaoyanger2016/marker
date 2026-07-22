<?php

namespace App\Filament\Resources\ContentResource\Pages;

use App\Filament\Resources\ContentResource;
use App\Models\Content;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;
use Filament\Resources\Pages\ListRecords\Tab;

class ListContents extends ListRecords
{
    protected static string $resource = ContentResource::class;

    public function getTabs(): array
    {
        $all = static::getResource()::getEloquentQuery();
        $tab = function (string $type) use ($all) {
            return Tab::make(Content::TYPES[$type]['icon'] . ' ' . Content::TYPES[$type]['label'])
                ->modifyQueryUsing(fn ($query) => $query->where('type', $type))
                ->badge(fn () => $all->where('type', $type)->count() ?: null)
                ->badgeColor('info');
        };

        return [
            'all' => Tab::make('全部')
                ->badge(fn () => $all->count() ?: null),

            'self_drive' => $tab('self_drive'),
            'play_water' => $tab('play_water'),
            'hiking' => $tab('hiking'),
            'paddle' => $tab('paddle'),
            'photo' => $tab('photo'),
            'food' => $tab('food'),
            'camping' => $tab('camping'),
            'sunrise_sunset' => $tab('sunrise_sunset'),

            'public' => Tab::make('已上架')
                ->modifyQueryUsing(fn ($q) => $q->where('is_public', true))
                ->badge(fn () => $all->where('is_public', true)->count() ?: null)
                ->badgeColor('success'),

            'draft' => Tab::make('草稿')
                ->modifyQueryUsing(fn ($q) => $q->where('is_public', false))
                ->badge(fn () => $all->where('is_public', false)->count() ?: null)
                ->badgeColor('gray'),

            'wishlist' => Tab::make('种草中')
                ->modifyQueryUsing(fn ($q) => $q->where('is_wishlist', true))
                ->badge(fn () => $all->where('is_wishlist', true)->count() ?: null)
                ->badgeColor('warning'),

            'visited' => Tab::make('已去过')
                ->modifyQueryUsing(fn ($q) => $q->where('is_visited', true))
                ->badge(fn () => $all->where('is_visited', true)->count() ?: null)
                ->badgeColor('info'),
        ];
    }

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make()->label('新建内容'),
        ];
    }
}
