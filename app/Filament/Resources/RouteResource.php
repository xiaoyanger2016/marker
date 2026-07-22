<?php

namespace App\Filament\Resources;

use App\Filament\Resources\RouteResource\Pages;
use App\Filament\Resources\RouteResource\RelationManagers;
use App\Models\Route;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Notifications\Notification;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

class RouteResource extends Resource
{
    protected static ?string $model = Route::class;

    protected static ?string $navigationIcon = 'heroicon-o-map';

    protected static ?string $navigationLabel = '线路（自驾/徒步）';

    protected static ?string $modelLabel = '线路';

    protected static ?string $pluralModelLabel = '线路';

    protected static ?string $navigationGroup = '内容管理';

    protected static ?int $navigationSort = 12;

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Hidden::make('user_id')
                    ->default(fn () => auth()->id()),

                Forms\Components\Tabs::make()->tabs([
                    Forms\Components\Tabs\Tab::make('基础信息')
                        ->icon('heroicon-o-information-circle')
                        ->schema([
                            Forms\Components\Select::make('type')
                                ->label('线路类型')
                                ->required()
                                ->options(collect(\App\Models\Route::TYPES)->mapWithKeys(fn ($v, $k) => [$k => $v['icon'] . ' ' . $v['label']])->toArray())
                                ->default('self_drive')
                                ->live()
                                ->afterStateUpdated(fn (Forms\Set $set) => $set('requires_order', $set('type') === 'self_drive')),

                            Forms\Components\TextInput::make('name')
                                ->label('线路名称')
                                ->required()
                                ->maxLength(200)
                                ->columnSpanFull(),

                            Forms\Components\TextInput::make('subtitle')
                                ->label('副标题')
                                ->maxLength(300)
                                ->columnSpanFull(),

                            Forms\Components\Textarea::make('summary')
                                ->label('简介（卡片用，1-2 句话）')
                                ->rows(2)
                                ->columnSpanFull(),

                            Forms\Components\RichEditor::make('description')
                                ->label('详情（长文描述）')
                                ->toolbarButtons(['bold', 'italic', 'bulletList', 'orderedList', 'h2', 'h3', 'link'])
                                ->columnSpanFull(),
                        ]),

                    Forms\Components\Tabs\Tab::make('封面与媒体')
                        ->icon('heroicon-o-photo')
                        ->schema([
                            Forms\Components\FileUpload::make('cover_upload')
                                ->label('封面图（必填）')
                                ->required()
                                ->image()
                                ->disk('public')
                                ->directory('routes/covers')
                                ->maxSize(5120)
                                ->afterStateUpdated(function ($state, Forms\Set $set) {
                                    if ($state) {
                                        $first = is_array($state) ? $state[0] : $state;
                                        $set('cover_media_path', $first);
                                    }
                                })
                                ->columnSpanFull()
                                ->helperText('推荐尺寸 1200x800，5MB 以内'),

                            Forms\Components\Repeater::make('gallery')
                                ->label('相册/视频（可选）')
                                ->schema([
                                    Forms\Components\Select::make('type')
                                        ->options(['image' => '图片', 'video' => '视频'])
                                        ->default('image')
                                        ->required(),
                                    Forms\Components\FileUpload::make('path')
                                        ->label('文件')
                                        ->disk('public')
                                        ->directory('routes/gallery')
                                        ->required()
                                        ->maxSize(51200),
                                    Forms\Components\TextInput::make('caption')->label('说明'),
                                ])
                                ->columns(3)
                                ->collapsible()
                                ->columnSpanFull()
                                ->addActionLabel('+ 添加图片/视频'),
                        ]),

                    Forms\Components\Tabs\Tab::make('沿途地点')
                        ->icon('heroicon-o-map-pin')
                        ->schema([
                            Forms\Components\Toggle::make('requires_order')
                                ->label('需要按顺序（自驾推荐开启）')
                                ->default(true)
                                ->live()
                                ->helperText('徒步线路无需顺序'),

                            Forms\Components\Select::make('place_ids')
                                ->label('沿途地点')
                                ->relationship('places', 'name', fn ($query) => $query->where('user_id', auth()->id()))
                                ->multiple()
                                ->searchable()
                                ->preload()
                                ->columnSpanFull()
                                ->helperText('多个地点用 Ctrl/Cmd 点选'),
                        ]),

                    Forms\Components\Tabs\Tab::make('详细信息')
                        ->icon('heroicon-o-document-text')
                        ->schema([
                            Forms\Components\Grid::make(3)->schema([
                                Forms\Components\Select::make('rating_label')
                                    ->label('评分')
                                    ->options(collect(\App\Models\Route::RATING_LABELS)->mapWithKeys(fn ($v, $k) => [$k => $v['icon'] . ' ' . $v['label']])->toArray())
                                    ->placeholder('选择评分'),

                                Forms\Components\Select::make('difficulty')
                                    ->label('难度')
                                    ->options(['easy' => '简单', 'moderate' => '中等', 'hard' => '困难']),

                                Forms\Components\TextInput::make('altitude_meters')
                                    ->label('海拔(米)')
                                    ->numeric(),
                            ]),

                            Forms\Components\Grid::make(3)->schema([
                                Forms\Components\TextInput::make('distance_km')
                                    ->label('总里程(km)')
                                    ->numeric()
                                    ->suffix('km'),
                                Forms\Components\TextInput::make('duration_hours')
                                    ->label('总时长(小时)')
                                    ->numeric()
                                    ->suffix('h'),
                                Forms\Components\Select::make('best_season')
                                    ->label('最佳季节')
                                    ->options(['春' => '春', '夏' => '夏', '秋' => '秋', '冬' => '冬', '四季' => '四季'])
                                    ->multiple(),
                            ]),

                            Forms\Components\Grid::make(3)->schema([
                                Forms\Components\TextInput::make('province')->label('省')->maxLength(60),
                                Forms\Components\TextInput::make('city')->label('市')->maxLength(60),
                                Forms\Components\TextInput::make('suitable_for')->label('适合人群')->maxLength(200),
                            ]),

                            Forms\Components\Grid::make(2)->schema([
                                Forms\Components\TextInput::make('start_point')->label('起点'),
                                Forms\Components\TextInput::make('end_point')->label('终点'),
                            ]),

                            Forms\Components\Repeater::make('gear_checklist')
                                ->label('装备清单')
                                ->schema([
                                    Forms\Components\TextInput::make('item')->label('装备')->required(),
                                ])
                                ->columns(1)
                                ->collapsible()
                                ->addActionLabel('+ 添加装备'),

                            Forms\Components\Repeater::make('safety_notes')
                                ->label('安全提示')
                                ->schema([
                                    Forms\Components\TextInput::make('note')->label('提示')->required(),
                                ])
                                ->columns(1)
                                ->collapsible()
                                ->addActionLabel('+ 添加提示'),
                        ]),

                    Forms\Components\Tabs\Tab::make('发布设置')
                        ->icon('heroicon-o-cog-6-tooth')
                        ->schema([
                            Forms\Components\Grid::make(3)->schema([
                                Forms\Components\Toggle::make('is_public')
                                    ->label('上架（公开）')
                                    ->default(true)
                                    ->helperText('关闭后仅自己可见')
                                    ->inline(false),

                                Forms\Components\Toggle::make('is_featured')
                                    ->label('推荐到首页')
                                    ->default(false)
                                    ->inline(false),

                                Forms\Components\TextInput::make('sort')
                                    ->label('排序')
                                    ->numeric()
                                    ->default(0),
                            ]),
                        ]),
                ])->columnSpanFull(),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\ImageColumn::make('cover_url')
                    ->label('封面')
                    ->getStateUsing(function ($record) {
                        return $record->cover_url ?: url('/images/placeholder.png');
                    })
                    ->height(50)
                    ->width(70)
                    ->extraImgAttributes(['style' => 'object-fit: cover; border-radius: 4px;']),

                Tables\Columns\TextColumn::make('name')
                    ->label('名称')
                    ->searchable()
                    ->weight('bold')
                    ->limit(30),

                Tables\Columns\TextColumn::make('type')
                    ->label('类型')
                    ->badge()
                    ->color(fn ($state) => $state === 'self_drive' ? 'danger' : 'success')
                    ->formatStateUsing(fn ($state) => \App\Models\Route::TYPES[$state]['icon'] . ' ' . (\App\Models\Route::TYPES[$state]['label'] ?? $state)),

                Tables\Columns\TextColumn::make('rating_label')
                    ->label('评分')
                    ->formatStateUsing(function ($state) {
                        if (! $state) return '-';
                        $m = \App\Models\Route::RATING_LABELS[$state] ?? null;
                        return $m ? "{$m['icon']} {$m['label']}" : $state;
                    }),

                Tables\Columns\TextColumn::make('city')->label('城市')->searchable()->toggleable(),

                Tables\Columns\TextColumn::make('distance_km')->label('km')->numeric()->toggleable(),
                Tables\Columns\TextColumn::make('places_count')->counts('places')->label('点数')->badge(),

                Tables\Columns\TextColumn::make('view_count')->label('浏览')->numeric()->toggleable(isToggledHiddenByDefault: true),
                Tables\Columns\TextColumn::make('like_count')->label('点赞')->numeric()->toggleable(isToggledHiddenByDefault: true),
                Tables\Columns\TextColumn::make('heat_score')->label('热度')->numeric(2)->sortable(),

                Tables\Columns\IconColumn::make('is_public')
                    ->label('上架')
                    ->boolean()
                    ->trueIcon('heroicon-o-eye')
                    ->falseIcon('heroicon-o-eye-slash')
                    ->trueColor('success')
                    ->falseColor('gray'),

                Tables\Columns\IconColumn::make('is_featured')
                    ->label('推荐')
                    ->boolean(),

                Tables\Columns\TextColumn::make('created_at')
                    ->label('创建')
                    ->dateTime('Y-m-d')
                    ->sortable(),
            ])
            ->defaultSort('heat_score', 'desc')
            ->filters([
                Tables\Filters\SelectFilter::make('type')
                    ->label('类型')
                    ->options(['self_drive' => 'N°01 · 自驾', 'hiking' => 'N°02 · 徒步'])
                    ->multiple(),

                Tables\Filters\SelectFilter::make('rating_label')
                    ->label('评分')
                    ->options(collect(\App\Models\Route::RATING_LABELS)->mapWithKeys(fn ($v, $k) => [$k => $v['icon'] . ' ' . $v['label']])->toArray())
                    ->multiple(),

                Tables\Filters\TernaryFilter::make('is_public')
                    ->label('上架状态')
                    ->placeholder('全部')
                    ->trueLabel('已上架')
                    ->falseLabel('已下架'),

                Tables\Filters\TernaryFilter::make('is_featured')
                    ->label('推荐')
                    ->placeholder('全部')
                    ->trueLabel('已推荐')
                    ->falseLabel('未推荐'),

                Tables\Filters\TrashedFilter::make(),
            ])
            ->actions([
                Tables\Actions\ActionGroup::make([
                    Tables\Actions\ViewAction::make(),
                    Tables\Actions\EditAction::make(),

                    Tables\Actions\Action::make('togglePublish')
                        ->label(fn ($record) => $record->is_public ? '下架' : '上架')
                        ->icon(fn ($record) => $record->is_public ? 'heroicon-o-eye-slash' : 'heroicon-o-eye')
                        ->color(fn ($record) => $record->is_public ? 'warning' : 'success')
                        ->requiresConfirmation()
                        ->action(function ($record) {
                            $record->is_public = ! $record->is_public;
                            $record->save();
                            Notification::make()
                                ->title($record->is_public ? '已上架' : '已下架')
                                ->body("线路「{$record->name}」" . ($record->is_public ? '已对所有用户可见' : '已从首页隐藏'))
                                ->success()
                                ->send();
                        }),

                    Tables\Actions\Action::make('toggleFeatured')
                        ->label(fn ($record) => $record->is_featured ? '取消推荐' : '推荐到首页')
                        ->icon('heroicon-o-star')
                        ->color('warning')
                        ->action(function ($record) {
                            $record->is_featured = ! $record->is_featured;
                            $record->save();
                            Notification::make()->title($record->is_featured ? '已推荐' : '已取消')->success()->send();
                        }),

                    Tables\Actions\Action::make('copyLink')
                        ->label('复制前台链接')
                        ->icon('heroicon-o-link')
                        ->url(fn ($record) => url('/route/' . $record->id))
                        ->openUrlInNewTab(),

                    Tables\Actions\DeleteAction::make(),
                    Tables\Actions\RestoreAction::make(),
                ]),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\BulkAction::make('publish')
                        ->label('批量上架')
                        ->icon('heroicon-o-eye')
                        ->color('success')
                        ->action(fn ($records) => $records->each->update(['is_public' => true]))
                        ->deselectRecordsAfterCompletion(),
                    Tables\Actions\BulkAction::make('unpublish')
                        ->label('批量下架')
                        ->icon('heroicon-o-eye-slash')
                        ->color('warning')
                        ->action(fn ($records) => $records->each->update(['is_public' => false]))
                        ->deselectRecordsAfterCompletion(),
                    Tables\Actions\DeleteBulkAction::make(),
                    Tables\Actions\RestoreBulkAction::make(),
                ]),
            ]);
    }

    public static function getRelations(): array
    {
        return [
            //
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListRoutes::route('/'),
            'create' => Pages\CreateRoute::route('/create'),
            'view' => Pages\ViewRoute::route('/{record}'),
            'edit' => Pages\EditRoute::route('/{record}/edit'),
        ];
    }

    public static function getEloquentQuery(): \Illuminate\Database\Eloquent\Builder
    {
        $query = parent::getEloquentQuery()
            ->withoutGlobalScopes();

        if (! auth()->user()?->is_admin) {
            $query->where('user_id', auth()->id());
        }

        return $query;
    }

    public static function getNavigationBadge(): ?string
    {
        return static::getEloquentQuery()->count() ?: null;
    }
}
