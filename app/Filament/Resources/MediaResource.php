<?php

namespace App\Filament\Resources;

use App\Filament\Resources\MediaResource\Pages;
use App\Models\Media;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

class MediaResource extends Resource
{
    protected static ?string $model = Media::class;

    protected static ?string $navigationIcon = 'heroicon-o-photo';

    protected static ?string $navigationLabel = '媒体库';

    protected static ?string $modelLabel = '媒体';

    protected static ?string $pluralModelLabel = '媒体库';

    protected static ?int $navigationSort = 50;

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Hidden::make('user_id')
                    ->default(fn () => auth()->id()),

                Forms\Components\Select::make('type')
                    ->label('类型')
                    ->options([
                        'image' => '图片',
                        'video' => '视频',
                    ])
                    ->required()
                    ->default('image'),

                Forms\Components\FileUpload::make('path')
                    ->label('文件')
                    ->required()
                    ->disk('public')
                    ->directory('media')
                    ->image(fn (Forms\Get $get) => $get('type') === 'image'),

                Forms\Components\Select::make('place_id')
                    ->label('关联地点')
                    ->relationship('place', 'name', fn ($query) => $query->where('user_id', auth()->id()))
                    ->searchable()
                    ->preload(),

                Forms\Components\TextInput::make('title')->maxLength(255),
                Forms\Components\Textarea::make('caption')->rows(2)->columnSpanFull(),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->defaultSort('created_at', 'desc')
            ->columns([
                Tables\Columns\ImageColumn::make('path')
                    ->label('预览')
                    ->disk('public')
                    ->height(60)
                    ->width(60)
                    ->extraImgAttributes(['style' => 'object-fit: cover; border-radius: 4px;']),

                Tables\Columns\TextColumn::make('type')
                    ->label('类型')
                    ->badge()
                    ->formatStateUsing(fn ($state) => $state === 'image' ? '图片' : '视频'),

                Tables\Columns\TextColumn::make('place.name')
                    ->label('关联地点')
                    ->limit(30)
                    ->searchable(),

                Tables\Columns\TextColumn::make('title')->label('标题')->searchable(),
                Tables\Columns\TextColumn::make('size')
                    ->label('大小')
                    ->formatStateUsing(fn ($state) => $state ? round($state / 1024, 1) . ' KB' : '-'),
            ])
            ->actions([
                Tables\Actions\DeleteAction::make(),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListMedia::route('/'),
            'create' => Pages\CreateMedia::route('/create'),
        ];
    }

    public static function getEloquentQuery(): \Illuminate\Database\Eloquent\Builder
    {
        return parent::getEloquentQuery()->where('user_id', auth()->id());
    }
}
