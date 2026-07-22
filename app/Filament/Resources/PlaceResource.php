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
                                    Forms\Components\TextInput::make('icon')->placeholder('N°01'),
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
                                ->label('评分（5 档）')
                                ->options([
                                    1 => '拉垮',
                                    2 => 'NPC',
                                    3 => 'NICE',
                                    4 => '超值',
                                    5 => '夯',
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
                            // ====== 类型选择 (8 大类) ======
                            Forms\Components\Section::make('类型')
                                ->schema([
                                    Forms\Components\Select::make('place_type')
                                        ->label('类型（8 大类）')
                                        ->options(collect(\App\Models\Place::PLACE_TYPES)
                                            ->mapWithKeys(fn ($v, $k) => [$k => ($v['icon'] ?? '') . ' ' . $v['label']])->toArray())
                                        ->required()
                                        ->live()
                                        ->afterStateUpdated(fn (Forms\Set $set) => $set('attributes', []))
                                        ->helperText(fn (Forms\Get $get) => \App\Models\Place::PLACE_TYPES[$get('place_type')]['desc'] ?? '选择类型后下方出现对应字段'),

                                    Forms\Components\Select::make('category_id')
                                        ->label('分类（组织维度）')
                                        ->relationship('category', 'name', fn ($query) => $query->where(fn ($q) =>
                                            $q->whereNull('user_id')->orWhere('user_id', auth()->id())))
                                        ->searchable()
                                        ->preload()
                                        ->helperText('系统预设 8 大类 + 你的私人分类'),
                                ]),

                            // ====== type-specific 动态属性 ======
                            Forms\Components\Section::make('类型专属信息')
                                ->description(fn (Forms\Get $get) => \App\Models\Place::PLACE_TYPES[$get('place_type')]['label'] ?? '')
                                ->schema(function (Forms\Get $get) {
                                    $type = $get('place_type');
                                    $defs = \App\Models\Place::TYPE_ATTRIBUTES[$type] ?? [];
                                    if (! $defs) {
                                        return [
                                            Forms\Components\Placeholder::make('no_type')
                                                ->label('')
                                                ->content('先选择类型'),
                                        ];
                                    }

                                    // 按 group 分组
                                    $byGroup = [];
                                    foreach ($defs as $i => $d) {
                                        $byGroup[$d['group']][$d['key']] = $d + ['_idx' => $i];
                                    }

                                    $fields = [];
                                    foreach ($byGroup as $group => $items) {
                                        $groupFields = [];
                                        foreach ($items as $key => $d) {
                                            $path = "attributes.{$key}";

                                            $field = match ($d['type']) {
                                                'number' => Forms\Components\TextInput::make($path)
                                                    ->label($d['label'])
                                                    ->numeric()
                                                    ->suffix($d['unit'] ?? null),
                                                'select' => Forms\Components\Select::make($path)
                                                    ->label($d['label'])
                                                    ->options($d['options'] ?? []),
                                                'checkbox-list' => Forms\Components\CheckboxList::make($path)
                                                    ->label($d['label'])
                                                    ->options($d['options'] ?? []),
                                                'toggle' => Forms\Components\Toggle::make($path)
                                                    ->label($d['label'])
                                                    ->inline(false),
                                                'repeater' => Forms\Components\TagsInput::make($path)
                                                    ->label($d['label'])
                                                    ->placeholder($d['placeholder'] ?? '回车添加')
                                                    ->columnSpanFull(),
                                                'textarea' => Forms\Components\Textarea::make($path)
                                                    ->label($d['label'])
                                                    ->rows(2)
                                                    ->columnSpanFull(),
                                                default => Forms\Components\TextInput::make($path)
                                                    ->label($d['label'])
                                                    ->placeholder($d['placeholder'] ?? null)
                                                    ->maxLength(255),
                                            };

                                            $groupFields[] = $field;
                                        }

                                        // 同一 group 放 1 行 (3 列网格)
                                        $fields[] = Forms\Components\Grid::make(3)->schema($groupFields);
                                    }

                                    return $fields;
                                })
                                ->visible(fn (Forms\Get $get) => filled($get('place_type'))),

                            // ====== 通用：评分 / 去过 / 状态 ======
                            Forms\Components\Section::make('状态')
                                ->collapsible()
                                ->schema([
                                    Forms\Components\Grid::make(3)->schema([
                                        Forms\Components\Select::make('rating')
                                            ->label('评分（5 档）')
                                            ->options(\App\Models\Place::RATING_LABELS)
                                            ->placeholder('未评'),

                                        Forms\Components\Toggle::make('is_visited')
                                            ->label('已去过')
                                            ->inline(false),

                                        Forms\Components\Toggle::make('is_wishlist')
                                            ->label('种草/想去')
                                            ->inline(false),
                                    ]),

                                    Forms\Components\Grid::make(3)->schema([
                                        Forms\Components\DatePicker::make('visited_at')
                                            ->label('上次去过时间'),
                                        Forms\Components\TextInput::make('visit_count')
                                            ->label('去过次数')
                                            ->numeric()
                                            ->default(0),
                                        Forms\Components\Toggle::make('is_public')
                                            ->label('公开（可分享）')
                                            ->default(true)
                                            ->inline(false),
                                    ]),
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

    /**
     * 编辑时把 place_attributes 加载到 form 的 attributes.* 字段
     */
    public static function mutateFormDataBeforeFill(array $data): array
    {
        if (! isset($data['id'])) return $data;

        $attrs = \App\Models\PlaceAttribute::where('place_id', $data['id'])
            ->orderBy('sort')
            ->get();

        $data['attributes'] = [];
        foreach ($attrs as $a) {
            $value = match ($a->value_type) {
                'json','array' => json_decode((string) $a->attribute_value, true) ?? [],
                'bool' => (bool) $a->attribute_value,
                default => $a->attribute_value,
            };
            $data['attributes'][$a->attribute_key] = $value;
        }
        return $data;
    }

    /**
     * 保存前从 form data 里移除 attributes (不写入 places 表)
     */
    public static function mutateFormDataBeforeSave(array $data): array
    {
        unset($data['attributes']);
        return $data;
    }

    /**
     * 保存后写入 place_attributes 关联表
     */
    public static function afterSave(\Illuminate\Database\Eloquent\Model $record, array $data): void
    {
        static::syncAttributes($record, request()->input('attributes', []));
    }

    public static function afterCreate(\Illuminate\Database\Eloquent\Model $record, array $data): void
    {
        static::syncAttributes($record, request()->input('attributes', []));
    }

    /**
     * 把 form attributes.* 同步到 place_attributes 表
     * - 删掉不再填的
     * - 新增新的
     * - 更新已有的
     */
    protected static function syncAttributes(\App\Models\Place $place, array $attrs): void
    {
        $type = $place->place_type;
        $defs = collect(\App\Models\Place::TYPE_ATTRIBUTES[$type] ?? []);
        $validKeys = $defs->pluck('key')->all();

        // 1. 删掉这个 place 已有但本次没填的 (避免脏数据)
        $existing = \App\Models\PlaceAttribute::where('place_id', $place->id)->get();
        $incomingKeys = array_keys($attrs);
        foreach ($existing as $e) {
            if (! in_array($e->attribute_key, $incomingKeys, true)) {
                $e->delete();
            }
        }

        // 2. upsert 每个 attribute
        $sort = 0;
        foreach ($attrs as $key => $value) {
            if (! in_array($key, $validKeys, true)) continue; // 跳过非法 key
            if ($value === null || $value === '' || $value === [] ) continue; // 跳过空

            $def = $defs->firstWhere('key', $key);
            $valueType = match ($def['type'] ?? 'text') {
                'number' => is_int($value + 0) || $value === (string)(int)$value ? 'int' : 'float',
                'toggle' => 'bool',
                'repeater','checkbox-list' => 'array',
                default => 'string',
            };
            $storedValue = match ($valueType) {
                'int'   => (string)(int) $value,
                'float' => (string)(float) $value,
                'bool'  => $value ? '1' : '0',
                'array' => is_array($value) ? json_encode($value, JSON_UNESCAPED_UNICODE) : (string) $value,
                default => (string) $value,
            };

            \App\Models\PlaceAttribute::updateOrCreate(
                ['place_id' => $place->id, 'attribute_key' => $key],
                [
                    'attribute_value' => $storedValue,
                    'value_type'      => $valueType,
                    'is_system'       => true,
                    'display_label'   => $def['label'] ?? null,
                    'display_group'   => $def['group'] ?? null,
                    'input_type'      => $def['type'] ?? 'text',
                    'unit'            => $def['unit'] ?? null,
                    'sort'            => $sort * 10,
                ],
            );
            $sort++;
        }
    }

    public static function table(Table $table): Table
    {
        // Linear 紧凑表格：mono ID + sans body
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
                    ->weight('medium')
                    ->size('lg')
                    ->wrap(),

                Tables\Columns\TextColumn::make('category.name')
                    ->label('分类')
                    ->formatStateUsing(fn ($state, $record) => ($record->category?->icon ? $record->category->icon . ' · ' : '') . ($state ?? '—'))
                    ->fontFamily('mono')
                    ->size('xs')
                    ->color('gray')
                    ->toggleable(),

                Tables\Columns\TextColumn::make('place_type')
                    ->label('类型')
                    ->badge()
                    ->color(fn ($state) => match($state) {
                        'self_drive' => 'primary',
                        'camping' => 'success',
                        'play_water' => 'info',
                        'food' => 'warning',
                        'photo' => 'gray',
                        'hiking' => 'success',
                        'paddle' => 'info',
                        'sunrise_sunset' => 'warning',
                        default => 'gray',
                    })
                    ->formatStateUsing(function ($state) {
                        if (! $state) return '—';
                        $types = \App\Models\Place::PLACE_TYPES;
                        $t = $types[$state] ?? null;
                        return $t ? ($t['icon'] . ' ' . $t['label']) : $state;
                    })
                    ->size('sm')
                    ->alignCenter(),

                Tables\Columns\TextColumn::make('city')
                    ->label('城市')
                    ->searchable()
                    ->fontFamily('mono')
                    ->size('xs')
                    ->toggleable(),

                Tables\Columns\TextColumn::make('rating')
                    ->label('评分')
                    ->formatStateUsing(function ($state) {
                        $labels = \App\Models\Place::RATING_LABELS;
                        if (! $state) return '—';
                        $r = $labels[$state] ?? null;
                        return $r ? $r['label'] : (string) $state;
                    })
                    ->fontFamily('sans')
                    ->size('sm')
                    ->alignCenter(),

                Tables\Columns\TextColumn::make('has_parking')
                    ->label('停车')
                    ->formatStateUsing(fn ($state) => $state ? '可' : '—')
                    ->fontFamily('mono')
                    ->size('xs')
                    ->alignCenter()
                    ->toggleable(),

                Tables\Columns\TextColumn::make('has_ticket')
                    ->label('门票')
                    ->formatStateUsing(fn ($state) => $state ? '收费' : '免费')
                    ->fontFamily('mono')
                    ->size('xs')
                    ->alignCenter()
                    ->toggleable(),

                Tables\Columns\TextColumn::make('is_visited')
                    ->label('已去')
                    ->formatStateUsing(fn ($state) => $state ? 'YES' : '—')
                    ->fontFamily('mono')
                    ->size('xs')
                    ->alignCenter(),

                Tables\Columns\TextColumn::make('is_wishlist')
                    ->label('种草')
                    ->formatStateUsing(fn ($state) => $state ? 'YES' : '—')
                    ->fontFamily('mono')
                    ->size('xs')
                    ->alignCenter(),

                Tables\Columns\TextColumn::make('is_public')
                    ->label('状态')
                    ->formatStateUsing(fn ($state) => $state ? '上架' : '下架')
                    ->fontFamily('mono')
                    ->size('xs')
                    ->alignCenter(),

                Tables\Columns\TextColumn::make('created_at')
                    ->label('收藏于')
                    ->dateTime('Y-m-d')
                    ->fontFamily('mono')
                    ->size('xs')
                    ->color('gray')
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
                    ->label('类型 (8 大类)')
                    ->options(collect(\App\Models\Place::PLACE_TYPES)->mapWithKeys(fn ($v, $k) => [$k => ($v['icon'] ?? '') . ' ' . $v['label']])->toArray())
                    ->multiple(),

                Tables\Filters\Filter::make('has_parking')
                    ->label('可停车')
                    ->query(fn (Builder $query) => $query->whereHas('attributes', fn ($a) => $a->where('attribute_key', 'parking')
                        ->where('attribute_value', 'not like', '%无停车%')))
                    ->toggle(),

                Tables\Filters\Filter::make('free_entry')
                    ->label('免门票')
                    ->query(fn (Builder $query) => $query->whereHas('attributes', fn ($a) => $a->where('attribute_key', 'ticket')
                        ->where('attribute_value', 'like', '%免费%')))
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
                Tables\Actions\ActionGroup::make([
                    Tables\Actions\ViewAction::make()->label('查看'),
                    Tables\Actions\EditAction::make()->label('编辑'),
                    Tables\Actions\Action::make('togglePublish')
                        ->label(fn ($record) => $record->is_public ? '下架' : '上架')
                        ->requiresConfirmation()
                        ->modalHeading(fn ($record) => $record->is_public ? '下架该地点？' : '上架该地点？')
                        ->modalDescription(fn ($record) => $record->is_public ? '前台将不再展示，公开分享链接也会失效' : '前台将立即展示给所有用户')
                        ->action(function ($record) {
                            $record->update(['is_public' => ! $record->is_public]);
                        }),
                    Tables\Actions\DeleteAction::make()->label('删除'),
                ])
                ->icon('heroicon-o-ellipsis-horizontal')
                ->iconPosition('after')
                ->label('操作'),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\BulkAction::make('publish')
                        ->label('批量上架')
                        ->action(fn ($records) => $records->each->update(['is_public' => true])),
                    Tables\Actions\BulkAction::make('unpublish')
                        ->label('批量下架')
                        ->action(fn ($records) => $records->each->update(['is_public' => false])),
                    Tables\Actions\DeleteBulkAction::make()->label('批量删除'),
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
