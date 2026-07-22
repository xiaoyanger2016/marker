<?php

namespace App\Filament\Resources\NoteResource\Pages;

use App\Filament\Resources\NoteResource;
use App\Services\v1\NoteLinkParser;
use Filament\Actions;
use Filament\Forms;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Support\Str;

class CreateNote extends CreateRecord
{
    protected static string $resource = NoteResource::class;

    public ?string $pendingUrl = null;

    /**
     * Phase 19：顶部「从链接快速建笔记」按钮
     * 粘贴小红书/大众点评/马蜂窝 URL → 自动解析 og meta + 提 note id → 预填表单
     */
    protected function getHeaderActions(): array
    {
        return [
            Actions\Action::make('fromUrl')
                ->label('从链接快速建笔记')
                ->icon('heroicon-o-link')
                ->color('primary')
                ->modalHeading('粘贴外部平台链接')
                ->modalDescription('支持小红书 / 大众点评 / 马蜂窝。会尝试抓 og:title / og:image / og:description 预填。')
                ->modalSubmitActionLabel('解析并预填')
                ->form([
                    Forms\Components\TextInput::make('url')
                        ->label('笔记 URL')
                        ->placeholder('https://www.xiaohongshu.com/explore/...')
                        ->required()
                        ->url()
                        ->columnSpanFull(),
                ])
                ->action(function (array $data) {
                    $parser = new NoteLinkParser();
                    $parsed = $parser->parse($data['url']);

                    // 预填到 CreateRecord 的 form data
                    $this->form->fill([
                        'title'       => $parsed['title'] ?: '未命名笔记',
                        'source'      => $parsed['source'],
                        'source_url'  => $parsed['source_url'],
                        'author'      => $parsed['author'],
                        'cover_url'   => $parsed['cover_url'],
                        'content'     => $parsed['content'],
                        'xhs_note_id' => $parsed['xhs_note_id'],
                        'xhs_xsec_token' => $parsed['xhs_xsec_token'],
                        'user_id'     => auth()->id(),
                    ]);

                    $msg = $parsed['title']
                        ? "已识别「{$parsed['source']}」: " . Str::limit($parsed['title'], 30)
                        : '解析完成';
                    if ($parsed['warning']) {
                        Notification::make()
                            ->title('部分解析: ' . $parsed['warning'])
                            ->body($msg)
                            ->warning()
                            ->send();
                    } else {
                        Notification::make()
                            ->title('已预填笔记')
                            ->body($msg)
                            ->success()
                            ->send();
                    }
                }),
        ];
    }
}
