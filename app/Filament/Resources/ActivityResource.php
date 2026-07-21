<?php

namespace App\Filament\Resources;

use App\Filament\Resources\ActivityResource\Pages;
use App\Models\Activity;
use App\Models\User;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class ActivityResource extends Resource
{
    protected static ?string $model = Activity::class;

    protected static ?string $navigationIcon = 'heroicon-o-calendar-days';

    protected static ?string $navigationLabel = '活动';

    protected static ?string $modelLabel = '活动';

    protected static ?string $pluralModelLabel = '活动';

    protected static ?string $navigationGroup = '社区';

    protected static ?int $navigationSort = 5;

    public static function form(Form $form): Form
    {
        return $form->schema([
            Forms\Components\Tabs::make()->tabs([
                Forms\Components\Tabs\Tab::make('基础信息')
                    ->schema([
                        Forms\Components\TextInput::make('title')
                            ->label('活动标题')
                            ->required()
                            ->maxLength(200),

                        Forms\Components\Textarea::make('description')
                            ->label('活动详情')
                            ->rows(4)
                            ->maxLength(5000),

                        Forms\Components\Select::make('user_id')
                            ->label('发起人')
                            ->relationship('user', 'name')
                            ->searchable()
                            ->preload()
                            ->required()
                            ->default(fn () => auth()->id()),

                        Forms\Components\Select::make('status')
                            ->label('状态')
                            ->options(Activity::STATUSES)
                            ->default('open')
                            ->required(),

                        Forms\Components\Toggle::make('is_public')
                            ->label('公开可见')
                            ->default(true)
                            ->inline(false),
                    ]),

                Forms\Components\Tabs\Tab::make('时间地点')
                    ->schema([
                        Forms\Components\DateTimePicker::make('start_at')
                            ->label('出发时间')
                            ->required()
                            ->native(false),

                        Forms\Components\DateTimePicker::make('end_at')
                            ->label('结束时间')
                            ->native(false)
                            ->after('start_at'),

                        Forms\Components\DateTimePicker::make('signup_deadline')
                            ->label('报名截止')
                            ->native(false)
                            ->before('start_at'),

                        Forms\Components\TextInput::make('meeting_point')
                            ->label('集合地点')
                            ->maxLength(200),

                        Forms\Components\Grid::make(2)->schema([
                            Forms\Components\TextInput::make('latitude')
                                ->label('纬度')
                                ->numeric()
                                ->step(0.0000001),
                            Forms\Components\TextInput::make('longitude')
                                ->label('经度')
                                ->numeric()
                                ->step(0.0000001),
                        ]),
                    ]),

                Forms\Components\Tabs\Tab::make('出行 & 费用')
                    ->schema([
                        Forms\Components\TextInput::make('transport')
                            ->label('出行方式')
                            ->placeholder('自驾/包车/徒步...')
                            ->maxLength(50),

                        Forms\Components\TextInput::make('max_participants')
                            ->label('人数上限（0 = 不限）')
                            ->numeric()
                            ->minValue(0)
                            ->maxValue(255)
                            ->default(0),

                        Forms\Components\TextInput::make('fee')
                            ->label('人均费用（¥）')
                            ->numeric()
                            ->minValue(0)
                            ->default(0)
                            ->prefix('¥'),

                        Forms\Components\TextInput::make('fee_includes')
                            ->label('费用包含')
                            ->maxLength(500),

                        Forms\Components\TextInput::make('fee_excludes')
                            ->label('费用不含')
                            ->maxLength(500),
                    ]),

                Forms\Components\Tabs\Tab::make('关联内容')
                    ->schema([
                        Forms\Components\Select::make('place_id')
                            ->label('关联地点')
                            ->relationship('place', 'name', fn (Builder $q) => $q->where('is_public', true))
                            ->searchable()
                            ->preload()
                            ->placeholder('可选'),

                        Forms\Components\Select::make('route_id')
                            ->label('关联线路')
                            ->relationship('route', 'name', fn (Builder $q) => $q->where('is_public', true))
                            ->searchable()
                            ->preload()
                            ->placeholder('可选'),

                        Forms\Components\Grid::make(2)->schema([
                            Forms\Components\TextInput::make('region_code')
                                ->label('城市代码')
                                ->placeholder('如 HZ')
                                ->maxLength(20),
                            Forms\Components\TextInput::make('region_name')
                                ->label('城市名')
                                ->placeholder('如 杭州')
                                ->maxLength(50),
                        ]),
                    ]),
            ])->columnSpanFull(),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('title')
                    ->label('标题')
                    ->searchable()
                    ->limit(40)
                    ->weight('bold'),

                Tables\Columns\TextColumn::make('user.name')
                    ->label('发起人')
                    ->searchable(),

                Tables\Columns\TextColumn::make('region_name')
                    ->label('城市')
                    ->badge()
                    ->color('info'),

                Tables\Columns\TextColumn::make('start_at')
                    ->label('出发时间')
                    ->dateTime('m-d H:i')
                    ->sortable(),

                Tables\Columns\TextColumn::make('transport')
                    ->label('出行'),

                Tables\Columns\TextColumn::make('fee')
                    ->label('费用')
                    ->money('CNY')
                    ->alignEnd(),

                Tables\Columns\TextColumn::make('joined_count_calculated')
                    ->label('报名')
                    ->getStateUsing(fn ($record) => $record->joined_count . ($record->max_participants > 0 ? '/' . $record->max_participants : ''))
                    ->alignCenter(),

                Tables\Columns\TextColumn::make('status')
                    ->label('状态')
                    ->badge()
                    ->color(fn (string $state) => match ($state) {
                        'open' => 'success',
                        'full' => 'warning',
                        'closed', 'cancelled' => 'danger',
                        default => 'gray',
                    })
                    ->formatStateUsing(fn (string $state) => Activity::STATUSES[$state] ?? $state),

                Tables\Columns\IconColumn::make('is_public')
                    ->label('公开')
                    ->boolean(),

                Tables\Columns\TextColumn::make('view_count')
                    ->label('浏览')
                    ->alignCenter()
                    ->sortable(),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('status')
                    ->label('状态')
                    ->options(Activity::STATUSES),

                Tables\Filters\SelectFilter::make('region_code')
                    ->label('城市')
                    ->options(fn () => \App\Models\Region::where('level', 'city')
                        ->orderBy('is_hot', 'desc')
                        ->orderBy('sort')
                        ->get()
                        ->mapWithKeys(fn ($r) => [$r->code => $r->name])
                        ->take(50)
                        ->toArray()),

                Tables\Filters\TernaryFilter::make('is_public')
                    ->label('是否公开'),

                Tables\Filters\Filter::make('upcoming')
                    ->label('即将出发')
                    ->query(fn (Builder $q) => $q->where('start_at', '>=', now()))
                    ->toggle(),
            ])
            ->actions([
                Tables\Actions\ViewAction::make(),
                Tables\Actions\EditAction::make(),
                Tables\Actions\Action::make('cancel')
                    ->label('取消活动')
                    ->icon('heroicon-o-x-circle')
                    ->color('danger')
                    ->requiresConfirmation()
                    ->visible(fn ($record) => $record->status !== 'cancelled')
                    ->action(fn ($record) => $record->update(['status' => 'cancelled'])),
                Tables\Actions\DeleteAction::make(),
            ])
            ->defaultSort('start_at', 'desc');
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListActivities::route('/'),
            'create' => Pages\CreateActivity::route('/create'),
            'edit' => Pages\EditActivity::route('/{record}/edit'),
        ];
    }

    public static function getNavigationBadge(): ?string
    {
        $n = static::getModel()::where('start_at', '>=', now())->whereIn('status', ['open', 'full'])->count();
        return $n > 0 ? (string) $n : null;
    }
}
