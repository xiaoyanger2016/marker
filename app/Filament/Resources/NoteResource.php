<?php

namespace App\Filament\Resources;

use App\Filament\Resources\NoteResource\Pages;
use App\Models\Note;
use App\Models\Place;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

class NoteResource extends Resource
{
    protected static ?string $model = Note::class;

    protected static ?string $navigationIcon = 'heroicon-o-document-text';

    protected static ?string $navigationLabel = '笔记/小红书';

    protected static ?string $modelLabel = '笔记';

    protected static ?int $navigationSort = 40;

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Hidden::make('user_id')
                    ->default(fn () => auth()->id()),

                Forms\Components\Select::make('place_id')
                    ->label('关联地点')
                    ->relationship('place', 'name', fn ($query) => $query->where('user_id', auth()->id()))
                    ->searchable()
                    ->preload()
                    ->createOptionForm([
                        Forms\Components\TextInput::make('name')->required(),
                        Forms\Components\TextInput::make('latitude')->required()->numeric(),
                        Forms\Components\TextInput::make('longitude')->required()->numeric(),
                    ])
                    ->createOptionUsing(function (array $data) {
                        $data['user_id'] = auth()->id();
                        $data['slug'] = \Illuminate\Support\Str::slug($data['name']);
                        $data['country'] = '中国';
                        $data['poi_source'] = 'manual';
                        return Place::create($data)->getKey();
                    }),

                Forms\Components\TextInput::make('title')
                    ->label('标题')
                    ->required()
                    ->maxLength(300)
                    ->columnSpanFull(),

                Forms\Components\Grid::make(3)->schema([
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

                    Forms\Components\TextInput::make('author')->label('作者')->maxLength(100),
                    Forms\Components\DateTimePicker::make('published_at')->label('发布时间'),
                ]),

                Forms\Components\TextInput::make('source_url')
                    ->label('原始链接')
                    ->url()
                    ->placeholder('https://www.xiaohongshu.com/explore/...')
                    ->columnSpanFull()
                    ->visible(fn (Forms\Get $get) => $get('source') !== 'manual'),

                Forms\Components\Textarea::make('content')
                    ->label('内容')
                    ->rows(5)
                    ->columnSpanFull(),

                Forms\Components\TextInput::make('cover_url')
                    ->label('封面图URL')
                    ->url()
                    ->columnSpanFull(),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->defaultSort('created_at', 'desc')
            ->columns([
                Tables\Columns\ImageColumn::make('cover_url')
                    ->label('封面')
                    ->height(50)
                    ->width(50),

                Tables\Columns\TextColumn::make('title')
                    ->label('标题')
                    ->searchable()
                    ->limit(40)
                    ->weight('bold'),

                Tables\Columns\TextColumn::make('place.name')
                    ->label('关联地点')
                    ->searchable()
                    ->limit(30),

                Tables\Columns\TextColumn::make('source')
                    ->label('来源')
                    ->badge()
                    ->color(fn ($state) => match ($state) {
                        'xiaohongshu' => 'danger',
                        'dianping' => 'warning',
                        default => 'gray',
                    })
                    ->formatStateUsing(fn ($state) => match ($state) {
                        'xiaohongshu' => '小红书',
                        'dianping' => '大众点评',
                        default => '手动',
                    }),

                Tables\Columns\TextColumn::make('author')->label('作者'),

                Tables\Columns\TextColumn::make('source_url')
                    ->label('链接')
                    ->url(fn ($record) => $record->source_url)
                    ->openUrlInNewTab()
                    ->limit(30)
                    ->toggleable(),

                Tables\Columns\TextColumn::make('created_at')
                    ->label('创建')
                    ->dateTime('Y-m-d'),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('source')
                    ->label('来源')
                    ->options([
                        'manual' => '手动',
                        'xiaohongshu' => '小红书',
                        'dianping' => '大众点评',
                        'mafengwo' => '马蜂窝',
                    ])
                    ->multiple(),
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
                Tables\Actions\DeleteAction::make(),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListNotes::route('/'),
            'create' => Pages\CreateNote::route('/create'),
            'edit' => Pages\EditNote::route('/{record}/edit'),
        ];
    }

    public static function getEloquentQuery(): \Illuminate\Database\Eloquent\Builder
    {
        return parent::getEloquentQuery()->where('user_id', auth()->id());
    }
}
