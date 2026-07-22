<?php

namespace App\Filament\Resources;

use App\Filament\Resources\PlaceResource\Pages;
use App\Filament\Resources\PlaceResource\RelationManagers;
use App\Models\Place;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

/**
 * Place 资源 — 纯 location 子表管理
 *
 * Place 现在是内容 (Content) 的地点子表，自身不持有 type/category/状态。
 * Admin 这里主要让用户浏览 / 修正地点基础信息 (名称/地址/坐标/联系方式)。
 * 新建内容请走 Content 资源。
 */
class PlaceResource extends Resource
{
    protected static ?string $model = Place::class;

    protected static ?string $navigationIcon = 'heroicon-o-map-pin';

    protected static ?string $navigationLabel = '位置库';

    protected static ?string $modelLabel = '位置';

    protected static ?string $pluralModelLabel = '位置库';

    protected static ?string $navigationGroup = '数据中台';

    protected static ?int $navigationSort = 30;

    public static function form(Form $form): Form
    {
        return $form->schema([
            Forms\Components\Tabs::make()->tabs([
                Forms\Components\Tabs\Tab::make('基础信息')
                    ->icon('heroicon-o-information-circle')
                    ->schema([
                        Forms\Components\Hidden::make('user_id')
                            ->default(fn () => auth()->id()),

                        Forms\Components\TextInput::make('name')
                            ->label('地点名称')
                            ->required()
                            ->maxLength(200)
                            ->columnSpanFull(),

                        Forms\Components\Textarea::make('description')
                            ->label('描述 / 笔记')
                            ->rows(3)
                            ->columnSpanFull(),

                        Forms\Components\Grid::make(2)->schema([
                            Forms\Components\TextInput::make('phone')->label('电话')->tel()->maxLength(30),
                            Forms\Components\TextInput::make('website')->label('官网')->url()->maxLength(255),
                        ]),

                        Forms\Components\Grid::make(2)->schema([
                            Forms\Components\TextInput::make('business_hours')->label('营业时间')->placeholder('09:00-22:00'),
                            Forms\Components\TextInput::make('price_range')->label('人均消费')->numeric()->prefix('¥'),
                        ]),

                        Forms\Components\Grid::make(2)->schema([
                            Forms\Components\TextInput::make('booking_url')->label('预订链接')->url(),
                            Forms\Components\TextInput::make('wechat_id')->label('微信号'),
                        ]),
                    ])->columns(2),

                Forms\Components\Tabs\Tab::make('位置 & 来源')
                    ->icon('heroicon-o-map-pin')
                    ->schema([
                        Forms\Components\Grid::make(2)->schema([
                            Forms\Components\TextInput::make('latitude')->label('纬度')->required()->numeric()->step(0.0000001)->rules(['between:-90,90']),
                            Forms\Components\TextInput::make('longitude')->label('经度')->required()->numeric()->step(0.0000001)->rules(['between:-180,180']),
                        ]),

                        Forms\Components\TextInput::make('address')->label('详细地址')->maxLength(255)->columnSpanFull(),
                        Forms\Components\Grid::make(3)->schema([
                            Forms\Components\TextInput::make('province')->label('省')->maxLength(60),
                            Forms\Components\TextInput::make('city')->label('市')->maxLength(60),
                            Forms\Components\TextInput::make('district')->label('区/县')->maxLength(60),
                        ]),
                        Forms\Components\TextInput::make('country')->label('国家')->default('中国')->maxLength(60),

                        Forms\Components\Section::make('POI 来源')
                            ->collapsed()
                            ->schema([
                                Forms\Components\Grid::make(3)->schema([
                                    Forms\Components\Select::make('poi_source')->label('来源')
                                        ->options([
                                            'manual'      => '手动添加',
                                            'amap'        => '高德地图',
                                            'baidu'       => '百度地图',
                                            'xiaohongshu' => '小红书',
                                            'dianping'    => '大众点评',
                                        ])->default('manual'),
                                    Forms\Components\TextInput::make('poi_id')->label('POI ID'),
                                    Forms\Components\TextInput::make('poi_type')->label('POI 分类'),
                                ]),
                            ]),
                    ]),
            ])->columnSpanFull(),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('id')
                    ->label('#')
                    ->formatStateUsing(fn ($state) => str_pad($state, 2, '0', STR_PAD_LEFT))
                    ->fontFamily('mono')
                    ->width('50px')
                    ->color('gray'),

                Tables\Columns\TextColumn::make('name')
                    ->label('名称')
                    ->searchable(['name', 'address', 'city'])
                    ->sortable()
                    ->weight('medium')
                    ->size('lg')
                    ->wrap(),

                Tables\Columns\TextColumn::make('city')
                    ->label('城市')
                    ->searchable()
                    ->fontFamily('mono')
                    ->size('xs')
                    ->color('gray'),

                Tables\Columns\TextColumn::make('address')
                    ->label('地址')
                    ->limit(40)
                    ->fontFamily('mono')
                    ->size('xs')
                    ->color('gray')
                    ->toggleable(),

                Tables\Columns\TextColumn::make('latitude')
                    ->label('坐标')
                    ->formatStateUsing(fn ($record) => $record->latitude ? number_format((float)$record->latitude, 4) . ' / ' . number_format((float)$record->longitude, 4) : '—')
                    ->fontFamily('mono')
                    ->size('xs')
                    ->color('gray')
                    ->toggleable(),

                Tables\Columns\TextColumn::make('contents_count')
                    ->label('被引用')
                    ->counts('contents')
                    ->badge()
                    ->color('info')
                    ->alignCenter(),

                Tables\Columns\TextColumn::make('media_count')
                    ->label('媒体')
                    ->counts('media')
                    ->badge()
                    ->color('gray')
                    ->alignCenter()
                    ->toggleable(),

                Tables\Columns\TextColumn::make('poi_source')
                    ->label('来源')
                    ->badge()
                    ->color(fn ($state) => match($state) {
                        'amap' => 'success', 'baidu' => 'info', 'xiaohongshu' => 'warning',
                        'dianping' => 'warning', default => 'gray',
                    })
                    ->formatStateUsing(fn ($state) => match($state) {
                        'amap' => '高德', 'baidu' => '百度', 'xiaohongshu' => '小红书',
                        'dianping' => '点评', 'manual' => '手动', default => $state ?? '—',
                    })
                    ->alignCenter(),

                Tables\Columns\TextColumn::make('created_at')
                    ->label('入库')
                    ->dateTime('Y-m-d')
                    ->fontFamily('mono')
                    ->size('xs')
                    ->color('gray')
                    ->sortable(),
            ])
            ->defaultSort('created_at', 'desc')
            ->filters([
                Tables\Filters\SelectFilter::make('city')
                    ->label('城市')
                    ->options(fn () => Place::query()->whereNotNull('city')->where('city', '!=', '')
                        ->groupBy('city')->orderBy('city')->pluck('city', 'city')->take(50)->toArray()),

                Tables\Filters\Filter::make('used_in_content')
                    ->label('已被内容引用')
                    ->query(fn (Builder $q) => $q->has('contents'))
                    ->toggle(),

                Tables\Filters\TrashedFilter::make(),
            ])
            ->actions([
                Tables\Actions\ActionGroup::make([
                    Tables\Actions\ViewAction::make()->label('查看'),
                    Tables\Actions\EditAction::make()->label('编辑'),
                    Tables\Actions\DeleteAction::make()->label('删除'),
                ])->icon('heroicon-o-ellipsis-horizontal')->iconPosition('after')->label('操作'),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make()->label('批量删除'),
                ]),
            ]);
    }

    public static function getRelations(): array
    {
        return [
            RelationManagers\MediaRelationManager::class,
            RelationManagers\NotesRelationManager::class,
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListPlaces::route('/'),
            'create' => Pages\CreatePlace::route('/create'),
            'view' => Pages\ViewPlace::route('/{record}'),
            'edit' => Pages\EditPlace::route('/{record}/edit'),
        ];
    }

    public static function getEloquentQuery(): Builder
    {
        $query = parent::getEloquentQuery()->withoutGlobalScopes();

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
