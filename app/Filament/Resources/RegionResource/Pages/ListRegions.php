<?php

namespace App\Filament\Resources\RegionResource\Pages;

use App\Filament\Resources\RegionResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListRegions extends ListRecords
{
    protected static string $resource = RegionResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\Action::make('reseed')
                ->label('重新灌入数据')
                ->icon('heroicon-o-arrow-path')
                ->color('warning')
                ->requiresConfirmation()
                ->modalHeading('重新灌入全国行政区划？')
                ->modalDescription('将清空 regions 表全部数据并重新写入 4 国/31 省/300 城市。已有的用户地区数据不会被清除。')
                ->action(function () {
                    \Artisan::call('db:seed', ['--class' => \Database\Seeders\RegionSeeder::class, '--force' => true]);
                    \Filament\Notifications\Notification::make()
                        ->title('已重新灌入')
                        ->success()
                        ->send();
                }),
            Actions\CreateAction::make(),
        ];
    }
}
