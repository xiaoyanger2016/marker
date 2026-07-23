<?php

namespace App\Filament\Resources;

use App\Filament\Resources\RegionResource\Pages;
use App\Models\Region;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class RegionResource extends Resource
{
    protected static ?string $model = Region::class;

    protected static ?string $navigationIcon = 'heroicon-o-map-pin';

    protected static ?string $navigationLabel = '区域';

    protected static ?string $modelLabel = '区域';

    protected static ?string $pluralModelLabel = '区域';

    protected static ?string $navigationGroup = '基础数据';

    protected static ?int $navigationSort = 10;

    public static function form(Form $form): Form
    {
        return $form->schema([
            Forms\Components\Grid::make(2)->schema([
                Forms\Components\TextInput::make('code')
                    ->label('代码')
                    ->required()
                    ->maxLength(20)
                    ->unique(ignoreRecord: true),

                Forms\Components\TextInput::make('parent_code')
                    ->label('父级代码')
                    ->maxLength(20)
                    ->helperText('顶级（国家）留空；省级填 CN；市级填所属省代码'),
            ]),

            Forms\Components\Grid::make(3)->schema([
                Forms\Components\TextInput::make('name')
                    ->label('名称')
                    ->required()
                    ->maxLength(50),

                Forms\Components\TextInput::make('short_name')
                    ->label('简称')
                    ->maxLength(20),

                Forms\Components\Select::make('level')
                    ->label('级别')
                    ->options([
                        'country' => '国家',
                        'province' => '省份',
                        'city' => '城市',
                    ])
                    ->required(),
            ]),

            Forms\Components\Grid::make(3)->schema([
                Forms\Components\TextInput::make('pinyin')
                    ->label('拼音')
                    ->maxLength(100),

                Forms\Components\TextInput::make('latitude')
                    ->label('纬度')
                    ->numeric()
                    ->step(0.0000001),

                Forms\Components\TextInput::make('longitude')
                    ->label('经度')
                    ->numeric()
                    ->step(0.0000001),
            ]),

            Forms\Components\Grid::make(2)->schema([
                Forms\Components\Toggle::make('is_hot')
                    ->label('热门')
                    ->inline(false),

                Forms\Components\TextInput::make('sort')
                    ->label('排序')
                    ->numeric()
                    ->default(0),
            ]),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('code')
                    ->label('代码')
                    ->searchable()
                    ->sortable()
                    ->copyable(),

                Tables\Columns\TextColumn::make('name')
                    ->label('名称')
                    ->searchable()
                    ->sortable()
                    ->weight('bold'),

                Tables\Columns\TextColumn::make('level')
                    ->label('级别')
                    ->badge()
                    ->color(fn (string $state) => match ($state) {
                        'country' => 'danger',
                        'province' => 'warning',
                        'city' => 'info',
                    })
                    ->formatStateUsing(fn (string $state) => match ($state) {
                        'country' => '国家', 'province' => '省份', 'city' => '城市', default => $state,
                    }),

                Tables\Columns\TextColumn::make('parent_code')
                    ->label('父级')
                    ->placeholder('—')
                    ->searchable(),

                Tables\Columns\TextColumn::make('pinyin')
                    ->label('拼音')
                    ->placeholder('—')
                    ->toggleable(isToggledHiddenByDefault: true),

                Tables\Columns\TextColumn::make('short_name')
                    ->label('简称')
                    ->placeholder('—'),

                Tables\Columns\IconColumn::make('is_hot')
                    ->label('热门')
                    ->boolean(),

                Tables\Columns\TextColumn::make('sort')
                    ->label('排序')
                    ->numeric()
                    ->sortable(),

                Tables\Columns\TextColumn::make('latitude')
                    ->label('纬度')
                    ->numeric(6)
                    ->placeholder('—')
                    ->toggleable(isToggledHiddenByDefault: true),

                Tables\Columns\TextColumn::make('longitude')
                    ->label('经度')
                    ->numeric(6)
                    ->placeholder('—')
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('level')
                    ->label('级别')
                    ->options([
                        'country' => '国家',
                        'province' => '省份',
                        'city' => '城市',
                    ]),

                Tables\Filters\TernaryFilter::make('is_hot')
                    ->label('热门'),

                Tables\Filters\Filter::make('has_parent')
                    ->label('有父级')
                    ->query(fn (Builder $q) => $q->whereNotNull('parent_code'))
                    ->toggle(),

                Tables\Filters\Filter::make('no_parent')
                    ->label('顶级')
                    ->query(fn (Builder $q) => $q->whereNull('parent_code'))
                    ->toggle(),
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
                Tables\Actions\DeleteAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ])
            ->modifyQueryUsing(fn ($query) => $query
                ->orderByRaw("CASE level WHEN 'country' THEN 1 WHEN 'province' THEN 2 WHEN 'city' THEN 3 ELSE 4 END")
                ->orderBy('sort')
                ->orderBy('code')
            );
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListRegions::route('/'),
            'create' => Pages\CreateRegion::route('/create'),
            'edit' => Pages\EditRegion::route('/{record}/edit'),
        ];
    }

    public static function getNavigationBadge(): ?string
    {
        return (string) static::getModel()::count();
    }
}
