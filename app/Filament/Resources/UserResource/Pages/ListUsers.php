<?php

namespace App\Filament\Resources\UserResource\Pages;

use App\Filament\Resources\UserResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListUsers extends ListRecords
{
    protected static string $resource = UserResource::class;

    protected ?string $heading = '用户';

    protected ?string $subheading = '管理注册用户、角色与权限';

    public function getBreadcrumb(): string
    {
        return '列表';
    }

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make()->label('新建用户'),
        ];
    }
}
