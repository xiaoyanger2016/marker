<?php

namespace App\Filament\Resources\PlaceResource\Pages;

use App\Filament\Resources\PlaceResource;
use App\Services\v1\PlaceService;
use Filament\Actions;
use Filament\Forms;
use Filament\Forms\Get;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\EditRecord;

class EditPlace extends EditRecord
{
    protected static string $resource = PlaceResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\Action::make('geocode')
                ->label('识别位置')
                ->icon('heroicon-o-map-pin')
                ->color('gray')
                ->modalHeading('从详细地址识别位置')
                ->modalDescription('点「识别」会调高德 geocode API，把当前表单里的「详细地址」转成经纬度 + 省/市/区。')
                ->modalSubmitActionLabel('识别')
                ->form([
                    Forms\Components\TextInput::make('address')
                        ->label('详细地址')
                        ->required()
                        ->placeholder('如：浙江省绍兴市西施岩村')
                        ->helperText('会自动带上表单当前的详细地址 + 城市（可改）'),
                    Forms\Components\TextInput::make('city')
                        ->label('所在城市（可选）')
                        ->placeholder('如：绍兴')
                        ->helperText('加城市能显著提升识别准确度'),
                ])
                ->fillForm([
                    'address' => fn (Get $get) => $get('address') ?? '',
                    'city'    => fn (Get $get) => $get('city') ?? '',
                ])
                ->action(function (array $data) {
                    /** @var PlaceService $svc */
                    $svc = app(PlaceService::class);
                    $r = $svc->geocodeFromAddress($data['address'], $data['city'] ?: null);

                    if (! $r['success']) {
                        Notification::make()
                            ->title('识别失败')
                            ->body($r['message'] ?? '未知错误')
                            ->danger()
                            ->send();
                        return;
                    }

                    $this->form->fill([
                        'address'   => $r['formatted_address'] ?: $data['address'],
                        'latitude'  => $r['latitude'],
                        'longitude' => $r['longitude'],
                        'province'  => $r['province'],
                        'city'      => $r['city'],
                        'district'  => $r['district'],
                    ]);

                    Notification::make()
                        ->title('识别成功')
                        ->body(sprintf(
                            "经纬度：%s, %s · 行政区：%s · 匹配度：%s",
                            number_format($r['longitude'], 6),
                            number_format($r['latitude'], 6),
                            trim(($r['province'] ?? '') . ' ' . ($r['city'] ?? '') . ' ' . ($r['district'] ?? '')),
                            $r['level'] ?? '—'
                        ))
                        ->success()
                        ->send();
                }),
            Actions\ViewAction::make()->label('查看'),
            Actions\DeleteAction::make()->label('删除'),
        ];
    }
}
