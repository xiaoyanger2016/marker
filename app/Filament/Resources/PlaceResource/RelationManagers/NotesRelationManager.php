<?php

namespace App\Filament\Resources\PlaceResource\RelationManagers;

use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables;
use Filament\Tables\Table;

class NotesRelationManager extends RelationManager
{
    protected static string $relationship = 'notes';

    protected static ?string $title = '关联笔记/小红书';

    public function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Hidden::make('user_id')
                    ->default(fn () => auth()->id()),

                Forms\Components\TextInput::make('title')
                    ->label('标题')
                    ->required()
                    ->maxLength(300)
                    ->columnSpanFull(),

                Forms\Components\Select::make('source')
                    ->label('来源')
                    ->options([
                        'manual' => '手动',
                        'xiaohongshu' => '小红书',
                        'dianping' => '大众点评',
                        'mafengwo' => '马蜂窝',
                    ])
                    ->default('manual')
                    ->required()
                    ->live(),

                Forms\Components\TextInput::make('source_url')
                    ->label('原始链接')
                    ->url()
                    ->placeholder('https://www.xiaohongshu.com/explore/...')
                    ->columnSpanFull()
                    ->visible(fn (Forms\Get $get) => $get('source') !== 'manual'),

                Forms\Components\TextInput::make('author')
                    ->label('作者')
                    ->maxLength(100)
                    ->visible(fn (Forms\Get $get) => $get('source') !== 'manual'),

                Forms\Components\Textarea::make('content')
                    ->label('内容')
                    ->rows(4)
                    ->columnSpanFull(),

                Forms\Components\TextInput::make('cover_url')
                    ->label('封面图URL')
                    ->url()
                    ->columnSpanFull(),
            ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('title')
            ->defaultSort('created_at', 'desc')
            ->columns([
                Tables\Columns\ImageColumn::make('cover_url')
                    ->label('封面')
                    ->height(50)
                    ->width(50)
                    ->extraImgAttributes(['style' => 'object-fit: cover; border-radius: 4px;']),

                Tables\Columns\TextColumn::make('title')
                    ->label('标题')
                    ->searchable()
                    ->limit(40),

                Tables\Columns\TextColumn::make('source')
                    ->label('来源')
                    ->badge()
                    ->color(fn ($state) => match ($state) {
                        'xiaohongshu' => 'danger',
                        'dianping' => 'warning',
                        'mafengwo' => 'info',
                        default => 'gray',
                    })
                    ->formatStateUsing(fn ($state) => match ($state) {
                        'xiaohongshu' => '小红书',
                        'dianping' => '大众点评',
                        'mafengwo' => '马蜂窝',
                        default => '手动',
                    }),

                Tables\Columns\TextColumn::make('author')->label('作者'),
                Tables\Columns\TextColumn::make('source_url')
                    ->label('链接')
                    ->limit(30)
                    ->url(fn ($record) => $record->source_url)
                    ->openUrlInNewTab(),

                Tables\Columns\TextColumn::make('created_at')
                    ->label('创建')
                    ->dateTime('Y-m-d'),
            ])
            ->headerActions([
                Tables\Actions\CreateAction::make()
                    ->mutateFormDataUsing(function (array $data): array {
                        $data['user_id'] = auth()->id();
                        return $data;
                    }),
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
                Tables\Actions\DeleteAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ]);
    }
}
