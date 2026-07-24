<?php

namespace App\Filament\Resources;

use App\Filament\Resources\ContentResource\Pages;
use App\Models\Content;
use App\Models\ContentTypeDefinition;
use App\Models\Media;
use App\Models\Place;
use App\Models\User;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Forms\Get;
use Filament\Forms\Set;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Str;

/**
 * Content 资源 — 8 大类内容贴主管理
 *
 *  - 列表: 8 大类 tabs + 状态 tabs
 *  - 表单: 4 tab (基础 / 封面与媒体 / 关联地点 / 类型专属)
 *           类型专属 tab 根据 type 字段动态渲染对应 1:1 子表
 */
class ContentResource extends Resource
{
    protected static ?string $model = Content::class;

    protected static ?string $navigationIcon = 'heroicon-o-document-text';

    protected static ?string $navigationLabel = '内容';

    protected static ?string $navigationGroup = '内容管理';

    protected static ?string $modelLabel = '内容';

    protected static ?string $pluralModelLabel = '内容';

    protected static ?int $navigationSort = 11;

    public static function form(Form $form): Form
    {
        return $form->schema([
            Forms\Components\Tabs::make()->tabs([
                self::basicsTab(),
                self::coverAndMediaTab(),
                self::placesTab(),
                self::notesTab(),
                self::subtableTab(),
            ])->columnSpanFull(),
        ]);
    }

    // ---------- Tab 1: 基础信息 ----------
    protected static function basicsTab(): Forms\Components\Tabs\Tab
    {
        return Forms\Components\Tabs\Tab::make('基础信息')
            ->icon('heroicon-o-information-circle')
            ->schema([
                Forms\Components\Hidden::make('user_id')->default(fn () => auth()->id()),

                Forms\Components\Grid::make(2)->schema([
                    Forms\Components\Select::make('type')
                        ->label('内容类型（8 大类）')
                        ->required()
                        ->options(collect(Content::TYPES)
                            ->mapWithKeys(fn ($v, $k) => [$k => ($v['icon'] ?? '') . ' ' . $v['label']])->toArray())
                        ->live()
                        ->afterStateUpdated(function (Set $set, ?string $state) {
                            // 切换 type: 清空所有 subtable + 强制把当前 type 的 array 字段初始化为 [] (避免 Livewire 把 CheckboxList 当 boolean 绑)
                            foreach (Content::TYPES as $key => $meta) {
                                $set($meta['subtable'], null);
                            }
                            $set('places', []);
                            $set('notes', []);
                            $set('gallery', []);
                            $set('videos', []);
                            if ($state && isset(Content::TYPES[$state])) {
                                $subKey = Content::TYPES[$state]['subtable'];
                                $set($subKey, [
                                    'best_season'    => [],
                                    'gear_checklist' => [],
                                    'safety_notes'   => [],
                                ]);
                            }
                        })
                        ->helperText('切换类型会清空下方类型专属字段与关联地点'),

                    Forms\Components\Select::make('rating_label')
                        ->label('评分（5 档）')
                        ->options(collect(Content::RATING_LABELS)
                            ->mapWithKeys(fn ($v, $k) => [$k => $v['label']])->toArray())
                        ->placeholder('未评'),
                ]),

                Forms\Components\TextInput::make('title')
                    ->label('标题')
                    ->required()
                    ->maxLength(200)
                    ->live(onBlur: true)
                    ->afterStateUpdated(function (Set $set, ?string $state, Get $get) {
                        if (empty($get('slug')) && $state) {
                            $set('slug', auth()->id() . '-' . Str::slug($state));
                        }
                    })
                    ->columnSpanFull(),

                Forms\Components\Grid::make(2)->schema([
                    Forms\Components\TextInput::make('slug')->label('Slug')->maxLength(200),
                    Forms\Components\TextInput::make('subtitle')->label('副标题')->maxLength(200),
                ]),

                Forms\Components\Textarea::make('summary')
                    ->label('简介（卡片用，1-2 句话）')
                    ->rows(2)
                    ->maxLength(500)
                    ->columnSpanFull(),

                Forms\Components\RichEditor::make('description')
                    ->label('详情（长文）')
                    ->toolbarButtons(['bold', 'italic', 'bulletList', 'orderedList', 'h2', 'h3', 'link', 'blockquote'])
                    ->columnSpanFull(),

                Forms\Components\Section::make('状态')
                    ->collapsible()
                    ->schema([
                        Forms\Components\Grid::make(3)->schema([
                            Forms\Components\Toggle::make('is_public')->label('公开（可分享）')->default(true)->inline(false),
                            Forms\Components\Toggle::make('is_visited')->label('已去过')->default(false)->inline(false),
                            Forms\Components\Toggle::make('is_wishlist')->label('种草/想去')->default(false)->inline(false),
                        ]),
                        Forms\Components\Grid::make(2)->schema([
                            Forms\Components\DateTimePicker::make('published_at')->label('发布时间')->native(false),
                            Forms\Components\DatePicker::make('visited_at')->label('上次去过时间')->native(false),
                        ]),
                    ]),

                Forms\Components\Section::make('首页推荐（§ 02 本期精选）')
                    ->collapsible()
                    ->collapsed(fn (string $context) => $context === 'create')
                    ->description('勾选后这条内容会出现在首页 § 02 — 本期精选 区域。sort 小的排前，可无限量。')
                    ->schema([
                        Forms\Components\Toggle::make('is_picked')
                            ->label('推送到首页')
                            ->default(false)
                            ->inline(false)
                            ->dehydrated(false) // 不写 contents 表，写 content_picks
                            ->afterStateHydrated(function ($component, $state, $record) {
                                $component->state($record?->pick ? true : false);
                            }),
                        Forms\Components\TextInput::make('pick_sort')
                            ->label('排序 (小=靠前)')
                            ->numeric()
                            ->default(0)
                            ->dehydrated(false)
                            ->afterStateHydrated(function ($component, $state, $record) {
                                $component->state($record?->pick?->sort ?? 0);
                            })
                            ->visible(fn ($get) => $get('is_picked')),
                        Forms\Components\TextInput::make('pick_note')
                            ->label('编辑备注 (仅 admin 可见)')
                            ->maxLength(200)
                            ->dehydrated(false)
                            ->afterStateHydrated(function ($component, $state, $record) {
                                $component->state($record?->pick?->note ?? '');
                            })
                            ->visible(fn ($get) => $get('is_picked')),
                    ]),
            ]);
    }

    // ---------- Tab 2: 封面与媒体 ----------
    protected static function coverAndMediaTab(): Forms\Components\Tabs\Tab
    {
        return Forms\Components\Tabs\Tab::make('封面与媒体')
            ->icon('heroicon-o-photo')
            ->schema([
                Forms\Components\Section::make('封面图')
                    ->description('推荐 16:9 / 1200×800 / 5MB 以内')
                    ->schema([
                        Forms\Components\FileUpload::make('cover_upload')
                            ->label('封面图（必填）')
                            ->image()
                            ->disk('public')
                            ->directory('contents/covers')
                            ->maxSize(5120)
                            ->afterStateUpdated(function ($state, Set $set) {
                                if ($state) {
                                    $first = is_array($state) ? $state[0] : $state;
                                    $set('cover_media_path', $first);
                                }
                            })
                            ->columnSpanFull(),
                    ]),

                Forms\Components\Section::make('相册')
                    ->description('点击 + 添加图片，可拖动排序。允许为空 — 没图也能发布。')
                    ->schema([
                        Forms\Components\Repeater::make('gallery')
                            ->label('')
                            ->defaultItems(0)
                            ->addActionLabel('+ 添加一张图片')
                            ->schema([
                                Forms\Components\FileUpload::make('path')
                                    ->label('图片')
                                    ->disk('public')
                                    ->directory('contents/gallery')
                                    ->image()
                                    ->maxSize(5120)
                                    ->columnSpan(2),
                                Forms\Components\TextInput::make('caption')
                                    ->label('说明')
                                    ->maxLength(200)
                                    ->columnSpan(3),
                            ])
                            ->columns(5)
                            ->collapsible()
                            ->itemLabel(fn (array $state) => $state['caption'] ?? '图片')
                            ->columnSpanFull(),
                    ]),

                Forms\Components\Section::make('视频集')
                    ->description('点击 + 添加视频链接或上传。允许为空。')
                    ->schema([
                        Forms\Components\Repeater::make('videos')
                            ->label('')
                            ->defaultItems(0)
                            ->addActionLabel('+ 添加一个视频')
                            ->schema([
                                Forms\Components\TextInput::make('url')
                                    ->label('视频链接 / 上传路径')
                                    ->maxLength(500)
                                    ->columnSpan(3),
                                Forms\Components\TextInput::make('caption')
                                    ->label('说明')
                                    ->maxLength(200)
                                    ->columnSpan(2),
                            ])
                            ->columns(5)
                            ->collapsible()
                            ->itemLabel(fn (array $state) => $state['caption'] ?? '视频')
                            ->columnSpanFull(),
                    ]),
            ]);
    }

    // ---------- Tab 3: 关联地点 ----------
    protected static function placesTab(): Forms\Components\Tabs\Tab
    {
        return Forms\Components\Tabs\Tab::make('关联地点')
            ->icon('heroicon-o-map-pin')
            ->schema([
                Forms\Components\Placeholder::make('place_binding_hint')
                    ->label('')
                    ->content(function (Get $get) {
                        $type = $get('type');
                        $meta = Content::TYPES[$type] ?? null;
                        if (! $meta) {
                            return '请先选择内容类型';
                        }
                        if ($meta['place_binding'] === 'multiple') {
                            return '多地点类型：' . $meta['icon'] . ' ' . $meta['label'] . ' — 可添加多个地点，按顺序串联';
                        }
                        return '单地点类型：' . $meta['icon'] . ' ' . $meta['label'] . ' — 只能关联 1 个地点';
                    }),

                Forms\Components\Repeater::make('places')
                    ->label('地点列表')
                    ->defaultItems(0)
                    ->addActionLabel('+ 关联一个地点')
                    ->schema([
                        Forms\Components\Select::make('place_id')
                            ->label('地点')
                            ->options(function () {
                                return Place::query()
                                    ->orderBy('id', 'desc')
                                    ->limit(300)
                                    ->get()
                                    ->mapWithKeys(fn ($p) => [
                                        $p->id => '#' . $p->id . ' · ' . $p->name . ($p->city ? ' · ' . $p->city : ''),
                                    ])->toArray();
                            })
                            ->searchable()
                            ->live()
                            ->columnSpan(3),

                        Forms\Components\TextInput::make('notes')
                            ->label('备注')
                            ->maxLength(500)
                            ->placeholder('此处停留 / 玩法 / 注意事项')
                            ->columnSpan(2),
                    ])
                    ->columns(5)
                    ->collapsible()
                    ->itemLabel(fn (array $state): ?string =>
                        isset($state['place_id'])
                            ? '地点 #' . $state['place_id']
                            : '新地点')
                    ->columnSpanFull()
                    ->helperText('多地点类型：可添加多个；单地点类型：留一个即可'),

                Forms\Components\Actions::make([
                    Forms\Components\Actions\Action::make('createNewPlace')
                        ->label('+ 在位置库新建地点（跳出）')
                        ->url(fn () => route('filament.admin.resources.places.create'))
                        ->openUrlInNewTab()
                        ->color('gray'),
                ])->columnSpanFull(),
            ]);
    }

    // ---------- Tab 3.5: 关联笔记 (Phase 19) ----------
    protected static function notesTab(): Forms\Components\Tabs\Tab
    {
        return Forms\Components\Tabs\Tab::make('关联笔记')
            ->icon('heroicon-o-book-open')
            ->badge(fn (Get $get) => is_array($get('notes')) ? count($get('notes')) : 0)
            ->badgeColor('primary')
            ->schema([
                Forms\Components\Placeholder::make('notes_hint')
                    ->label('')
                    ->content('关联已收录的小红书 / 大众点评 / 马蜂窝 等笔记作为参考。在此处选择已有笔记，或粘贴新链接快速建笔记。'),

                Forms\Components\Repeater::make('notes')
                    ->label('笔记列表')
                    ->defaultItems(0)  // 关联笔记是非必须的，0 默认空
                    ->schema([
                        Forms\Components\Select::make('note_id')
                            ->label('笔记')
                            ->options(function () {
                                return \App\Models\Note::query()
                                    ->where('user_id', auth()->id())
                                    ->orderBy('id', 'desc')
                                    ->limit(300)
                                    ->get()
                                    ->mapWithKeys(fn ($n) => [
                                        $n->id => '#' . $n->id . ' · ' . \Illuminate\Support\Str::limit($n->title, 40) . ($n->source ? ' · [' . $n->source . ']' : ''),
                                    ])->toArray();
                            })
                            ->searchable()
                            ->live()
                            ->columnSpan(3)
                            ->helperText('先在「笔记/小红书」里建好笔记，再回到这里关联'),

                        Forms\Components\Select::make('role')
                            ->label('作用')
                            ->options([
                                'reference'    => '参考',
                                'inspiration'  => '灵感来源',
                                'detailed'     => '详细内容',
                            ])
                            ->default('reference')
                            ->columnSpan(1),
                    ])
                    ->columns(4)
                    ->collapsible()
                    ->itemLabel(fn (array $state): ?string =>
                        isset($state['note_id'])
                            ? '笔记 #' . $state['note_id']
                            : '新关联')
                    ->columnSpanFull()
                    ->addActionLabel('+ 关联一条笔记')
                    ->reorderable(),

                Forms\Components\Actions::make([
                    Forms\Components\Actions\Action::make('createNewNote')
                        ->label('+ 新建笔记（跳出）')
                        ->url(fn () => route('filament.admin.resources.notes.create'))
                        ->openUrlInNewTab()
                        ->color('gray'),
                ])->columnSpanFull(),
            ]);
    }

    // ---------- Tab 4: 类型专属 (动态) ----------
    protected static function subtableTab(): Forms\Components\Tabs\Tab
    {
        return Forms\Components\Tabs\Tab::make('类型专属信息')
            ->icon('heroicon-o-sparkles')
            ->schema(function (Get $get) {
                $type = $get('type');
                if (! $type) {
                    return [
                        Forms\Components\Placeholder::make('no_type')
                            ->label('')
                            ->content('先选择「内容类型」'),
                    ];
                }
                $meta = Content::TYPES[$type] ?? null;
                $subtable = $meta['subtable'] ?? null;

                if (! $subtable) {
                    return [
                        Forms\Components\Placeholder::make('no_sub')
                            ->label('')
                            ->content('该类型暂无专属字段'),
                    ];
                }

                return self::fieldsForType($type);
            })
            ->visible(fn (Get $get) => filled($get('type')));
    }

    /**
     * 8 大类各自专属字段 (admin form)
     */
    protected static function fieldsForType(string $type): array
    {
        $sharedSeasons = Forms\Components\CheckboxList::make('best_season')
            ->label('最佳季节')
            ->options(['春' => '春', '夏' => '夏', '秋' => '秋', '冬' => '冬', '四季' => '四季'])
            ->columns(5)
            ->statePath("{$type}.best_season");

        $sharedGear = Forms\Components\TagsInput::make("{$type}.gear_checklist")
            ->label('装备清单')
            ->placeholder('回车添加')
            ->columnSpanFull();

        $sharedSafety = Forms\Components\TagsInput::make("{$type}.safety_notes")
            ->label('安全提示')
            ->placeholder('回车添加')
            ->columnSpanFull();

        return match ($type) {
            'self_drive' => [
                Forms\Components\Section::make('基本信息')
                    ->schema([
                        Forms\Components\Grid::make(4)->schema([
                            Forms\Components\TextInput::make("$type.distance_km")->label('距离')->numeric()->suffix('km'),
                            Forms\Components\TextInput::make("$type.duration_minutes")->label('预计时长')->numeric()->suffix('分钟'),
                            Forms\Components\TextInput::make("$type.altitude_meters")->label('最高海拔')->numeric()->suffix('m'),
                            Forms\Components\Select::make("$type.difficulty")->label('难度')
                                ->options(['easy' => '轻松', 'moderate' => '中等', 'hard' => '困难']),
                        ]),
                        Forms\Components\Grid::make(2)->schema([
                            Forms\Components\Select::make("$type.road_condition")->label('路况')
                                ->options(['paved' => '全程铺装', 'mostly_paved' => '大部分铺装', 'mixed' => '混合', 'offroad' => '越野']),
                            Forms\Components\TextInput::make("$type.two_foot_route_id")->label('两步路线路编号'),
                        ]),
                        $sharedSeasons,
                    ]),
                Forms\Components\Section::make('沿途信息')
                    ->schema([
                        Forms\Components\TagsInput::make("$type.gas_stations")->label('加油站')->placeholder('加油站 + km 标记')->columnSpanFull(),
                        Forms\Components\TagsInput::make("$type.waypoints")->label('途经点')->placeholder('途经点 + 经纬度')->columnSpanFull(),
                    ]),
                Forms\Components\Section::make('装备 & 安全')->schema([$sharedGear, $sharedSafety]),
            ],

            'hiking' => [
                Forms\Components\Section::make('基本信息')
                    ->schema([
                        Forms\Components\Grid::make(4)->schema([
                            Forms\Components\TextInput::make("$type.distance_km")->label('距离')->numeric()->suffix('km'),
                            Forms\Components\TextInput::make("$type.duration_minutes")->label('预计时长')->numeric()->suffix('分钟'),
                            Forms\Components\TextInput::make("$type.altitude_meters")->label('最高海拔')->numeric()->suffix('m'),
                            Forms\Components\TextInput::make("$type.elevation_gain")->label('累计爬升')->numeric()->suffix('m'),
                        ]),
                        Forms\Components\Grid::make(3)->schema([
                            Forms\Components\Select::make("$type.difficulty")->label('难度')
                                ->options(['easy' => '轻松', 'moderate' => '中等', 'hard' => '困难', 'expert' => '专业']),
                            Forms\Components\Select::make("$type.route_type")->label('线路类型')
                                ->options(['loop' => '环形', 'out_back' => '往返', 'one_way' => '单程']),
                            Forms\Components\TextInput::make("$type.two_foot_route_id")->label('两步路线路编号'),
                        ]),
                        $sharedSeasons,
                    ]),
                Forms\Components\Section::make('途经点')
                    ->schema([
                        Forms\Components\TagsInput::make("$type.waypoints")->label('途经点')->placeholder('途经点 + 经纬度')->columnSpanFull(),
                    ]),
                Forms\Components\Section::make('装备 & 安全')->schema([$sharedGear, $sharedSafety]),
            ],

            'play_water' => [
                Forms\Components\Section::make('基本信息')
                    ->schema([
                        Forms\Components\Grid::make(3)->schema([
                            Forms\Components\Select::make("$type.water_type")->label('水域')
                                ->options(['lake' => '湖', 'river' => '河', 'sea' => '海', 'pool' => '潭', 'reservoir' => '水库']),
                            Forms\Components\TextInput::make("$type.water_depth")->label('水深')->placeholder('例: 1.5m'),
                            Forms\Components\TextInput::make("$type.ticket")->label('门票')->placeholder('例: 30元/人'),
                        ]),
                        Forms\Components\Grid::make(4)->schema([
                            Forms\Components\Toggle::make("$type.is_swimmable")->label('可游泳')->inline(false),
                            Forms\Components\Toggle::make("$type.is_free")->label('免费')->inline(false),
                            Forms\Components\Toggle::make("$type.has_lifeguard")->label('有救生员')->inline(false),
                            Forms\Components\Select::make("$type.parking")->label('停车')
                                ->options(['free' => '免费', 'paid' => '收费', 'limited' => '有限', 'no' => '无']),
                        ]),
                        $sharedSeasons,
                    ]),
                Forms\Components\Section::make('装备 & 安全')->schema([$sharedGear, $sharedSafety]),
            ],

            'paddle' => [
                Forms\Components\Section::make('基本信息')
                    ->schema([
                        Forms\Components\Grid::make(3)->schema([
                            Forms\Components\TextInput::make("$type.water_depth")->label('水深'),
                            Forms\Components\Select::make("$type.water_current")->label('水流情况')
                                ->options(['calm' => '平静', 'mild' => '缓流', 'moderate' => '中流', 'strong' => '急流']),
                            Forms\Components\Select::make("$type.difficulty")->label('难度')
                                ->options(['easy' => '轻松', 'moderate' => '中等', 'hard' => '困难']),
                        ]),
                        Forms\Components\Grid::make(2)->schema([
                            Forms\Components\Toggle::make("$type.rental_available")->label('装备租赁')->inline(false),
                            Forms\Components\TextInput::make("$type.best_time")->label('最佳时间')->placeholder('上午 / 黄昏'),
                        ]),
                    ]),
                Forms\Components\Section::make('装备 & 安全')->schema([$sharedGear, $sharedSafety]),
            ],

            'photo' => [
                Forms\Components\Section::make('基本信息')
                    ->schema([
                        Forms\Components\Grid::make(2)->schema([
                            Forms\Components\TextInput::make("$type.best_time")->label('最佳时间')->placeholder('上午 / 黄昏 / 夜晚'),
                            Forms\Components\TextInput::make("$type.best_light")->label('最佳光影')->placeholder('顺光 / 黄金时刻'),
                        ]),
                        Forms\Components\Grid::make(4)->schema([
                            Forms\Components\TextInput::make("$type.viewpoint_count")->label('机位数量')->numeric(),
                            Forms\Components\Toggle::make("$type.is_drone_allowed")->label('可飞无人机')->inline(false),
                            Forms\Components\Toggle::make("$type.permit_required")->label('需要许可')->inline(false),
                            Forms\Components\Select::make("$type.parking")->label('停车')
                                ->options(['free' => '免费', 'paid' => '收费', 'limited' => '有限', 'no' => '无']),
                        ]),
                        $sharedSeasons,
                    ]),
                Forms\Components\Section::make('装备')->schema([$sharedGear]),
            ],

            'food' => [
                Forms\Components\Section::make('基本信息')
                    ->schema([
                        Forms\Components\Grid::make(3)->schema([
                            Forms\Components\TextInput::make("$type.price_per_person")->label('人均')->numeric()->prefix('¥'),
                            Forms\Components\TextInput::make("$type.cuisine_type")->label('菜系')->placeholder('川菜 / 西餐 / 咖啡'),
                            Forms\Components\TextInput::make("$type.business_hours")->label('营业时间')->placeholder('09:00-22:00'),
                        ]),
                        Forms\Components\Grid::make(2)->schema([
                            Forms\Components\TextInput::make("$type.reservation")->label('预订方式')->placeholder('电话 / 微信 / 大众点评'),
                            Forms\Components\Select::make("$type.parking")->label('停车')
                                ->options(['free' => '免费', 'paid' => '收费', 'limited' => '有限', 'no' => '无']),
                        ]),
                        Forms\Components\TextInput::make("$type.contact")->label('联系方式'),
                    ]),
                Forms\Components\Section::make('菜品')
                    ->schema([
                        Forms\Components\TagsInput::make("$type.signature_dishes")->label('招牌菜')->placeholder('招牌菜名')->columnSpanFull(),
                    ]),
            ],

            'camping' => [
                Forms\Components\Section::make('基本信息')
                    ->schema([
                        Forms\Components\Grid::make(5)->schema([
                            Forms\Components\TextInput::make("$type.altitude_meters")->label('海拔')->numeric()->suffix('m'),
                            Forms\Components\Toggle::make("$type.is_free")->label('免费')->inline(false),
                            Forms\Components\Toggle::make("$type.has_water")->label('有水源')->inline(false),
                            Forms\Components\Toggle::make("$type.has_toilet")->label('有厕所')->inline(false),
                            Forms\Components\Toggle::make("$type.fire_allowed")->label('可明火')->inline(false),
                        ]),
                        Forms\Components\Grid::make(2)->schema([
                            Forms\Components\Toggle::make("$type.has_signal")->label('有信号')->inline(false),
                            Forms\Components\Select::make("$type.parking")->label('停车')
                                ->options(['free' => '免费', 'paid' => '收费', 'limited' => '有限', 'no' => '无']),
                        ]),
                        $sharedSeasons,
                    ]),
                Forms\Components\Section::make('装备 & 安全')->schema([$sharedGear, $sharedSafety]),
            ],

            'sunrise_sunset' => [
                Forms\Components\Section::make('基本信息')
                    ->schema([
                        Forms\Components\Grid::make(4)->schema([
                            Forms\Components\Select::make("$type.direction")->label('方位')
                                ->options(['east' => '东 (日出)', 'west' => '西 (日落)', 'both' => '都能看']),
                            Forms\Components\TextInput::make("$type.best_time")->label('最佳时间')->placeholder('比日落早 30 分钟'),
                            Forms\Components\TextInput::make("$type.viewpoint_count")->label('机位数量')->numeric(),
                            Forms\Components\Select::make("$type.difficulty")->label('抵达难度')
                                ->options(['easy' => '轻松', 'moderate' => '中等', 'hard' => '困难']),
                        ]),
                        Forms\Components\Grid::make(2)->schema([
                            Forms\Components\Toggle::make("$type.is_drone_allowed")->label('可飞无人机')->inline(false),
                            Forms\Components\Select::make("$type.parking")->label('停车')
                                ->options(['free' => '免费', 'paid' => '收费', 'limited' => '有限', 'no' => '无']),
                        ]),
                        $sharedSeasons,
                    ]),
                Forms\Components\Section::make('装备 & 安全')->schema([$sharedGear, $sharedSafety]),
            ],

            default => [
                Forms\Components\Placeholder::make('unknown_type')
                    ->label('')
                    ->content('未知类型'),
            ],
        };
    }

    // ============= mutate hooks =============
    // 注意：mutateFormDataBeforeFill / mutateFormDataBeforeSave / afterCreate / afterSave
    //  都移到 EditContent / CreateContent Page 上 (Filament 不会自动 call Resource 上的同名 static)

    // ============= table =============

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

                Tables\Columns\ImageColumn::make('coverMedia.path')
                    ->label('封面')
                    ->getStateUsing(function ($record) {
                        $m = $record->coverMedia;
                        if ($m) return $m->thumbnail_url ?: $m->url;
                        // fallback 第一张 gallery
                        $first = $record->gallery->first();
                        return $first ? ($first->thumbnail_url ?: $first->url) : null;
                    })
                    ->height(40)
                    ->width(60)
                    ->extraImgAttributes(['style' => 'object-fit: cover;'])
                    ->defaultImageUrl(url('/images/placeholder.png')),

                Tables\Columns\TextColumn::make('title')
                    ->label('标题')
                    ->searchable(['title', 'subtitle', 'summary'])
                    ->weight('medium')
                    ->size('sm')
                    ->wrap()
                    ->extraAttributes(['style' => 'min-width: 180px; max-width: 280px;']),

                Tables\Columns\TextColumn::make('type')
                    ->label('类型')
                    ->badge()
                    ->color(fn ($state) => match($state) {
                        'self_drive', 'hiking' => 'primary',
                        'play_water', 'paddle' => 'info',
                        'photo' => 'gray',
                        'food' => 'warning',
                        'camping' => 'success',
                        'sunrise_sunset' => 'danger',
                        default => 'gray',
                    })
                    ->formatStateUsing(fn ($state) => Content::TYPES[$state]['icon'] . ' ' . (Content::TYPES[$state]['label'] ?? $state))
                    ->size('sm'),

                Tables\Columns\ToggleColumn::make('is_picked')
                    ->label('推首页')
                    ->getStateUsing(fn ($record) => (bool) $record->pick)
                    ->updateStateUsing(function ($record, $state) {
                        if ($state) {
                            // 推到首页 (本期精选 = sort=0 or 1)
                            \App\Models\ContentPick::updateOrCreate(
                                ['content_id' => $record->id],
                                ['is_featured' => true, 'sort' => 0]
                            );
                        } else {
                            \App\Models\ContentPick::where('content_id', $record->id)->delete();
                        }
                    })
                    ->onIcon('heroicon-s-star')
                    ->offIcon('heroicon-o-star')
                    ->onColor('warning')
                    ->offColor('gray')
                    ->tooltip(fn ($record) => $record->pick ? '已推首页 · 点击取消' : '未推首页 · 点击推到首页精选'),

                Tables\Columns\TextColumn::make('rating_label')
                    ->label('评分')
                    ->badge()
                    ->color(fn ($state) => match ($state) {
                        'amazing' => 'danger',
                        'great' => 'success',
                        'nice' => 'info',
                        'npc' => 'gray',
                        'terrible' => 'warning',
                        default => 'gray',
                    })
                    ->formatStateUsing(fn ($state) => $state ? (Content::RATING_LABELS[$state]['label'] ?? $state) : '未评')
                    ->alignCenter(),

                Tables\Columns\TextColumn::make('votes_count')
                    ->label('投票')
                    ->counts('votes')
                    ->badge()
                    ->color('warning')
                    ->alignCenter()
                    ->formatStateUsing(fn ($state, $record) => $state . ($state > 0 ? ' · ' . ($record->vote_avg ?? 0) : ''))
                    ->toggleable(),

                Tables\Columns\TextColumn::make('places_count')
                    ->label('地点')
                    ->counts('places')
                    ->badge()
                    ->color('info')
                    ->alignCenter(),

                Tables\Columns\TextColumn::make('media_count')
                    ->label('媒体')
                    ->counts('media')
                    ->badge()
                    ->color('info')
                    ->alignCenter()
                    ->toggleable(),

                Tables\Columns\TextColumn::make('comments_count')
                    ->label('评论')
                    ->counts('comments')
                    ->badge()
                    ->color('info')
                    ->alignCenter()
                    ->toggleable(),

                Tables\Columns\ToggleColumn::make('is_public')
                    ->label('上架')
                    ->onIcon('heroicon-o-eye')
                    ->offIcon('heroicon-o-eye-slash')
                    ->onColor('success')
                    ->offColor('gray')
                    ->tooltip(fn ($record) => $record->is_public ? '已公开 · 点击改为草稿' : '草稿态 · 点击发布'),

                Tables\Columns\ToggleColumn::make('is_visited')
                    ->label('已去')
                    ->onColor('info')
                    ->offColor('gray')
                    ->toggleable(),

                Tables\Columns\ToggleColumn::make('is_wishlist')
                    ->label('种草')
                    ->onColor('warning')
                    ->offColor('gray')
                    ->toggleable(),

                Tables\Columns\TextColumn::make('view_count')
                    ->label('浏览')
                    ->numeric()
                    ->alignCenter()
                    ->fontFamily('mono')
                    ->size('xs')
                    ->sortable()
                    ->toggleable(),

                Tables\Columns\TextColumn::make('created_at')
                    ->label('创建')
                    ->dateTime('Y-m-d')
                    ->fontFamily('mono')
                    ->size('xs')
                    ->color('gray')
                    ->sortable(),
            ])
            ->defaultSort('created_at', 'desc')
            ->filters([
                Tables\Filters\SelectFilter::make('type')
                    ->label('类型')
                    ->options(collect(Content::TYPES)->mapWithKeys(fn ($v, $k) => [$k => $v['icon'] . ' ' . $v['label']])->toArray())
                    ->multiple(),

                Tables\Filters\SelectFilter::make('rating_label')
                    ->label('评分')
                    ->options(collect(Content::RATING_LABELS)->mapWithKeys(fn ($v, $k) => [$k => $v['label']])->toArray())
                    ->multiple(),

                Tables\Filters\TernaryFilter::make('is_public')
                    ->label('上架')
                    ->placeholder('全部')
                    ->trueLabel('已上架')
                    ->falseLabel('已下架'),

                Tables\Filters\TernaryFilter::make('is_visited')
                    ->label('已去过'),

                Tables\Filters\TernaryFilter::make('is_wishlist')
                    ->label('种草中'),

                Tables\Filters\Filter::make('has_multiple_places')
                    ->label('多地点 (自驾/徒步)')
                    ->query(fn (Builder $q) => $q->whereIn('type', ['self_drive', 'hiking']))
                    ->toggle(),

                Tables\Filters\TrashedFilter::make(),
            ])
            ->actions([
                Tables\Actions\ActionGroup::make([
                    Tables\Actions\ViewAction::make()->label('查看'),
                    Tables\Actions\EditAction::make()->label('编辑'),
                    Tables\Actions\Action::make('togglePublish')
                        ->label(fn ($record) => $record->is_public ? '下架' : '上架')
                        ->icon(fn ($record) => $record->is_public ? 'heroicon-o-eye-slash' : 'heroicon-o-eye')
                        ->color(fn ($record) => $record->is_public ? 'warning' : 'success')
                        ->requiresConfirmation()
                        ->action(fn ($record) => $record->update(['is_public' => ! $record->is_public])),
                    Tables\Actions\DeleteAction::make()->label('删除'),
                ])->icon('heroicon-o-ellipsis-horizontal')->iconPosition('after')->label('操作'),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\BulkAction::make('publish')
                        ->label('批量上架')
                        ->icon('heroicon-o-eye')->color('success')
                        ->action(fn ($records) => $records->each->update(['is_public' => true]))
                        ->deselectRecordsAfterCompletion(),
                    Tables\Actions\BulkAction::make('unpublish')
                        ->label('批量下架')
                        ->icon('heroicon-o-eye-slash')->color('warning')
                        ->action(fn ($records) => $records->each->update(['is_public' => false]))
                        ->deselectRecordsAfterCompletion(),
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListContents::route('/'),
            'create' => Pages\CreateContent::route('/create'),
            'view' => Pages\ViewContent::route('/{record}'),
            'edit' => Pages\EditContent::route('/{record}/edit'),
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
