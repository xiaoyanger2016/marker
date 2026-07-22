<?php

namespace App\Filament\Resources\ContentResource\Pages;

use App\Filament\Resources\ContentResource;
use App\Models\Content;
use Filament\Actions;
use Filament\Infolists;
use Filament\Infolists\Infolist;
use Filament\Resources\Pages\ViewRecord;

class ViewContent extends ViewRecord
{
    protected static string $resource = ContentResource::class;

    public function infolist(Infolist $infolist): Infolist
    {
        return $infolist->schema([
            Infolists\Components\Section::make('基础信息')->schema([
                Infolists\Components\TextEntry::make('title')->label('标题')->size('lg')->weight('bold'),
                Infolists\Components\TextEntry::make('subtitle')->label('副标题'),
                Infolists\Components\TextEntry::make('type')
                    ->label('类型')
                    ->badge()
                    ->formatStateUsing(fn ($state) => Content::TYPES[$state]['icon'] . ' ' . (Content::TYPES[$state]['label'] ?? $state)),
                Infolists\Components\TextEntry::make('rating_label')
                    ->label('评分')
                    ->formatStateUsing(fn ($state) => $state ? (Content::RATING_LABELS[$state]['label'] ?? $state) : '—'),
                Infolists\Components\TextEntry::make('summary')->label('简介'),
                Infolists\Components\TextEntry::make('description')->label('详情')->html(),
            ])->columns(2),

            Infolists\Components\Section::make('关联地点')->schema([
                Infolists\Components\TextEntry::make('places_summary')
                    ->label('')
                    ->getStateUsing(function ($record) {
                        $lines = $record->places->map(fn ($p) =>
                            '<span class="font-mono text-xs text-gray-500">#' . str_pad($p->id, 2, '0', STR_PAD_LEFT) . '</span> ' .
                            ($p->pivot->sequence + 1) . '. ' . $p->name .
                            ($p->city ? ' <span class="text-xs text-gray-400">· ' . $p->city . '</span>' : '') .
                            ($p->pivot->notes ? '<br><span class="text-xs text-gray-500 pl-4">' . e($p->pivot->notes) . '</span>' : '')
                        );
                        return $lines->implode('<br>') ?: '无';
                    })
                    ->html(),
            ]),

            Infolists\Components\Section::make('媒体')->schema([
                Infolists\Components\TextEntry::make('media_summary')
                    ->label('')
                    ->getStateUsing(function ($record) {
                        $g = $record->gallery->count();
                        $v = $record->videos->count();
                        return "相册 {$g} 张 · 视频 {$v} 条";
                    }),
            ]),
        ]);
    }

    protected function getHeaderActions(): array
    {
        return [
            Actions\EditAction::make()->label('编辑'),
        ];
    }
}
