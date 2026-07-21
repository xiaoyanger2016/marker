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

class PlaceResource extends Resource
{
    protected static ?string $model = Place::class;

    protected static ?string $navigationIcon = 'heroicon-o-map-pin';

    protected static ?string $navigationLabel = '收藏地点';

    protected static ?string $modelLabel = '地点';

    protected static ?string $pluralModelLabel = '收藏地点';

    protected static ?int $navigationSort = 10;

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Tabs::make()->tabs([
                    Forms\Components\Tabs\Tab::make('基础信息')
                        ->icon('heroicon-o-information-circle')
                        ->schema([
                            Forms\Components\Hidden::make('user_id')
                                ->default(fn () => auth()->id()),

                            Forms\Components\TextInput::make('name')
                                ->label('名称')
                                ->required()
                                ->maxLength(200)
                                ->columnSpanFull(),

                            Forms\Components\Select::make('category_id')
                                ->label('分类')
                                ->relationship('category', 'name', fn ($query) => $query->whereNull('user_id')->orWhere('user_id', auth()->id()))
                                ->searchable()
                                ->preload()
                                ->createOptionForm([
                                    Forms\Components\TextInput::make('name')->required(),
                                    Forms\Components\TextInput::make('icon')->placeholder('🏕️'),
                                    Forms\Components\ColorPicker::make('color'),
                                ])
                                ->createOptionUsing(function (array $data) {
                                    $data['user_id'] = auth()->id();
                                    $data['slug'] = \Illuminate\Support\Str::slug($data['name']);
                                    return \App\Models\Category::create($data)->getKey();
                                }),

                            Forms\Components\Select::make('tags')
                                ->label('标签')
                                ->relationship('tags', 'name', fn ($query) => $query->where('user_id', auth()->id()))
                                ->multiple()
                                ->searchable()
                                ->preload()
                                ->createOptionForm([
                                    Forms\Components\TextInput::make('name')->required(),
                                ])
                                ->createOptionUsing(function (array $data) {
                                    $data['user_id'] = auth()->id();
                                    $data['slug'] = \Illuminate\Support\Str::slug($data['name']);
                                    return \App\Models\Tag::create($data)->getKey();
                                }),

                            Forms\Components\Textarea::make('description')
                                ->label('描述/笔记')
                                ->rows(4)
                                ->columnSpanFull(),

                            Forms\Components\TextInput::make('phone')
                                ->label('电话')
                                ->tel()
                                ->maxLength(30),

                            Forms\Components\TextInput::make('website')
                                ->label('官网')
                                ->url()
                                ->maxLength(255),

                            Forms\Components\TextInput::make('business_hours')
                                ->label('营业时间')
                                ->placeholder('09:00-22:00'),

                            Forms\Components\TextInput::make('price_range')
                                ->label('人均消费')
                                ->numeric()
                                ->prefix('¥'),

                            Forms\Components\Select::make('rating')
                                ->label('评分')
                                ->options([
                                    1 => '⭐',
                                    2 => '⭐⭐',
                                    3 => '⭐⭐⭐',
                                    4 => '⭐⭐⭐⭐',
                                    5 => '⭐⭐⭐⭐⭐',
                                ])
                                ->native(false),
                        ])->columns(2),

                    Forms\Components\Tabs\Tab::make('位置信息')
                        ->icon('heroicon-o-map-pin')
                        ->schema([
                            Forms\Components\Grid::make(2)->schema([
                                Forms\Components\TextInput::make('latitude')
                                    ->label('纬度')
                                    ->required()
                                    ->numeric()
                                    ->step(0.0000001)
                                    ->rules(['between:-90,90']),

                                Forms\Components\TextInput::make('longitude')
                                    ->label('经度')
                                    ->required()
                                    ->numeric()
                                    ->step(0.0000001)
                                    ->rules(['between:-180,180']),
                            ]),

                            Forms\Components\Placeholder::make('map_picker')
                                ->label('')
                                ->content(new \Illuminate\Support\HtmlString(
                                    '<div id="place-map-picker" style="height:300px;border-radius:8px;border:1px solid #ddd"></div>'
                                    . '<p class="text-xs text-gray-500 mt-1">点击地图选点，自动回填经纬度</p>'
                                ))
                                ->columnSpanFull(),

                            Forms\Components\TextInput::make('address')
                                ->label('详细地址')
                                ->maxLength(255)
                                ->columnSpanFull(),

                            Forms\Components\Grid::make(3)->schema([
                                Forms\Components\TextInput::make('province')->label('省')->maxLength(60),
                                Forms\Components\TextInput::make('city')->label('市')->maxLength(60),
                                Forms\Components\TextInput::make('district')->label('区/县')->maxLength(60),
                            ]),

                            Forms\Components\TextInput::make('country')
                                ->label('国家')
                                ->default('中国')
                                ->maxLength(60),
                        ]),

                    Forms\Components\Tabs\Tab::make('详细信息')
                        ->icon('heroicon-o-document-text')
                        ->schema([
                            Forms\Components\Section::make('类型与游玩')
                                ->schema([
                                    Forms\Components\Select::make('place_type')
                                        ->label('细分类（POI 类型）')
                                        ->options(collect(\App\Models\Place::PLACE_TYPES)->mapWithKeys(fn ($v, $k) => [$k => $v['icon'] . ' ' . $v['label']])->toArray())
                                        ->searchable()
                                        ->placeholder('选择更具体的类型'),

                                    Forms\Components\Grid::make(3)->schema([
                                        Forms\Components\Select::make('difficulty')
                                            ->label('难度')
                                            ->options(collect(\App\Models\Place::DIFFICULTY_LEVELS)->mapWithKeys(fn ($v, $k) => [$k => $v['label']])->toArray()),

                                        Forms\Components\TextInput::make('altitude_meters')
                                            ->label('海拔(米)')
                                            ->numeric(),

                                        Forms\Components\TextInput::make('recommended_duration_minutes')
                                            ->label('建议游玩时长(分钟)')
                                            ->numeric(),
                                    ]),

                                    Forms\Components\Select::make('best_season')
                                        ->label('最佳季节')
                                        ->options(\App\Models\Place::SEASONS)
                                        ->multiple(),

                                    Forms\Components\TextInput::make('suitable_for')
                                        ->label('适合人群')
                                        ->placeholder('亲子,情侣,朋友,独自'),
                                ]),

                            Forms\Components\Section::make('停车信息')
                                ->collapsible()
                                ->schema([
                                    Forms\Components\Toggle::make('has_parking')
                                        ->label('可停车')
                                        ->default(false)
                                        ->live(),

                                    Forms\Components\Grid::make(3)->schema([
                                        Forms\Components\Select::make('parking_fee_type')
                                            ->label('收费类型')
                                            ->options(\App\Models\Place::PARKING_FEE_TYPES)
                                            ->visible(fn (Forms\Get $get) => $get('has_parking')),

                                        Forms\Components\TextInput::make('parking_fee')
                                            ->label('费用')
                                            ->numeric()
                                            ->prefix('¥')
                                            ->visible(fn (Forms\Get $get) => $get('has_parking') && in_array($get('parking_fee_type'), ['per_time', 'per_hour', 'per_day'])),

                                        Forms\Components\TextInput::make('parking_capacity')
                                            ->label('大约车位数')
                                            ->numeric()
                                            ->visible(fn (Forms\Get $get) => $get('has_parking')),
                                    ]),

                                    Forms\Components\Textarea::make('parking_notes')
                                        ->label('停车备注')
                                        ->rows(2)
                                        ->visible(fn (Forms\Get $get) => $get('has_parking')),
                                ]),

                            Forms\Components\Section::make('门票信息')
                                ->collapsible()
                                ->schema([
                                    Forms\Components\Toggle::make('has_ticket')
                                        ->label('需门票')
                                        ->default(false)
                                        ->live(),

                                    Forms\Components\Grid::make(2)->schema([
                                        Forms\Components\TextInput::make('ticket_price')
                                            ->label('门票价格')
                                            ->numeric()
                                            ->prefix('¥')
                                            ->visible(fn (Forms\Get $get) => $get('has_ticket')),

                                        Forms\Components\TextInput::make('ticket_unit')
                                            ->label('单位')
                                            ->default('人')
                                            ->visible(fn (Forms\Get $get) => $get('has_ticket')),
                                    ]),

                                    Forms\Components\Textarea::make('ticket_notes')
                                        ->label('门票备注')
                                        ->rows(2)
                                        ->visible(fn (Forms\Get $get) => $get('has_ticket')),
                                ]),

                            Forms\Components\Section::make('装备与安全')
                                ->collapsible()
                                ->schema([
                                    Forms\Components\TagsInput::make('gear_checklist')
                                        ->label('装备清单')
                                        ->placeholder('回车添加')
                                        ->columnSpanFull(),

                                    Forms\Components\TagsInput::make('safety_notes')
                                        ->label('安全提示')
                                        ->placeholder('回车添加')
                                        ->columnSpanFull(),
                                ]),

                            Forms\Components\Section::make('预订/联系')
                                ->collapsible()
                                ->schema([
                                    Forms\Components\TextInput::make('booking_url')
                                        ->label('预订链接')
                                        ->url()
                                        ->columnSpanFull(),

                                    Forms\Components\TextInput::make('wechat_id')
                                        ->label('微信号'),
                                ]),
                        ]),

                    Forms\Components\Tabs\Tab::make('状态/POI 来源')
                        ->icon('heroicon-o-tag')
                        ->schema([
                            Forms\Components\Grid::make(3)->schema([
                                Forms\Components\Toggle::make('is_visited')
                                    ->label('已去过')
                                    ->default(false),

                                Forms\Components\Toggle::make('is_wishlist')
                                    ->label('种草/想去')
                                    ->default(false),

                                Forms\Components\Toggle::make('is_public')
                                    ->label('公开（可分享）')
                                    ->default(true),
                            ]),

                            Forms\Components\Grid::make(2)->schema([
                                Forms\Components\DatePicker::make('visited_at')
                                    ->label('上次去过时间')
                                    ->native(false),

                                Forms\Components\TextInput::make('visit_count')
                                    ->label('去过次数')
                                    ->numeric()
                                    ->default(0),
                            ]),

                            Forms\Components\Section::make('POI 来源信息')
                                ->collapsed()
                                ->schema([
                                    Forms\Components\Grid::make(3)->schema([
                                        Forms\Components\Select::make('poi_source')
                                            ->label('来源')
                                            ->options([
                                                'manual' => '手动添加',
                                                'amap' => '高德地图',
                                                'baidu' => '百度地图',
                                                'xiaohongshu' => '小红书',
                                                'dianping' => '大众点评',
                                            ])
                                            ->default('manual'),

                                        Forms\Components\TextInput::make('poi_id')
                                            ->label('POI ID'),

                                        Forms\Components\TextInput::make('poi_type')
                                            ->label('POI 分类'),
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
                Tables\Columns\ImageColumn::make('cover')
                    ->label('封面')
                    ->getStateUsing(function ($record) {
                        $first = $record->media()->images()->first();
                        return $first?->url;
                    })
                    ->size(60)
                    ->defaultImageUrl(url('/images/placeholder.png')),

                Tables\Columns\TextColumn::make('name')
                    ->label('名称')
                    ->searchable(['name', 'address', 'city'])
                    ->sortable()
                    ->weight('bold'),

                Tables\Columns\TextColumn::make('category.name')
                    ->label('分类')
                    ->badge()
                    ->color(fn ($record) => $record->category?->color ?: 'gray')
                    ->formatStateUsing(fn ($state, $record) => ($record->category?->icon ?? '') . ' ' . $state)
                    ->searchable(),

                Tables\Columns\TextColumn::make('place_type')
                    ->label('细类')
                    ->formatStateUsing(function ($state) {
                        $types = \App\Models\Place::PLACE_TYPES;
                        return $types[$state]['icon'] . ' ' . ($types[$state]['label'] ?? $state) ?? null;
                    })
                    ->toggleable(),

                Tables\Columns\TextColumn::make('city')
                    ->label('城市')
                    ->searchable()
                    ->toggleable(),

                Tables\Columns\TextColumn::make('rating')
                    ->label('评分')
                    ->formatStateUsing(fn ($state) => $state ? str_repeat('⭐', $state) : '-')
                    ->alignCenter(),

                Tables\Columns\IconColumn::make('has_parking')
                    ->label('停车')
                    ->boolean()
                    ->trueIcon('heroicon-o-truck')
                    ->falseIcon('heroicon-o-minus-circle')
                    ->trueColor('info')
                    ->toggleable(),

                Tables\Columns\IconColumn::make('has_ticket')
                    ->label('门票')
                    ->boolean()
                    ->trueIcon('heroicon-o-ticket')
                    ->falseIcon('heroicon-o-minus-circle')
                    ->trueColor('warning')
                    ->toggleable(),

                Tables\Columns\IconColumn::make('is_visited')
                    ->label('去过')
                    ->boolean()
                    ->trueIcon('heroicon-o-check-circle')
                    ->falseIcon('heroicon-o-minus-circle'),

                Tables\Columns\IconColumn::make('is_wishlist')
                    ->label('种草')
                    ->boolean()
                    ->trueIcon('heroicon-o-heart')
                    ->falseIcon('heroicon-o-minus')
                    ->trueColor('danger'),

                Tables\Columns\IconColumn::make('is_public')
                    ->label('公开')
                    ->boolean(),

                Tables\Columns\TextColumn::make('created_at')
                    ->label('收藏于')
                    ->dateTime('Y-m-d')
                    ->sortable(),
            ])
            ->defaultSort('created_at', 'desc')
            ->filters([
                Tables\Filters\SelectFilter::make('category_id')
                    ->label('分类')
                    ->relationship('category', 'name', fn ($query) => $query->whereNull('user_id')->orWhere('user_id', auth()->id()))
                    ->preload()
                    ->multiple(),

                Tables\Filters\SelectFilter::make('place_type')
                    ->label('细类')
                    ->options(collect(\App\Models\Place::PLACE_TYPES)->mapWithKeys(fn ($v, $k) => [$k => $v['icon'] . ' ' . $v['label']])->toArray())
                    ->multiple(),

                Tables\Filters\Filter::make('has_parking')
                    ->label('可停车')
                    ->query(fn (Builder $query) => $query->where('has_parking', true))
                    ->toggle(),

                Tables\Filters\Filter::make('free_entry')
                    ->label('免门票')
                    ->query(fn (Builder $query) => $query->where('has_ticket', false))
                    ->toggle(),

                Tables\Filters\Filter::make('wishlist')
                    ->label('种草')
                    ->query(fn (Builder $query) => $query->where('is_wishlist', true))
                    ->toggle(),

                Tables\Filters\Filter::make('visited')
                    ->label('已去过')
                    ->query(fn (Builder $query) => $query->where('is_visited', true))
                    ->toggle(),

                Tables\Filters\Filter::make('public')
                    ->label('公开')
                    ->query(fn (Builder $query) => $query->where('is_public', true))
                    ->toggle(),

                Tables\Filters\TrashedFilter::make(),
            ])
            ->actions([
                Tables\Actions\ViewAction::make(),
                Tables\Actions\EditAction::make(),
                Tables\Actions\DeleteAction::make(),
                Tables\Actions\RestoreAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                    Tables\Actions\RestoreBulkAction::make(),
                    Tables\Actions\ForceDeleteBulkAction::make(),
                ]),
            ]);
    }

    public static function getRelations(): array
    {
        return [
            RelationManagers\MediaRelationManager::class,
            RelationManagers\NotesRelationManager::class,
            RelationManagers\CollectionsRelationManager::class,
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
        // 限制只看到自己的（管理员除外）
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
