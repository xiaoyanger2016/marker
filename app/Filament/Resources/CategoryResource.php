<?php

namespace App\Filament\Resources;

use App\Filament\Resources\CategoryResource\Pages;
use App\Models\Category;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class CategoryResource extends Resource
{
    protected static ?string $model = Category::class;

    protected static ?string $navigationIcon = 'heroicon-o-rectangle-stack';

    protected static ?string $navigationLabel = '分类';

    protected static ?string $modelLabel = '分类';

    protected static ?int $navigationSort = 20;

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Hidden::make('user_id')
                    ->default(fn () => auth()->id()),

                Forms\Components\TextInput::make('name')
                    ->label('名称')
                    ->required()
                    ->maxLength(100)
                    ->live(onBlur: true)
                    ->afterStateUpdated(fn (Forms\Set $set, ?string $state) => $set('slug', \Illuminate\Support\Str::slug($state ?? ''))),

                Forms\Components\TextInput::make('slug')
                    ->label('Slug')
                    ->required()
                    ->maxLength(100),

                Forms\Components\Grid::make(2)->schema([
                    Forms\Components\TextInput::make('icon')
                        ->label('图标 (emoji 或文字)')
                        ->placeholder('N°01 · 露营')
                        ->maxLength(50),

                    Forms\Components\ColorPicker::make('color')
                        ->label('颜色'),
                ]),

                Forms\Components\Textarea::make('description')
                    ->label('说明')
                    ->rows(2)
                    ->columnSpanFull(),

                Forms\Components\Grid::make(2)->schema([
                    Forms\Components\TextInput::make('sort')
                        ->label('排序')
                        ->numeric()
                        ->default(0),
                    Forms\Components\Toggle::make('is_active')
                        ->label('启用')
                        ->default(true),
                ]),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->defaultSort('sort')
            ->columns([
                Tables\Columns\TextColumn::make('icon')
                    ->label('图标')
                    ->size('lg'),
                Tables\Columns\TextColumn::make('name')
                    ->label('名称')
                    ->searchable()
                    ->weight('bold'),
                Tables\Columns\ColorColumn::make('color')
                    ->label('颜色'),
                Tables\Columns\TextColumn::make('places_count')
                    ->counts('places')
                    ->label('地点数')
                    ->badge(),
                Tables\Columns\TextColumn::make('sort')->label('排序'),
                Tables\Columns\IconColumn::make('is_active')->label('启用')->boolean(),
                Tables\Columns\TextColumn::make('user_id')
                    ->label('类型')
                    ->formatStateUsing(fn ($state) => $state ? '个人' : '系统')
                    ->badge()
                    ->color(fn ($state) => $state ? 'warning' : 'info'),
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
                Tables\Actions\DeleteAction::make(),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListCategories::route('/'),
            'create' => Pages\CreateCategory::route('/create'),
            'edit' => Pages\EditCategory::route('/{record}/edit'),
        ];
    }

    public static function getEloquentQuery(): Builder
    {
        $query = parent::getEloquentQuery();
        // 显示系统预设 + 自己的
        return $query->where(fn ($q) => $q->whereNull('user_id')->orWhere('user_id', auth()->id()));
    }
}
